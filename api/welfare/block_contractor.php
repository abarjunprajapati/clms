<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

function contractorBlockJson($success, $message, $data = null, $code = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function contractorBlockLog($message) {
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', '[BLOCK_CONTRACTOR] ' . date('c') . ' - ' . $message . "\n", FILE_APPEND);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    contractorBlockLog($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    if (!headers_sent()) {
        contractorBlockJson(false, 'Contractor action failed on the server. Please check api_errors.log.', null, 500);
    }
});

require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/ContractorBlockingService.php';

checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    contractorBlockJson(false, 'Invalid request method.', null, 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$contractorId = (int)($data['contractor_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? 'block')));
$reason = trim((string)($data['reason'] ?? 'Admin Manual Block'));
$remarks = trim((string)($data['remarks'] ?? ''));
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($contractorId <= 0) {
    contractorBlockJson(false, 'Contractor ID required.', null, 400);
}
if (!in_array($action, ['block', 'unblock'], true)) {
    contractorBlockJson(false, 'Invalid action.', null, 400);
}

try {
    if ($action === 'block') {
        $result = ContractorBlockingService::blockContractor($conn, $contractorId, $reason, $remarks, $userId);
    } else {
        $result = ContractorBlockingService::unblockContractor($conn, $contractorId, $userId);
    }
} catch (Throwable $e) {
    contractorBlockLog($e->getMessage());
    contractorBlockJson(false, 'Contractor action failed: ' . $e->getMessage(), null, 500);
}

contractorBlockJson((bool)($result['success'] ?? false), $result['message'] ?? 'Action completed.', $result);
