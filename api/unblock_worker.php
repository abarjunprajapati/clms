<?php
/**
 * Unblock Worker API
 */
require_once 'api_helper.php';
require_once '../include/config.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $workman_id = (int)($input['workman_id']);

    $stmt = $conn->prepare("
        UPDATE blocks SET status = 'inactive', unblocked_at = NOW() WHERE entity_type = 'worker' AND entity_id = ? AND status = 'active'
    ");
    $stmt->bind_param('i', $workman_id);
    $stmt->execute();

    $conn->query("UPDATE workmen SET status = 'active' WHERE id = $workman_id");

    apiSuccess(['message' => 'Worker unblocked.']);

} catch (Exception $e) {
    apiError($e->getMessage());
}
?>


