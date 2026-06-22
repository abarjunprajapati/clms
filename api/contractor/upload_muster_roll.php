<?php
// api/contractor/upload_muster_roll.php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

function respondJSON($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['contractor', 'super_admin'])) {
    respondJSON(false, 'Unauthorized access.');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Establish contractor ID
$contractor_id = 0;
if ($role === 'super_admin' && isset($_POST['contractor_id'])) {
    $contractor_id = intval($_POST['contractor_id']);
} else {
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $contractor_id = $contractor['id'] ?? 0;
}

if (!$contractor_id) {
    respondJSON(false, 'Contractor profile context not found.');
}

$month_year = $_POST['month_year'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
    respondJSON(false, 'Invalid month/year format. Must be YYYY-MM.');
}

if (empty($_FILES['muster_file']) || $_FILES['muster_file']['error'] !== UPLOAD_ERR_OK) {
    respondJSON(false, 'Please upload a valid Muster Roll signed copy.');
}

$file = $_FILES['muster_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowed, true)) {
    respondJSON(false, 'Invalid file type. Allowed formats: PDF, JPEG, PNG.');
}

if ($file['size'] > 5 * 1024 * 1024) {
    respondJSON(false, 'File size exceeds limit of 5MB.');
}

$upload_dir = __DIR__ . '/../../uploads/muster_roll/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate secure filename
$filename = 'muster_roll_' . $contractor_id . '_' . str_replace('-', '_', $month_year) . '_' . uniqid() . '.' . $ext;
$relative_path = 'uploads/muster_roll/' . $filename;
$target_path = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    respondJSON(false, 'Failed to save uploaded file on server.');
}

// Ensure database table exists
ensureMusterRollSchema($conn);

// Insert or update record on duplicate key
$stmt = $conn->prepare("
    INSERT INTO muster_rolls (contractor_id, month_year, file_path, status, remarks, uploaded_at)
    VALUES (?, ?, ?, 'pending', NULL, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE 
        file_path = VALUES(file_path),
        status = 'pending',
        remarks = NULL,
        uploaded_at = CURRENT_TIMESTAMP
");

if (!$stmt) {
    respondJSON(false, 'Database preparation error: ' . $conn->error);
}

$stmt->bind_param('iss', $contractor_id, $month_year, $relative_path);
if ($stmt->execute()) {
    respondJSON(true, 'Signed Muster Roll has been uploaded successfully for verification.');
} else {
    respondJSON(false, 'Failed to log Muster Roll submission details in database: ' . $stmt->error);
}

$stmt->close();
?>
