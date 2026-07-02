<?php
/**
 * Block Worker API (Contractor Request)
 */
require_once 'api_helper.php';
require_once '../include/config.php';
require_once '../include/session.php';
require_once 'WorkflowEngine.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $input = getApiInput();
    $workman_id = (int)($input['workman_id'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    $application_id = $input['application_id'] ?? null;
    $contractor_id = $_SESSION['contractor_id'] ?? null;
    $role = $_SESSION['role'] ?? null;

    if (!$workman_id) apiError('Workman ID is required');
    if (!$reason) apiError('Reason is required');
    
    // Security check: Only Contractor can block, and only their own workmen
    if ($role === 'contractor') {
        if (!$contractor_id) apiError('Contractor context not found.');
        $check = db_single($conn, "SELECT id FROM workmen WHERE id = ? AND contractor_id = ?", 'ii', [$workman_id, $contractor_id]);
        if (!$check) apiError('Access Denied: You can only block your own workmen.');
    } else {
        apiError('Only Contractors are permitted to block workmen.');
    }

    // Insert block
    try {
        $stmt = $conn->prepare("
            INSERT INTO blocks (entity_type, entity_id, block_reason, blocked_by)
            VALUES ('worker', ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('isi', $workman_id, $reason, $_SESSION['user_id']);
            $stmt->execute();
        }
    } catch (Throwable $e) {}

    // Update workmen status and purge ACC card details
    $updateStmt = $conn->prepare("UPDATE workmen SET is_blocked = 1, blocked_source = 'contractor', acc_number = NULL, acc_card_number = NULL WHERE id = ?");
    $updateStmt->bind_param('i', $workman_id);
    if (!$updateStmt->execute()) {
        apiError('Failed to update worker status.');
    }

    if ($application_id) {
        try {
            WorkflowEngine::performAction($conn, $application_id, 'check_blocking', $_SESSION['role'], $_SESSION['user_id']);
        } catch (Throwable $e) {}
    }

    apiSuccess(['message' => 'Worker successfully blocked and ACC card has been deactivated.']);

} catch (Exception $e) {
    apiError($e->getMessage());
}
?>
