<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';
require_once '../../include/NotificationEngine.php';

require_role(['pass_issuer', 'admin', 'welfare', 'welfare_user', 'welfare_admin']);

$input = json_decode(file_get_contents('php://input'), true);
@file_put_contents(__DIR__ . '/../../logs/biometric_debug.log', date('Y-m-d H:i:s') . ' - Input: ' . json_encode($input) . "\n", FILE_APPEND);
$workman_id = $input['workman_id'] ?? $input['id'] ?? 0;

if (!$workman_id) {
    json_response(false, null, 'Invalid Workman ID (received: ' . json_encode($input) . ')');
}

$workman = db_single($conn, "SELECT * FROM workmen WHERE id = ?", "i", [$workman_id]);
if (!$workman) {
    json_response(false, null, "Workman ID $workman_id not found");
}
if ($workman['status'] !== 'acc_generated') {
    json_response(false, null, "Workman status is '" . $workman['status'] . "', but it must be 'acc_generated'");
}

db_execute($conn, "UPDATE workmen SET biometric_status = 'completed', status = 'permanent_active' WHERE id = ?", "i", [$workman_id]);

// Notify contractor
$contractor = db_single($conn, "SELECT user_id FROM contractors WHERE id = ?", "i", [$workman['contractor_id']]);
if ($contractor && $contractor['user_id']) {
    NotificationEngine::trigger($conn, $contractor['user_id'], "Biometric Update", "Biometric enrollment completed for " . $workman['name'] . ". Permanent pass is now active.", 'success');
}

json_response(true, null, 'Biometric enrollment completed and pass activated');

