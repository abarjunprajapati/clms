<?php
session_start();
header('Content-Type: application/json');
include '../../include/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$contractor_id = intval($_POST['contractor_id'] ?? 0);
$report_date = $_POST['report_date'] ?? date('Y-m-d');
$dept_id = intval($_POST['dept_id'] ?? 0);
$work_description = trim($_POST['work_description'] ?? '');
$output_unit = trim($_POST['output_unit'] ?? '');
$output_qty = floatval($_POST['output_qty'] ?? 0);
$manpower_deployed = intval($_POST['manpower_deployed'] ?? 0);
$remarks = trim($_POST['remarks'] ?? '');

if (!$dept_id || !$work_description || !$output_qty || !$manpower_deployed) {
    die(json_encode(['success' => false, 'message' => 'Please fill all mandatory fields']));
}

$sql = "INSERT INTO productivity_reports (contractor_id, report_date, dept_id, work_description, output_unit, output_qty, manpower_deployed, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]));
}

$stmt->bind_param("isissdis", $contractor_id, $report_date, $dept_id, $work_description, $output_unit, $output_qty, $manpower_deployed, $remarks);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Productivity report saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Execution error: ' . $stmt->error]);
}
?>
