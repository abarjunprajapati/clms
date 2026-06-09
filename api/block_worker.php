<?php
/**
 * Block Worker API (Contractor/Welfare request)
 */
require_once 'api_helper.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $workman_id = (int)($input['workman_id']);
    $reason = trim($input['reason']);
    $application_id = $input['application_id'] ?? null;

    if (!$workman_id) apiError('workman_id required');
    if (!$reason) apiError('reason required');

    // Insert block
    $stmt = $conn->prepare("
        INSERT INTO blocks (entity_type, entity_id, block_reason, blocked_by)
        VALUES ('worker', ?, ?, ?)
    ");
    $stmt->bind_param('isi', $workman_id, $reason, $_SESSION['user_id']);
    $stmt->execute();

    // Update workmen status
    $conn->query("UPDATE workmen SET status = 'blocked' WHERE id = $workman_id");

    if ($application_id) {
        WorkflowEngine::performAction($conn, $application_id, 'check_blocking', $_SESSION['role'], $_SESSION['user_id']);
    }

    apiSuccess(['message' => 'Worker blocked successfully.']);

} catch (Exception $e) {
    apiError($e->getMessage());
}
?>


