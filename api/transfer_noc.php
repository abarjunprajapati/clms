<?php
/**
 * Worker Transfer NOC API
 */
require_once 'api_helper.php';
require_once '../include/config.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $workman_id = (int)($input['workman_id']);
    $to_contractor_id = (int)($input['to_contractor_id']);
    $reason = trim($input['reason']);

    if (!$workman_id || !$to_contractor_id || !$reason) apiError('workman_id, to_contractor_id, reason required');

    $stmt = $conn->prepare("
        INSERT INTO worker_transfers (workman_id, to_contractor_id, noc_status, rejection_reason)
        VALUES (?, ?, 'pending', NULL)
    ");
    $stmt->bind_param('ii', $workman_id, $to_contractor_id);
    $stmt->execute();

    apiSuccess(['message' => 'NOC request submitted for welfare approval.']);

} catch (Exception $e) {
    apiError($e->getMessage());
}
?>


