<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // assign_batch, update_result
    $worker_id = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;
    $user_id = 1; // TODO: session

    if (!$worker_id || empty($action)) {
        throw new Exception("Worker ID and action are required.");
    }

    clms_db_begin_transaction($conn);

    if ($action === 'assign_batch') {
        $batch_id = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;
        if (!$batch_id) throw new Exception("Batch ID required");

        // Verify batch exists
        $batchCheck = clms_db_query($conn, "SELECT * FROM safety_batches WHERE batch_id = $batch_id FOR UPDATE");
        if (clms_db_num_rows($batchCheck) === 0) throw new Exception("Batch not found.");
        $batch = clms_db_fetch_assoc($batchCheck);

        // Assign worker to batch
        $insertQuery = "INSERT INTO worker_safety (worker_id, batch_no, trainer_name, result)
                        VALUES ($worker_id, $batch_id, '{$batch['trainer']}', 'Scheduled')";
        if (!clms_db_query($conn, $insertQuery)) {
            throw new Exception("Failed to assign safety batch: " . clms_db_error($conn));
        }

        // Update worker status
        $updateWorker = "UPDATE worker_master SET safety_status = 'Scheduled' WHERE worker_id = $worker_id";
        clms_db_query($conn, $updateWorker);

        echo json_encode(['status' => 'success', 'message' => 'Worker assigned to safety batch']);
    } 
    elseif ($action === 'update_result') {
        $safety_id = isset($_POST['safety_id']) ? (int)$_POST['safety_id'] : 0;
        $result_status = isset($_POST['result_status']) ? clms_db_real_escape_string($conn, $_POST['result_status']) : ''; // Passed/Failed
        $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 0;
        $remarks = isset($_POST['remarks']) ? clms_db_real_escape_string($conn, $_POST['remarks']) : '';

        if (!$safety_id || !in_array($result_status, ['Passed', 'Failed'])) {
            throw new Exception("Valid Safety ID and Result (Passed/Failed) required");
        }

        // Update safety record
        $validity = $result_status === 'Passed' ? "DATE_ADD(NOW(), INTERVAL 1 YEAR)" : "NULL";
        $updateQuery = "UPDATE worker_safety 
                        SET result = '$result_status', marks_obtained = $marks, remarks = '$remarks', validity_date = $validity
                        WHERE safety_id = $safety_id";
        clms_db_query($conn, $updateQuery);

        // Update worker master status
        $workerStatus = $result_status === 'Passed' ? 'Pass Pending' : 'Safety Failed';
        $updateWorker = "UPDATE worker_master 
                         SET safety_status = '$result_status', worker_status = '$workerStatus' 
                         WHERE worker_id = $worker_id";
        clms_db_query($conn, $updateWorker);
        
        // Log the action
        $logQuery = "INSERT INTO worker_audit_logs (worker_id, module_name, action_type, old_values, new_values, ip_address, browser_info, remarks, created_by) 
                     VALUES ($worker_id, 'Safety Training', 'Result Update', '{\"status\":\"Pending\"}', '{\"status\":\"$result_status\"}', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'Updated safety result to $result_status', $user_id)";
        clms_db_query($conn, $logQuery);

        echo json_encode(['status' => 'success', 'message' => "Safety result updated to $result_status"]);
    } 
    else {
        throw new Exception("Invalid action.");
    }

    clms_db_commit($conn);

} catch (Exception $e) {
    if (isset($conn)) clms_db_rollback($conn);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
