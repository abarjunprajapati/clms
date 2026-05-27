<?php
require_once 'helpers.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $data = getApiInput();
    $application_id = validateApplicationId($data);
    $action = trim(strtolower($data['action'] ?? ''));
    $remarks = trim($data['remarks'] ?? '');

    if (empty($action)) {
        apiError('action is required');
    }

    $userRole = $_SESSION['role'] ?? 'admin';
    $userId = intval($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

    $result = WorkflowEngine::performAction($conn, $application_id, $action, $userRole, $userId, $remarks);

    if ($result['success']) {
        apiSuccess([
            'new_status' => $result['new_status'],
            'old_status' => $result['old_status']
        ], $result['message']);
    }

    apiError($result['message']);
} catch (Throwable $ex) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    apiError($ex->getMessage());
}

