<?php
// api/welfare/action_muster_roll.php
session_start();
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json; charset=utf-8');

function respondJSON($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['welfare_user', 'welfare_admin', 'welfare', 'super_admin', 'admin'])) {
    respondJSON(false, 'Unauthorized access.');
}

// Read JSON input payload
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    respondJSON(false, 'Invalid payload context.');
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$remarks = isset($input['remarks']) ? trim($input['remarks']) : '';

if (!$id) {
    respondJSON(false, 'Missing Muster Roll submission ID.');
}

if (!in_array($action, ['verify', 'reject'], true)) {
    respondJSON(false, 'Invalid verification action specified.');
}

if ($action === 'reject' && empty($remarks)) {
    respondJSON(false, 'Please provide rejection remarks.');
}

$status = ($action === 'verify') ? 'verified' : 'rejected';
$verifier_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE muster_rolls 
    SET status = ?, 
        remarks = ?, 
        verified_by = ?, 
        verified_at = CURRENT_TIMESTAMP 
    WHERE id = ?
");

if (!$stmt) {
    respondJSON(false, 'Database preparation error: ' . $conn->error);
}

$stmt->bind_param('ssii', $status, $remarks, $verifier_id, $id);

if ($stmt->execute()) {
    $action_text = ($status === 'verified') ? 'verified and approved' : 'rejected';
    respondJSON(true, "Muster Roll has been successfully " . $action_text . ".");
} else {
    respondJSON(false, 'Failed to update Muster Roll status in database: ' . $stmt->error);
}

$stmt->close();
?>
