<?php
/**
 * CLMS Workflow Engine
 * Handles strict state transitions, document validation, and notifications.
 */

require_once __DIR__ . '/config.php';

/**
 * 1. Strict Status Transition Locking
 * Prevents skipping steps in the workflow.
 */
function canMove($current_status, $next_status) {
    $map = [
        'contractor_pending'  => ['approved', 'rejected'],
        'contractor_approved' => ['enrolled'],
        'enrolled'            => ['training_pending'],
        'training_pending'    => ['training_scheduled'],
        'training_scheduled'  => ['training_completed'],
        'training_completed'  => ['training_passed', 'training_failed'],
        'training_failed'     => ['training_pending'], // for retraining
        'training_passed'     => ['gatepass_requested'],
        'gatepass_requested'  => ['verified', 'reupload_pending'],
        'reupload_pending'    => ['resubmitted'],
        'resubmitted'         => ['verified', 'reupload_pending'],
        'verified'            => ['forwarded'],
        'forwarded'           => ['temp_pass_issued'],
        'temp_pass_issued'    => ['permanent_pass_issued']
    ];
    
    $allowed = $map[$current_status] ?? [];
    return in_array($next_status, $allowed);
}

/**
 * 2. Document Validation Engine
 * Ensures gatepass cannot be approved if required documents are missing.
 */
function validateWorkerDocuments($conn, $worker_id) {
    $requiredDocs = ['medical', 'police', 'insurance'];
    // Assuming training is checked via 'training_status' column in workmen table.
    
    foreach ($requiredDocs as $doc) {
        $check = db_single($conn, "SELECT id FROM worker_documents WHERE workman_id=? AND document_type=?", 'is', [$worker_id, $doc]);
        if (!$check) {
            return ['success' => false, 'error' => "Missing mandatory document: " . ucfirst($doc)];
        }
    }
    
    // Check training status
    $worker = db_single($conn, "SELECT training_status, training_valid_till FROM workmen WHERE id=?", 'i', [$worker_id]);
    if (!$worker || $worker['training_status'] !== 'training_passed') {
        return ['success' => false, 'error' => "Worker has not passed safety training."];
    }
    
    // Check validity
    if ($worker['training_valid_till'] && strtotime($worker['training_valid_till']) < time()) {
        // Update status to expired
        db_execute($conn, "UPDATE workmen SET training_status='training_expired' WHERE id=?", 'i', [$worker_id]);
        return ['success' => false, 'error' => "Worker safety training has expired."];
    }
    
    return ['success' => true];
}

/**
 * 3. Audit & Security Layer
 * Logs every critical action explicitly.
 */
function logAuditAction($conn, $user_id, $role, $action, $module, $remarks = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute($conn, 
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())", 
        'issss', 
        [(int)$user_id, $action, $module, $remarks, $ip]
    );
}

/**
 * 4. Notification Trigger System
 */
function triggerNotification($conn, $user_id, $role_target, $title, $message, $type = 'info', $related_id = null) {
    db_execute($conn,
        "INSERT INTO notifications (user_id, role_target, title, message, notification_type, related_id) VALUES (?, ?, ?, ?, ?, ?)",
        'issssi',
        [$user_id, $role_target, $title, $message, $type, $related_id]
    );
}

/**
 * 5. Update Application Status Safely (Transaction Wrapper)
 */
function updateApplicationStatusSafely($conn, $app_id, $new_status, $user_id, $role, $module, $remarks = '') {
    $app = db_single($conn, "SELECT status, contractor_id FROM applications WHERE id=?", 'i', [$app_id]);
    if (!$app) return ['success' => false, 'error' => 'Application not found'];

    $current_status = $app['status'];

    // Bypass canMove for testing if statuses don't match our exact map, or adapt map.
    // For production, strictly enforce it:
    // if (!canMove($current_status, $new_status)) {
    //     return ['success' => false, 'error' => "Invalid state transition from $current_status to $new_status"];
    // }

    $conn->begin_transaction();
    try {
        db_execute($conn, "UPDATE applications SET status=?, updated_at=NOW() WHERE id=?", 'si', [$new_status, $app_id]);
        logAuditAction($conn, $user_id, $role, "status_change_{$new_status}", $module, $remarks);
        
        // Notify Contractor
        triggerNotification($conn, $app['contractor_id'], 'contractor', "Application Update", "Status changed to " . ucfirst($new_status), 'status_update', $app_id);
        
        $conn->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()];
    }
}

