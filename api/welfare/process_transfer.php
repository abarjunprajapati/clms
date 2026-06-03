<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

function transferJson($success, $message, $data = null, $code = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function transferLog($message) {
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', '[NOC_TRANSFER] ' . date('c') . ' - ' . $message . "\n", FILE_APPEND);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    transferLog($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    if (!headers_sent()) {
        transferJson(false, 'Transfer failed on the server. Please check api_errors.log.', null, 500);
    }
});

require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

checkAuth(['pass_user', 'welfare_user', 'welfare_admin', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    transferJson(false, 'Invalid request method.', null, 405);
}

function transferColumnExists($conn, $table, $column) {
    return db_count(
        $conn,
        "SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        'ss',
        [$table, $column]
    ) > 0;
}

function transferTryQuery($conn, $sql) {
    try {
        return (bool)$conn->query($sql);
    } catch (Throwable $e) {
        transferLog($e->getMessage() . ' | ' . $sql);
        return false;
    }
}

function transferEnsureSchema($conn) {
    transferTryQuery($conn, "CREATE TABLE IF NOT EXISTS worker_transfer_logs (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        from_contractor_id INT NOT NULL,
        to_contractor_id INT DEFAULT NULL,
        noc_id INT DEFAULT NULL,
        noc_reference VARCHAR(100) DEFAULT NULL,
        transfer_type VARCHAR(20) DEFAULT 'noc',
        status VARCHAR(20) DEFAULT 'completed',
        approved_by INT DEFAULT NULL,
        remarks TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'workman_id' => "ALTER TABLE worker_transfer_logs ADD workman_id INT NOT NULL",
        'from_contractor_id' => "ALTER TABLE worker_transfer_logs ADD from_contractor_id INT NOT NULL",
        'to_contractor_id' => "ALTER TABLE worker_transfer_logs ADD to_contractor_id INT DEFAULT NULL",
        'noc_id' => "ALTER TABLE worker_transfer_logs ADD noc_id INT DEFAULT NULL",
        'noc_reference' => "ALTER TABLE worker_transfer_logs ADD noc_reference VARCHAR(100) DEFAULT NULL",
        'transfer_type' => "ALTER TABLE worker_transfer_logs ADD transfer_type VARCHAR(20) DEFAULT 'noc'",
        'status' => "ALTER TABLE worker_transfer_logs ADD status VARCHAR(20) DEFAULT 'completed'",
        'approved_by' => "ALTER TABLE worker_transfer_logs ADD approved_by INT DEFAULT NULL",
        'remarks' => "ALTER TABLE worker_transfer_logs ADD remarks TEXT DEFAULT NULL",
        'created_at' => "ALTER TABLE worker_transfer_logs ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($columns as $column => $sql) {
        if (!transferColumnExists($conn, 'worker_transfer_logs', $column)) {
            transferTryQuery($conn, $sql);
        }
    }
}

$processedBy = (int)($_SESSION['user_id'] ?? 0);
$workerId = (int)($_POST['worker_id'] ?? $_POST['workman_id'] ?? 0);
$fromContractorId = (int)($_POST['from_contractor_id'] ?? 0);
$toContractorId = (int)($_POST['to_contractor_id'] ?? 0);
$nocReference = trim((string)($_POST['noc_reference'] ?? ''));
$remarks = trim((string)($_POST['remarks'] ?? ''));

if (!$processedBy) {
    transferJson(false, 'Unauthorized session.', null, 401);
}
if (!$workerId || !$toContractorId || $nocReference === '') {
    transferJson(false, 'Please select worker, target contractor, and NOC reference.', null, 400);
}
if ($fromContractorId && $fromContractorId === $toContractorId) {
    transferJson(false, 'Source and destination contractors cannot be the same.', null, 400);
}

$worker = db_single($conn, "SELECT id, name, contractor_id, status FROM workmen WHERE id = ?", 'i', [$workerId]);
if (!$worker) {
    transferJson(false, 'Selected worker was not found.', null, 404);
}

$actualFromContractorId = (int)($worker['contractor_id'] ?? 0);
if (!$fromContractorId) {
    $fromContractorId = $actualFromContractorId;
}
if ($actualFromContractorId && $fromContractorId !== $actualFromContractorId) {
    transferJson(false, 'Current contractor changed. Please refresh and try again.', null, 409);
}
if ($actualFromContractorId === $toContractorId) {
    transferJson(false, 'Worker is already assigned to the selected contractor.', null, 400);
}

$toContractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE id = ?", 'i', [$toContractorId]);
if (!$toContractor) {
    transferJson(false, 'Target contractor was not found.', null, 404);
}

transferEnsureSchema($conn);

$started = false;
try {
    $conn->begin_transaction();
    $started = true;

    $workerUpdated = db_execute(
        $conn,
        "UPDATE workmen SET contractor_id = ?, status = 'active', updated_at = NOW() WHERE id = ?",
        'ii',
        [$toContractorId, $workerId]
    );
    if (!$workerUpdated) {
        throw new Exception('Unable to update worker contractor.');
    }

    db_execute(
        $conn,
        "UPDATE gate_passes SET status = 'blocked', remarks = 'Blocked due to NOC transfer' WHERE workman_id = ? AND status IN ('active', 'approved')",
        'i',
        [$workerId]
    );

    $nextId = 1;
    $maxRow = db_single($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM worker_transfer_logs");
    if ($maxRow && isset($maxRow['next_id'])) {
        $nextId = (int)$maxRow['next_id'];
    }

    $logSaved = db_execute(
        $conn,
        "INSERT INTO worker_transfer_logs
         (id, workman_id, from_contractor_id, to_contractor_id, noc_reference, transfer_type, status, approved_by, remarks, created_at)
         VALUES (?, ?, ?, ?, ?, 'noc', 'completed', ?, ?, NOW())",
        'iiiisis',
        [$nextId, $workerId, $fromContractorId, $toContractorId, $nocReference, $processedBy, $remarks]
    );
    if (!$logSaved) {
        transferLog('Transfer log insert failed for worker_id=' . $workerId . '. Worker contractor was still updated.');
    }

    $conn->commit();
} catch (Throwable $e) {
    if ($started) {
        @mysqli_rollback($conn);
    }
    transferJson(false, 'Transfer failed: ' . $e->getMessage(), null, 500);
}

transferJson(true, 'Worker transferred successfully.');
