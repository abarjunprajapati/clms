<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['front_line_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../api/WorkflowEngine.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$accNo = $data['acc_no'] ?? '';
$appId = $data['application_id'] ?? ''; // Optional, if known
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'admin';

if (!$accNo) {
    echo json_encode(['success' => false, 'message' => 'ACC Card Number required']);
    exit;
}

// If appId is not provided, find it from workmen table
if (!$appId) {
    $res = $conn->query("SELECT application_no FROM workmen WHERE acc_number = '" . $conn->real_escape_string($accNo) . "' LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $appId = $row['application_no'];
    }
}

if (!$appId) {
    echo json_encode(['success' => false, 'message' => 'Could not find application for this ACC card']);
    exit;
}

$result = WorkflowEngine::performAction($conn, $appId, 'return_acc', $userRole, $userId, "ACC Card $accNo returned", ['acc_no' => $accNo]);

echo json_encode($result);
?>

