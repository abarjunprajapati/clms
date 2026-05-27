<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../api/WorkflowEngine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

$worker_id = (int)($_POST['worker_id'] ?? 0);
$action = $_POST['action'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$worker_id || !in_array($action, ['pass', 'fail'])) {
    die("Invalid parameters");
}

if ($action == 'pass') {
    $valid_till = date('Y-m-d', strtotime('+1 year'));
    db_execute($conn, "
        UPDATE workmen 
        SET training_status = 'PASS', 
            eligibility_status = 'ELIGIBLE',
            training_valid_till = ?,
            updated_at = NOW() 
        WHERE id = ?
    ", 'si', [$valid_till, $worker_id]);

    $worker_data = db_single($conn, "SELECT application_no, contractor_id FROM workmen WHERE id = ?", 'i', [$worker_id]);
    $contractor_id = $worker_data['contractor_id'] ?? 0;

    // Make sure there is a request to update, or insert one
    $exists = db_single($conn, "SELECT id FROM training_requests WHERE workman_id = ?", 'i', [$worker_id]);
    if ($exists) {
        db_execute($conn, "
            UPDATE training_requests 
            SET status = 'completed' 
            WHERE workman_id = ?
        ", 'i', [$worker_id]);
    } else {
        // If it doesn't exist, we can create it so the JOIN works.
        db_execute($conn, "
            INSERT INTO training_requests (workman_id, contractor_id, training_type, status, requested_date)
            VALUES (?, ?, 'Safety Induction', 'completed', CURDATE())
        ", 'ii', [$worker_id, $contractor_id]);
    }
    
    // Update training results table to align with the gate pass query
    $app_no = $worker_data['application_no'] ?? '';
    $resExists = db_single($conn, "SELECT id FROM training_results WHERE workman_id = ?", 'i', [$worker_id]);
    if ($resExists) {
        db_execute($conn, "UPDATE training_results SET result = 'pass', updated_at = NOW() WHERE workman_id = ?", 'i', [$worker_id]);
    } else {
        db_execute($conn, "INSERT INTO training_results (workman_id, application_no, result, recorded_by, attendance_status, created_at) VALUES (?, ?, 'pass', ?, 'present', NOW())", 'isi', [$worker_id, $app_no, $user_id]);
    }

    if ($worker_data && $worker_data['application_no']) {
        WorkflowEngine::performAction($conn, $worker_data['application_no'], 'complete_training', $_SESSION['role'], $user_id, 'Safety Training Completed');
    }
}

if ($action == 'fail') {
    db_execute($conn, "
        UPDATE workmen 
        SET training_status = 'FAIL', 
            eligibility_status = 'NOT ELIGIBLE',
            training_valid_till = NULL,
            updated_at = NOW() 
        WHERE id = ?
    ", 'i', [$worker_id]);
    
    // Update training results
    $worker_data = db_single($conn, "SELECT application_no FROM workmen WHERE id = ?", 'i', [$worker_id]);
    $app_no = $worker_data['application_no'] ?? '';
    
    $resExists = db_single($conn, "SELECT id FROM training_results WHERE workman_id = ?", 'i', [$worker_id]);
    if ($resExists) {
        db_execute($conn, "UPDATE training_results SET result = 'failed', updated_at = NOW() WHERE workman_id = ?", 'i', [$worker_id]);
    } else {
        db_execute($conn, "INSERT INTO training_results (workman_id, application_no, result, recorded_by, attendance_status, created_at) VALUES (?, ?, 'failed', ?, 'present', NOW())", 'isi', [$worker_id, $app_no, $user_id]);
    }
}

header("Location: enrollment_monitor.php");
exit;
?>

