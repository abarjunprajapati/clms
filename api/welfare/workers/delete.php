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
    $delete_reason = isset($_POST['delete_reason']) ? clms_db_real_escape_string($conn, trim($_POST['delete_reason'])) : '';
    $deleted_by = 1; // TODO: Get from session

    if (!$worker_id || empty($delete_reason)) {
        throw new Exception("Worker ID and delete reason are required.");
    }

    // Begin transaction
    clms_db_begin_transaction($conn);

    // Rule 6 Soft Delete Validation: Check if active attendance, active pass, etc.
    $checkQuery = "SELECT attendance_status, safety_status, worker_status, pass_type FROM worker_master WHERE worker_id = $worker_id FOR UPDATE";
    $result = clms_db_query($conn, $checkQuery);
    
    if (clms_db_num_rows($result) === 0) {
        throw new Exception("Worker not found.");
    }
    
    $worker = clms_db_fetch_assoc($result);
    
    if ($worker['attendance_status'] === 'Active') {
        throw new Exception("Cannot delete worker with active attendance.");
    }
    if ($worker['worker_status'] === 'Active' && $worker['pass_type'] !== '') {
        throw new Exception("Cannot delete worker with an active pass. Please revoke the pass first.");
    }
    
    // Perform Soft Delete
    $updateQuery = "UPDATE worker_master 
                    SET worker_status = 'Deleted', 
                        deleted_at = NOW(), 
                        deleted_by = $deleted_by, 
                        delete_reason = '$delete_reason' 
                    WHERE worker_id = $worker_id";
                    
    if (!clms_db_query($conn, $updateQuery)) {
        throw new Exception("Failed to delete worker: " . clms_db_error($conn));
    }

    // Sync soft delete to workmen table
    $updateWorkman = "UPDATE workmen SET status = 'removed' WHERE id = $worker_id";
    if (!clms_db_query($conn, $updateWorkman)) {
        throw new Exception("Failed to update workmen delete status: " . clms_db_error($conn));
    }

    // Log the action
    $oldValues = json_encode(['worker_status' => $worker['worker_status']]);
    $newValues = json_encode(['worker_status' => 'Deleted', 'delete_reason' => $delete_reason]);
    $ip = $_SERVER['REMOTE_ADDR'];
    $browser = $_SERVER['HTTP_USER_AGENT'];
    
    $logQuery = "INSERT INTO worker_audit_logs (worker_id, module_name, action_type, old_values, new_values, ip_address, browser_info, remarks, created_by) 
                 VALUES ($worker_id, 'Enrolled Workers', 'Soft Delete', '$oldValues', '$newValues', '$ip', '$browser', 'Worker soft deleted', $deleted_by)";
                 
    if (!clms_db_query($conn, $logQuery)) {
        throw new Exception("Failed to write audit log: " . clms_db_error($conn));
    }

    clms_db_commit($conn);

    echo json_encode(['status' => 'success', 'message' => 'Worker soft-deleted successfully']);

} catch (Exception $e) {
    clms_db_rollback($conn);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
