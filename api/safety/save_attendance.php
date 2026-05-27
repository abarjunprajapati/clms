<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$session_id = intval($_POST['session_id'] ?? 0);
$attendance_data = $_POST['attendance'] ?? [];
$remarks_data = $_POST['remarks'] ?? [];

header('Content-Type: application/json');

if (!$session_id || empty($attendance_data)) {
    echo json_encode(["success" => false, "message" => "No data to save"]);
    exit;
}

// Check session status
$session = db_single($conn, "SELECT session_status FROM training_schedule WHERE id=?", 'i', [$session_id]);
if (!$session || $session['session_status'] == 'completed') {
    echo json_encode(["success" => false, "message" => "Session locked"]);
    exit;
}

mysqli_begin_transaction($conn);
try {
    foreach ($attendance_data as $workman_id => $status) {
        $rem = $remarks_data[$workman_id] ?? '';
        db_execute($conn, "UPDATE training_session_workers SET attendance_status=?, remarks=? WHERE session_id=? AND workman_id=?", 'ssii', [$status, $rem, $session_id, $workman_id]);
    }
    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Attendance saved"]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

