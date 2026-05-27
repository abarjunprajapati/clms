<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../api/WorkflowEngine.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$appId = $data['application_id'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'admin';

if (!$appId) {
    echo json_encode(['success' => false, 'message' => 'Application ID required']);
    exit;
}

$result = WorkflowEngine::performAction($conn, $appId, 'generate_acc', $userRole, $userId);

echo json_encode($result);
?>

