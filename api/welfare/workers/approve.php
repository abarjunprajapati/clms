<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Input validation
    $worker_id = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // 'approve' or 'reject'
    $remarks = isset($_POST['remarks']) ? clms_db_real_escape_string($conn, trim($_POST['remarks'])) : '';
    $user_id = 1; // TODO: Get from session

    if (!$worker_id || !in_array($action, ['approve', 'reject'])) {
        throw new Exception("Worker ID and valid action (approve/reject) are required.");
    }
    
    if ($action === 'reject' && empty($remarks)) {
        throw new Exception("Remarks are mandatory when rejecting a worker.");
    }

    clms_db_begin_transaction($conn);

    $checkQuery = "SELECT verification_status, worker_status, contractor_id, pass_type, worker_type FROM worker_master WHERE worker_id = $worker_id FOR UPDATE";
    $result = clms_db_query($conn, $checkQuery);
    
    if (clms_db_num_rows($result) === 0) {
        throw new Exception("Worker not found.");
    }
    
    $worker = clms_db_fetch_assoc($result);
    $current_status = $worker['verification_status'];
    
    if ($current_status === 'Approved' && $action === 'approve') {
        throw new Exception("Worker is already approved.");
    }
    
    // Validate Realtime Contractor Limit check
    if ($action === 'approve') {
        require_once '../../../include/pass_limit_validator.php';
        
        $limit_type = 'Workman';
        $w_type = $worker['worker_type'] ?? '';
        $p_type = $worker['pass_type'] ?? '';
        if (stripos($w_type, 'supervisor') !== false || stripos($p_type, 'supervisor') !== false) {
            $limit_type = 'Supervisor';
        } elseif (stripos($w_type, 'representative') !== false || stripos($p_type, 'representative') !== false) {
            $limit_type = 'Representative';
        } elseif (stripos($w_type, 'contractor') !== false || stripos($p_type, 'contractor') !== false) {
            $limit_type = 'Contractor';
        }
        
        validatePassLimit($conn, (int)$worker['contractor_id'], $limit_type, 1, false);
    }

    $newVerificationStatus = $action === 'approve' ? 'Approved' : 'Rejected';
    // If approved, move to Safety Pending. If rejected, stay at Rejected.
    $newWorkerStatus = $action === 'approve' ? 'Safety Pending' : 'Rejected';

    $updateQuery = "UPDATE worker_master 
                    SET verification_status = '$newVerificationStatus', 
                        worker_status = '$newWorkerStatus',
                        updated_by = $user_id,
                        updated_at = NOW()
                    WHERE worker_id = $worker_id";
                        
    if (!clms_db_query($conn, $updateQuery)) {
        throw new Exception("Failed to update worker status: " . clms_db_error($conn));
    }

    // Sync status to workmen table
    $newWorkmanStatus = $action === 'approve' ? 'approved' : 'rejected';
    $newSafetyStatus = $action === 'approve' ? 'PENDING_TRAINING' : 'FAILED_TRAINING';
    $updateWorkmanQuery = "UPDATE workmen 
                           SET status = '$newWorkmanStatus', 
                               safety_training_status = '$newSafetyStatus',
                               updated_at = NOW()
                           WHERE id = $worker_id";
    if (!clms_db_query($conn, $updateWorkmanQuery)) {
        throw new Exception("Failed to update workmen status: " . clms_db_error($conn));
    }

    // Log the action
    $oldValues = json_encode(['verification_status' => $current_status, 'worker_status' => $worker['worker_status']]);
    $newValues = json_encode(['verification_status' => $newVerificationStatus, 'worker_status' => $newWorkerStatus]);
    $ip = $_SERVER['REMOTE_ADDR'];
    $browser = $_SERVER['HTTP_USER_AGENT'];
    
    $logQuery = "INSERT INTO worker_audit_logs (worker_id, module_name, action_type, old_values, new_values, ip_address, browser_info, remarks, created_by) 
                 VALUES ($worker_id, 'Enrolled Workers', '" . ucfirst($action) . "', '$oldValues', '$newValues', '$ip', '$browser', '$remarks', $user_id)";
                 
    if (!clms_db_query($conn, $logQuery)) {
        throw new Exception("Failed to write audit log: " . clms_db_error($conn));
    }

    clms_db_commit($conn);

    echo json_encode(['status' => 'success', 'message' => "Worker successfully {$action}ed"]);

} catch (Exception $e) {
    clms_db_rollback($conn);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
