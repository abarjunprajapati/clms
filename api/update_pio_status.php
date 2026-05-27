<?php
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';
require_once 'WorkflowEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $input = getApiInput();
    $application_id = validateApplicationId($input);
    $action = trim(strtolower($input['action'] ?? $input['status'] ?? ''));
    $remarks = trim($input['remarks'] ?? '');

    if (empty($action)) {
        apiError('action is required');
    }

    if ($action === 'approved') {
        $action = 'approve';
    } elseif ($action === 'rejected') {
        $action = 'reject';
    }

    $userRole = $_SESSION['role'] ?? 'admin';
    $userId = intval($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

    $result = WorkflowEngine::performAction($conn, $application_id, $action, $userRole, $userId, $remarks);

    if ($result['success']) {
        sendResponse(true, ['application_id' => $application_id, 'new_status' => $result['new_status']], $result['message']);
    }

    apiError($result['message']);
} catch (Throwable $e) {
    apiError($e->getMessage());
}
?>

