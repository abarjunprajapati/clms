<?php
require_once __DIR__ . '/../json_error_handler.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../workflow_helpers.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../WorkflowEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    workflow_ensure_tables($conn);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON body');
    }

    $application_id = validateApplicationId($data);
    $status = trim(strtolower($data['status'] ?? ''));
    $remarks = trim($data['remarks'] ?? '');

    if (!in_array($status, ['approved', 'rejected'], true)) {
        throw new Exception('Invalid status');
    }

    $action = $status === 'approved' ? 'approve' : 'reject';
    $userRole = $_SESSION['role'] ?? 'welfare';
    $userId = intval($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

    $result = WorkflowEngine::performAction($conn, $application_id, $action, $userRole, $userId, $remarks);
    if (!$result['success']) {
        apiError($result['message']);
    }

    $legacy_status = $status === 'approved' ? 'forwarded' : 'rejected';
    $legacy = $conn->prepare("UPDATE annexure2a SET status = ?, remarks = ?, updated_at = NOW() WHERE ref_id = ?");
    if ($legacy) {
        $legacy->bind_param('sss', $legacy_status, $remarks, $application_id);
        $legacy->execute();
        $legacy->close();
    }

    jsonErrorFlush();
    sendResponse(true, [
        'application_id' => $application_id,
        'old_status' => $result['old_status'],
        'new_status' => $result['new_status']
    ], $result['message']);
} catch (Throwable $e) {
    apiError($e->getMessage());
}
?>

