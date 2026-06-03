<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

function permanentPassJson($success, $message, $data = null, $code = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function permanentPassLog($message) {
    @file_put_contents(
        __DIR__ . '/../../logs/api_errors.log',
        '[COMPLETE_BIOMETRIC] ' . date('c') . ' - ' . $message . "\n",
        FILE_APPEND
    );
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    permanentPassLog($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    if (!headers_sent()) {
        permanentPassJson(false, 'Permanent pass issue failed on the server. Please check api_errors.log.', null, 500);
    }
});

require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';

require_role(['pass_issuer', 'pass_user', 'admin', 'welfare', 'welfare_user', 'welfare_admin']);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

@file_put_contents(__DIR__ . '/../../logs/biometric_debug.log', date('Y-m-d H:i:s') . ' - Input: ' . json_encode($input) . "\n", FILE_APPEND);

$workmanId = (int)($input['workman_id'] ?? $input['id'] ?? 0);
if ($workmanId <= 0) {
    permanentPassJson(false, 'Invalid Workman ID.', null, 400);
}

$workman = db_single($conn, "SELECT * FROM workmen WHERE id = ?", "i", [$workmanId]);
if (!$workman) {
    permanentPassJson(false, "Workman ID $workmanId not found.", null, 404);
}

$accNo = trim((string)($workman['acc_number'] ?? $workman['acc_card_number'] ?? ''));
if ($accNo === '') {
    permanentPassJson(false, 'ACC number is not generated for this worker.', null, 400);
}

if (($workman['status'] ?? '') === 'permanent_active') {
    permanentPassJson(true, 'Permanent pass is already active for this worker.');
}

$validFrom = date('Y-m-d');
$validTo = date('Y-m-d', strtotime('+1 year'));
$appId = (string)($workman['application_no'] ?? $workman['application_id'] ?? '');
$contractorId = (int)($workman['contractor_id'] ?? 0);
$qrData = base64_encode(json_encode([
    'type' => 'permanent_pass',
    'pass_no' => $accNo,
    'worker_id' => $workmanId,
    'worker_name' => $workman['name'] ?? '',
    'application_id' => $appId,
    'valid_till' => $validTo
]));

try {
    $updatedWorker = db_execute(
        $conn,
        "UPDATE workmen
         SET biometric_status = 'completed',
             status = 'permanent_active',
             valid_from = ?,
             valid_to = ?
         WHERE id = ?",
        "ssi",
        [$validFrom, $validTo, $workmanId]
    );

    if (!$updatedWorker) {
        permanentPassJson(false, 'Unable to activate worker permanent pass status.', null, 500);
    }

    db_execute(
        $conn,
        "UPDATE gate_passes
         SET pass_type = 'permanent',
             acc_card_number = ?,
             valid_from = ?,
             valid_to = ?,
             status = 'approved'
         WHERE workman_id = ?",
        "sssi",
        [$accNo, $validFrom, $validTo, $workmanId]
    );

    $nextPassId = 1;
    $maxPass = db_single($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM permanent_gate_passes");
    if ($maxPass && isset($maxPass['next_id'])) {
        $nextPassId = (int)$maxPass['next_id'];
    }

    $existingPass = db_single(
        $conn,
        "SELECT id FROM permanent_gate_passes WHERE pass_no = ? OR worker_id = ? LIMIT 1",
        "si",
        [$accNo, $workmanId]
    );

    if ($existingPass && !empty($existingPass['id'])) {
        $savedPass = db_execute(
            $conn,
            "UPDATE permanent_gate_passes
             SET pass_no = ?,
                 worker_id = ?,
                 application_id = ?,
                 contractor_id = ?,
                 valid_from = ?,
                 valid_till = ?,
                 qr_code = ?,
                 status = 'active'
             WHERE id = ?",
            "sisisssi",
            [$accNo, $workmanId, $appId, $contractorId, $validFrom, $validTo, $qrData, (int)$existingPass['id']]
        );
    } else {
        $savedPass = db_execute(
            $conn,
            "INSERT INTO permanent_gate_passes
             (id, pass_no, worker_id, application_id, contractor_id, valid_from, valid_till, qr_code, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            "isisisss",
            [$nextPassId, $accNo, $workmanId, $appId, $contractorId, $validFrom, $validTo, $qrData]
        );
    }

    if (!$savedPass) {
        permanentPassLog("Optional permanent_gate_passes save failed for worker_id=$workmanId, acc=$accNo. Worker status was still activated.");
    }
} catch (Throwable $e) {
    permanentPassLog($e->getMessage());
    permanentPassJson(false, 'Unable to issue permanent pass: ' . $e->getMessage(), null, 500);
}

try {
    require_once '../../include/NotificationEngine.php';
    $contractor = db_single($conn, "SELECT user_id FROM contractors WHERE id = ?", "i", [$contractorId]);
    if ($contractor && !empty($contractor['user_id'])) {
        NotificationEngine::trigger($conn, $contractor['user_id'], 'Permanent Pass Issued', 'Permanent ACC pass issued for ' . ($workman['name'] ?? 'worker') . '.', 'success');
    }
} catch (Throwable $e) {
    permanentPassLog('Notification failed: ' . $e->getMessage());
}

permanentPassJson(true, 'Permanent pass issued and ACC activated successfully.');
