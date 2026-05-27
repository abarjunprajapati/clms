<?php
require_once '../include/config.php';
require_once 'helpers.php';
require_once 'WorkflowEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (basename($_SERVER['SCRIPT_FILENAME']) === 'workflow_action.php') {
    try {
        $input = getApiInput();
        $app_id = validateApplicationId($input);
        $action = trim(strtolower($input['action'] ?? ''));
        $remarks = trim($input['remarks'] ?? '');
        $additionalData = $input['additional_data'] ?? [];

        if (empty($action)) {
            apiError('action is required');
        }

        $userRole = $_SESSION['role'] ?? 'admin';
        $userId = intval($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

        $result = WorkflowEngine::performAction($conn, $app_id, $action, $userRole, $userId, $remarks, $additionalData);

        if ($result['success']) {
            sendResponse(true, [
                'application_id' => $app_id,
                'old_status' => $result['old_status'],
                'new_status' => $result['new_status']
            ], $result['message']);
        }

        apiError($result['message']);

    } catch (Throwable $e) {
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        apiError($e->getMessage());
    }
}

function workflowSetStatus(string $applicationId, string $targetStatus, string $remarks = '', string $userRole = 'admin', int $userId = 0): array {
    global $conn;
    return WorkflowEngine::setStatus($conn, $applicationId, $targetStatus, $userRole, $userId, $remarks);
}

function workflowUpdate($targetStatus, $remarks = '') {
    $input = getApiInput();
    $app_id = validateApplicationId($input);
    
    $userRole = $_SESSION['role'] ?? 'admin';
    $userId = intval($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
    
    $result = workflowSetStatus($app_id, $targetStatus, $remarks, $userRole, $userId);
    
    if ($result['success']) {
        apiSuccess($result, $result['message']);
    } else {
        apiError($result['message']);
    }
}

