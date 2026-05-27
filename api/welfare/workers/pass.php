<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
        if (!$worker_id) throw new Exception("Worker ID required");
        
        $query = "SELECT * FROM worker_passes WHERE worker_id = $worker_id ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    // POST Actions
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // generate, block, reissue, print
    $worker_id = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;
    $user_id = 1; // session

    if (!$worker_id || empty($action)) {
        throw new Exception("Worker ID and action are required.");
    }

    mysqli_begin_transaction($conn);

    if ($action === 'generate') {
        // Business Rule 4: Failed safety = disable pass generation
        $safetyCheck = "SELECT safety_status FROM worker_master WHERE worker_id = $worker_id FOR UPDATE";
        $safetyResult = mysqli_query($conn, $safetyCheck);
        $safetyData = mysqli_fetch_assoc($safetyResult);
        
        if ($safetyData['safety_status'] === 'Failed') {
            throw new Exception("Cannot generate pass. Worker failed safety training.");
        }

        $insertQuery = "INSERT INTO worker_passes (worker_id, pass_status, acc_status, issued_by)
                        VALUES ($worker_id, 'Draft', 'Not Generated', $user_id)";
        if (!mysqli_query($conn, $insertQuery)) {
            throw new Exception("Failed to generate pass: " . mysqli_error($conn));
        }

        $updateWorker = "UPDATE worker_master SET worker_status = 'Pass Pending' WHERE worker_id = $worker_id";
        mysqli_query($conn, $updateWorker);
        
        echo json_encode(['status' => 'success', 'message' => 'Pass generated successfully (Draft state)']);
    } 
    elseif ($action === 'print') {
        $pass_id = isset($_POST['pass_id']) ? (int)$_POST['pass_id'] : 0;
        $printer = isset($_POST['printer']) ? mysqli_real_escape_string($conn, $_POST['printer']) : 'Default Printer';
        
        if (!$pass_id) throw new Exception("Pass ID required");
        
        $insertLog = "INSERT INTO worker_pass_print_logs (worker_id, pass_id, printed_by, printer_name) 
                      VALUES ($worker_id, $pass_id, $user_id, '$printer')";
        mysqli_query($conn, $insertLog);
        
        $updatePass = "UPDATE worker_passes SET pass_status = 'Issued' WHERE pass_id = $pass_id";
        mysqli_query($conn, $updatePass);

        $updateWorker = "UPDATE worker_master SET worker_status = 'Active' WHERE worker_id = $worker_id AND worker_status = 'Pass Pending'";
        mysqli_query($conn, $updateWorker);

        echo json_encode(['status' => 'success', 'message' => 'Pass printed successfully']);
    } 
    else {
        throw new Exception("Invalid action.");
    }

    mysqli_commit($conn);

} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
