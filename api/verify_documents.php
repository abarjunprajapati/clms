<?php
/**
 * Verify Documents API
 * Updates workflow status to gatepass_verified after document verification
 */
require_once 'helpers.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $data = getApiInput();
    $application_id = validateApplicationId($data);
    
    $userRole = $_SESSION['role'] ?? 'admin';
    $userId = intval($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

    $result = WorkflowEngine::performAction($conn, $application_id, 'verify_documents', $userRole, $userId, 'Documents verified');

    if ($result['success']) {
        apiSuccess([
            'new_status' => $result['new_status'],
            'old_status' => $result['old_status']
        ], $result['message']);
    }

    apiError($result['message']);
} catch (Throwable $ex) {
    apiError($ex->getMessage());
}
?>
