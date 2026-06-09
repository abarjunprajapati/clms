<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $worker_id = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;
    $user_id = 1; // TODO: session

    if (!$worker_id) {
        throw new Exception("Worker ID is required.");
    }

    clms_db_begin_transaction($conn);

    $checkQuery = "SELECT * FROM worker_master WHERE worker_id = $worker_id FOR UPDATE";
    $result = clms_db_query($conn, $checkQuery);
    
    if (clms_db_num_rows($result) === 0) {
        throw new Exception("Worker not found.");
    }
    $worker = clms_db_fetch_assoc($result);

    $hasNationality = false;
    $colRes = clms_db_query($conn, "SHOW COLUMNS FROM workmen LIKE 'nationality'");
    if ($colRes && clms_db_num_rows($colRes) > 0) {
        $hasNationality = true;
    }
    if (!$hasNationality) {
        @clms_db_query($conn, "ALTER TABLE workmen ADD COLUMN nationality VARCHAR(100) NULL DEFAULT 'Indian'");
    }

    // Extract fields from POST. In a real scenario, loop through allowed fields.
    $allowedFields = ['mobile_no', 'email', 'blood_group', 'contractor_id', 'department_id', 'skill_category'];
    $updates = [];
    foreach ($allowedFields as $field) {
        if (isset($_POST[$field])) {
            $val = clms_db_real_escape_string($conn, $_POST[$field]);
            $updates[] = "$field = '$val'";
        }
    }
    
    if (empty($updates)) {
        throw new Exception("No valid fields provided for update.");
    }

    $updates[] = "updated_by = $user_id";
    $updates[] = "updated_at = NOW()";

    $updateQuery = "UPDATE worker_master SET " . implode(', ', $updates) . " WHERE worker_id = $worker_id";
    
    if (!clms_db_query($conn, $updateQuery)) {
        throw new Exception("Failed to update worker: " . clms_db_error($conn));
    }

    if (isset($_POST['nationality'])) {
        $nationality = clms_db_real_escape_string($conn, trim($_POST['nationality']) ?: 'Indian');
        clms_db_query($conn, "UPDATE workmen SET nationality = '$nationality' WHERE id = $worker_id");
    }

    // Log the action
    $oldValues = json_encode($worker); // Simply storing old state
    // In reality, would fetch new state or build selective diff
    $newValues = json_encode($_POST); 
    $ip = $_SERVER['REMOTE_ADDR'];
    $browser = $_SERVER['HTTP_USER_AGENT'];
    
    $logQuery = "INSERT INTO worker_audit_logs (worker_id, module_name, action_type, old_values, new_values, ip_address, browser_info, remarks, created_by) 
                 VALUES ($worker_id, 'Enrolled Workers', 'Edit', '$oldValues', '$newValues', '$ip', '$browser', 'Worker profile edited', $user_id)";
                 
    if (!clms_db_query($conn, $logQuery)) {
        throw new Exception("Failed to write audit log: " . clms_db_error($conn));
    }

    clms_db_commit($conn);

    echo json_encode(['status' => 'success', 'message' => 'Worker updated successfully']);

} catch (Exception $e) {
    clms_db_rollback($conn);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
