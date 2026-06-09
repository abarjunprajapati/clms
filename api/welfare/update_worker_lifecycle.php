<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

function workerBlockJson($success, $message, $data = null, $code = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'error' => $success ? null : $message, 'data' => $data]);
    exit;
}

function workerBlockLog($message) {
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', '[WORKER_BLOCK] ' . date('c') . ' - ' . $message . "\n", FILE_APPEND);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    workerBlockLog($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    if (!headers_sent()) workerBlockJson(false, 'Worker block action failed on the server. Please check api_errors.log.', null, 500);
});

require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);

function wbColumnExists($conn, $table, $column) {
    return db_count($conn, "SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", 'ss', [$table, $column]) > 0;
}

function wbTryQuery($conn, $sql) {
    try {
        return (bool)$conn->query($sql);
    } catch (Throwable $e) {
        workerBlockLog($e->getMessage() . ' | ' . $sql);
        return false;
    }
}

function wbEnsureCoreSchema($conn) {
    if (!wbColumnExists($conn, 'workmen', 'is_blocked')) {
        wbTryQuery($conn, "ALTER TABLE workmen ADD is_blocked TINYINT(1) DEFAULT 0");
    }
    if (!wbColumnExists($conn, 'workmen', 'blocked_source')) {
        wbTryQuery($conn, "ALTER TABLE workmen ADD blocked_source VARCHAR(30) DEFAULT NULL");
    }
    wbTryQuery($conn, "CREATE TABLE IF NOT EXISTS worker_blocks (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        workman_id INT DEFAULT NULL,
        blocked_by INT DEFAULT NULL,
        reason TEXT DEFAULT NULL,
        block_type VARCHAR(20) DEFAULT 'permanent',
        status VARCHAR(20) DEFAULT 'active',
        blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    wbTryQuery($conn, "CREATE TABLE IF NOT EXISTS worker_block_history (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        action VARCHAR(30) NOT NULL,
        reason TEXT DEFAULT NULL,
        action_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') workerBlockJson(false, 'Invalid request method.', null, 405);

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = [];

$workmanId = (int)($data['workman_id'] ?? $data['worker_id'] ?? 0);
$action = strtolower(trim((string)($data['action'] ?? '')));
$reason = trim((string)($data['reason'] ?? ''));
$actionBy = (int)($_SESSION['user_id'] ?? 0);

if (!$workmanId || !in_array($action, ['block', 'unblock'], true)) workerBlockJson(false, 'Invalid parameters.', null, 400);
if ($action === 'block' && $reason === '') workerBlockJson(false, 'Reason is required for blocking.', null, 400);

$worker = db_single($conn, "SELECT id, name FROM workmen WHERE id = ?", 'i', [$workmanId]);
if (!$worker) workerBlockJson(false, 'Worker not found.', null, 404);

wbEnsureCoreSchema($conn);

$blockedFlag = $action === 'block' ? 1 : 0;
$sourceSql = $action === 'block' ? "'manual'" : "NULL";
$coreSql = "UPDATE workmen SET is_blocked = $blockedFlag, blocked_source = $sourceSql WHERE id = $workmanId";

if (!wbTryQuery($conn, $coreSql)) {
    $fallbackSql = "UPDATE workmen SET is_blocked = $blockedFlag WHERE id = $workmanId";
    if (!wbTryQuery($conn, $fallbackSql)) {
        workerBlockJson(false, 'Unable to update worker block flag. DB error: ' . clms_db_error($conn), null, 500);
    }
}

if ($action === 'block') {
    wbTryQuery($conn, "UPDATE worker_blocks SET status = 'released' WHERE workman_id = $workmanId AND status = 'active'");
    $reasonSql = "'" . clms_db_real_escape_string($conn, $reason) . "'";
    $nextBlockId = db_count($conn, "SELECT COALESCE(MAX(id), 0) + 1 FROM worker_blocks");
    if ($nextBlockId < 1) $nextBlockId = 1;
    wbTryQuery($conn, "INSERT INTO worker_blocks (id, workman_id, blocked_by, reason, block_type, status, blocked_at)
        VALUES ($nextBlockId, $workmanId, $actionBy, $reasonSql, 'permanent', 'active', NOW())");
    wbTryQuery($conn, "UPDATE gate_passes SET status = 'blocked', remarks = 'Worker blocked by welfare' WHERE workman_id = $workmanId AND status IN ('active', 'approved')");
    $historyAction = 'permanent_block';
    $message = 'Worker blocked successfully.';
} else {
    wbTryQuery($conn, "UPDATE worker_blocks SET status = 'released' WHERE workman_id = $workmanId AND status = 'active'");
    $historyAction = 'unblock';
    $reason = $reason ?: 'Worker unblocked by welfare.';
    $message = 'Worker unblocked successfully.';
}

$reasonSql = "'" . clms_db_real_escape_string($conn, $reason) . "'";
$historyActionSql = "'" . clms_db_real_escape_string($conn, $historyAction) . "'";
$nextHistoryId = db_count($conn, "SELECT COALESCE(MAX(id), 0) + 1 FROM worker_block_history");
if ($nextHistoryId < 1) $nextHistoryId = 1;
wbTryQuery($conn, "INSERT INTO worker_block_history (id, workman_id, action, reason, action_by, created_at)
    VALUES ($nextHistoryId, $workmanId, $historyActionSql, $reasonSql, $actionBy, NOW())");

$detail = clms_db_real_escape_string($conn, "Worker $workmanId $action. Reason: $reason");
wbTryQuery($conn, "INSERT INTO audit_logs (user_id, action, module, details, ip_address)
    VALUES ($actionBy, 'worker_$action', 'worker_blocks', '$detail', '" . clms_db_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '') . "')");

workerBlockJson(true, $message);
