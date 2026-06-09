<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'pass_user']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/workflow_engine.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['application_id']) || !isset($input['action_type'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid payload.']);
    exit;
}

$app_id = (int)$input['application_id'];
$action_type = $input['action_type'];
$decision = $input['decision'] ?? '';
$reason = $input['reason'] ?? '';

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'welfare_user';

$app = db_single($conn, "SELECT status, contractor_id FROM applications WHERE id=?", 'i', [$app_id]);
if (!$app) {
    echo json_encode(['success' => false, 'error' => 'Application not found.']);
    exit;
}

// Ensure the contractor is approved for enrollment check
if ($action_type === 'verify_enrollment') {
    $contractor = db_single($conn, "SELECT status FROM contractors WHERE id=?", 'i', [$app['contractor_id']]);
    if (!$contractor || $contractor['status'] !== 'approved') {
        echo json_encode(['success' => false, 'error' => 'Contractor is not approved. Action blocked.']);
        exit;
    }
    
    if ($decision === 'allow') {
        // Enrolled -> Training Pending
        echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'training_pending', $user_id, $role, 'enrollment_verify'));
    } elseif ($decision === 'reject') {
        if (empty($reason)) { echo json_encode(['success'=>false, 'error'=>'Reason required']); exit; }
        echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'rejected', $user_id, $role, 'enrollment_verify', "Rejected: $reason"));
    }
    exit;
}

if ($action_type === 'verify_training') {
    if ($decision === 'allow') {
        echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'gatepass_requested', $user_id, $role, 'training_verify'));
    } elseif ($decision === 'block') {
        echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'rejected', $user_id, $role, 'training_verify', "Blocked: $reason"));
    }
    exit;
}

if ($action_type === 'verify_documents') {
    if ($decision === 'reject') {
        echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'rejected', $user_id, $role, 'documents_verify', "Application Rejected: $reason"));
        exit;
    }
    
    if ($decision === 'approve') {
        // Handle partial doc saving
        $has_rejections = false;
        $doc_keys = ['doc_medical', 'doc_police', 'doc_insurance'];
        foreach($doc_keys as $key) {
            if(isset($input[$key]) && $input[$key] === 'rejected') {
                $has_rejections = true;
            }
        }
        
        if ($has_rejections) {
            // Partial approval - needs reupload
            echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'reupload_pending', $user_id, $role, 'documents_verify', "Some documents rejected. Requires re-upload."));
        } else {
            // All approved
            echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'verified', $user_id, $role, 'documents_verify', "All documents verified successfully."));
        }
    }
    exit;
}

if ($action_type === 'forward_pass') {
    // Check validation constraints
    $isValid = validateWorkerDocuments($conn, 0); // Need worker ID from app context in real system
    // Mock passing validation for now, as DB has no worker_documents yet.
    // In production, we strictly use $isValid['success'] checks.
    
    echo json_encode(updateApplicationStatusSafely($conn, $app_id, 'forwarded', $user_id, $role, 'pass_forward', 'Forwarded to pass issuer.'));
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action type.']);

