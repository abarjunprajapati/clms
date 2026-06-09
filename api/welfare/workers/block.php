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
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // 'block' or 'unblock'
    $reason = isset($_POST['reason']) ? clms_db_real_escape_string($conn, trim($_POST['reason'])) : '';
    $user_id = 1; // TODO: Get from session

    if (!$worker_id || !in_array($action, ['block', 'unblock']) || empty($reason)) {
        throw new Exception("Worker ID, action (block/unblock), and reason are required.");
    }

    clms_db_begin_transaction($conn);

    $checkQuery = "SELECT worker_status FROM worker_master WHERE worker_id = $worker_id FOR UPDATE";
    $result = clms_db_query($conn, $checkQuery);
    
    if (clms_db_num_rows($result) === 0) {
        throw new Exception("Worker not found.");
    }
    
    $worker = clms_db_fetch_assoc($result);
    $current_status = $worker['worker_status'];
    
    if ($action === 'block' && $current_status === 'Blocked') {
        throw new Exception("Worker is already blocked.");
    }
    
    if ($action === 'unblock' && $current_status !== 'Blocked') {
        throw new Exception("Worker is not blocked.");
    }

    if ($action === 'block') {
        $updateQuery = "UPDATE worker_master 
                        SET worker_status = 'Blocked', 
                            blocked_at = NOW(), 
                            blocked_by = $user_id, 
                            blocked_reason = '$reason',
                            attendance_status = 'Inactive'
                        WHERE worker_id = $worker_id";
        
        $historyQuery = "INSERT INTO worker_block_history (worker_id, block_type, reason, blocked_by) 
                         VALUES ($worker_id, 'Block', '$reason', $user_id)";
    } else {
        $updateQuery = "UPDATE worker_master 
                        SET worker_status = 'Active', 
                            blocked_at = NULL, 
                            blocked_by = NULL, 
                            blocked_reason = NULL 
                        WHERE worker_id = $worker_id";
                        
        $historyQuery = "UPDATE worker_block_history 
                         SET unblocked_by = $user_id, 
                             unblocked_at = NOW(), 
                             remarks = '$reason'
                         WHERE worker_id = $worker_id AND unblocked_at IS NULL ORDER BY blocked_at DESC LIMIT 1";
    }
                    
    if (!clms_db_query($conn, $updateQuery)) {
        throw new Exception("Failed to update worker status: " . clms_db_error($conn));
    }
    
    // Sync block/unblock to workmen table
    $workmanStatus = $action === 'block' ? 'blocked' : 'active';
    $updateWorkmanStatus = "UPDATE workmen SET status = '$workmanStatus' WHERE id = $worker_id";
    if (!clms_db_query($conn, $updateWorkmanStatus)) {
        throw new Exception("Failed to update workmen block status: " . clms_db_error($conn));
    }
    
    if (!clms_db_query($conn, $historyQuery)) {
        throw new Exception("Failed to update block history: " . clms_db_error($conn));
    }

    // Log the action
    $oldValues = json_encode(['worker_status' => $current_status]);
    $newValues = json_encode(['worker_status' => $action === 'block' ? 'Blocked' : 'Active']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $browser = $_SERVER['HTTP_USER_AGENT'];
    
    $logQuery = "INSERT INTO worker_audit_logs (worker_id, module_name, action_type, old_values, new_values, ip_address, browser_info, remarks, created_by) 
                 VALUES ($worker_id, 'Enrolled Workers', '" . ucfirst($action) . "', '$oldValues', '$newValues', '$ip', '$browser', '$reason', $user_id)";
                 
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
