<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';
require_once '../../include/NotificationEngine.php';

require_role(['pass_issuer', 'admin', 'welfare', 'welfare_admin']);

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = $input['workman_id'] ?? 0;
$action = $input['action'] ?? ''; // 'approve' or 'reject'
$docs = $input['docs'] ?? []; // Array of {doc_type, status, remarks}
$global_remarks = $input['remarks'] ?? '';

if (!$workman_id) {
    json_response(false, null, 'Invalid Workman ID');
}

// 1. Fetch Workman and check prerequisites
$workman = db_single($conn, "SELECT * FROM workmen WHERE id = ?", "i", [$workman_id]);
if (!$workman) {
    json_response(false, null, 'Workman not found');
}

if ($workman['is_blocked']) {
    json_response(false, null, 'Worker is blocked and cannot be verified');
}

$trainingPassed = ((int)$workman['safety_training_status'] === 1 || in_array(strtolower($workman['training_status']), ['pass', 'passed', 'training_passed', 'qualified', 'completed']));
if (!$trainingPassed) {
    json_response(false, null, 'Safety training not completed or failed. Cannot proceed.');
}

if ($workman['welfare_user_verified'] != 1) {
    json_response(false, null, 'Initial Welfare User verification pending.');
}

clms_db_begin_transaction($conn);

try {
    if ($action === 'reject') {
        db_execute($conn, "UPDATE workmen SET status = 'reupload_pending', pass_issuer_verified = 0 WHERE id = ?", "i", [$workman_id]);
        
        // Notify contractor
        $notif = new NotificationEngine($conn);
        $notif->send($workman['contractor_id'], "Pass request rejected for workman " . $workman['name'] . ". Reason: " . $global_remarks, 'warning');
        
        clms_db_commit($conn);
        json_response(true, null, 'Workman documents rejected and sent for re-upload');
    }

    // Individual document status update
    foreach ($docs as $doc) {
        $doc_type = $doc['doc_type'];
        $doc_status = $doc['status']; // 'approved' or 'rejected'
        $doc_remarks = $doc['remarks'] ?? '';
        
        // Safety Check: Cannot approve if not uploaded
        if ($doc_status === 'approved') {
            $has_file = db_count($conn, "SELECT COUNT(*) FROM documents WHERE workman_id = ? AND document_type = ?", "is", [$workman_id, $doc_type]);
            if (!$has_file) {
                throw new Exception("Cannot approve $doc_type as no file is uploaded.");
            }
        }

        // Check if record exists in workman_documents (final verification table)
        $exists = db_count($conn, "SELECT COUNT(*) FROM workman_documents WHERE workman_id = ? AND doc_type = ?", "is", [$workman_id, $doc_type]);
        
        if ($exists) {
            db_execute($conn, "UPDATE workman_documents SET status = ?, remarks = ? WHERE workman_id = ? AND doc_type = ?", "ssis", [$doc_status, $doc_remarks, $workman_id, $doc_type]);
        } else {
            db_execute($conn, "INSERT INTO workman_documents (workman_id, doc_type, status, remarks) VALUES (?, ?, ?, ?)", "isss", [$workman_id, $doc_type, $doc_status, $doc_remarks]);
        }
        
        if ($doc_status === 'rejected') {
            throw new Exception("Document $doc_type rejected. Overall status marked as re-upload pending.");
        }
    }

    // If all docs approved
    db_execute($conn, "UPDATE workmen SET status = 'verified', pass_issuer_verified = 1 WHERE id = ?", "i", [$workman_id]);
    
    // 3. Sync with gate_pass_request_workers table (Automated Workflow Transition)
    $request_worker = db_single($conn, "SELECT * FROM gate_pass_request_workers WHERE workman_id = ?", "i", [$workman_id]);
    if ($request_worker) {
        // If entry exists, mark as approved (Welfare approval complete)
        db_execute($conn, "UPDATE gate_pass_request_workers SET status = 'approved' WHERE workman_id = ?", "i", [$workman_id]);
    } else {
        // If no entry exists, AUTO-CREATE it to ensure it moves to the next stage
        $contractor_id = $workman['contractor_id'];
        
        // Find existing open request for this contractor or create a new one
        $request = db_single($conn, "SELECT id FROM gate_pass_requests WHERE contractor_id = ? ORDER BY created_at DESC LIMIT 1", "i", [$contractor_id]);
        
        if ($request) {
            $request_id = $request['id'];
        } else {
            $request_no = 'GPR-' . date('Ymd') . '-' . rand(1000, 9999);
            db_execute($conn, "INSERT INTO gate_pass_requests (request_no, contractor_id, pass_type, status) VALUES (?, ?, 'Workmen', 'pending')", "si", [$request_no, $contractor_id]);
            $request_id = clms_db_insert_id($conn);
        }
        
        db_execute($conn, "INSERT INTO gate_pass_request_workers (request_id, workman_id, status) VALUES (?, ?, 'approved')", "ii", [$request_id, $workman_id]);
    }
    
    clms_db_commit($conn);
    json_response(true, null, 'Documents verified and approved successfully');

} catch (Exception $e) {
    clms_db_rollback($conn);
    
    // If it was a doc rejection in the loop
    db_execute($conn, "UPDATE workmen SET status = 'reupload_pending', pass_issuer_verified = 0 WHERE id = ?", "i", [$workman_id]);
    
    json_response(false, null, $e->getMessage());
}

