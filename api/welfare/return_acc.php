<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

function accReturnJson($success, $message, $data = null, $code = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function accReturnLog($message) {
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', '[ACC_RETURN] ' . date('c') . ' - ' . $message . "\n", FILE_APPEND);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    accReturnLog($error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    if (!headers_sent()) {
        accReturnJson(false, 'ACC return failed on the server. Please check api_errors.log.', null, 500);
    }
});

require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

checkAuth(['front_line_user', 'pass_user', 'welfare_user', 'welfare_admin', 'super_admin']);

function accReturnColumnExists($conn, $table, $column) {
    return db_count(
        $conn,
        "SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        'ss',
        [$table, $column]
    ) > 0;
}

function accReturnTryQuery($conn, $sql) {
    try {
        return (bool)$conn->query($sql);
    } catch (Throwable $e) {
        accReturnLog($e->getMessage() . ' | ' . $sql);
        return false;
    }
}

function accReturnEnsureSchema($conn) {
    accReturnTryQuery($conn, "CREATE TABLE IF NOT EXISTS acc_return_logs (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        acc_no VARCHAR(50) DEFAULT NULL,
        return_date DATE DEFAULT NULL,
        received_by INT DEFAULT NULL,
        condition_notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'workman_id' => "ALTER TABLE acc_return_logs ADD workman_id INT NOT NULL",
        'acc_no' => "ALTER TABLE acc_return_logs ADD acc_no VARCHAR(50) DEFAULT NULL",
        'return_date' => "ALTER TABLE acc_return_logs ADD return_date DATE DEFAULT NULL",
        'received_by' => "ALTER TABLE acc_return_logs ADD received_by INT DEFAULT NULL",
        'condition_notes' => "ALTER TABLE acc_return_logs ADD condition_notes TEXT DEFAULT NULL",
        'created_at' => "ALTER TABLE acc_return_logs ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($columns as $column => $sql) {
        if (!accReturnColumnExists($conn, 'acc_return_logs', $column)) {
            accReturnTryQuery($conn, $sql);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    accReturnJson(false, 'Invalid request method.', null, 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$accNo = trim((string)($data['acc_no'] ?? ''));
$workmanId = (int)($data['workman_id'] ?? 0);
$notes = trim((string)($data['notes'] ?? ''));
$receivedBy = (int)($_SESSION['user_id'] ?? 0);

if ($accNo === '' && !$workmanId) {
    accReturnJson(false, 'ACC card number or worker ID is required.', null, 400);
}

if ($workmanId) {
    $worker = db_single($conn, "SELECT id, name, acc_number, acc_card_number FROM workmen WHERE id = ?", 'i', [$workmanId]);
} else {
    $worker = db_single($conn, "SELECT id, name, acc_number, acc_card_number FROM workmen WHERE acc_number = ? OR acc_card_number = ? LIMIT 1", 'ss', [$accNo, $accNo]);
}

if (!$worker) {
    accReturnJson(false, 'Worker not found for this ACC card.', null, 404);
}

$workmanId = (int)$worker['id'];
$accNo = $accNo ?: (string)($worker['acc_number'] ?? $worker['acc_card_number'] ?? '');

accReturnEnsureSchema($conn);

$started = false;
try {
    $conn->begin_transaction();
    $started = true;

    $updated = db_execute(
        $conn,
        "UPDATE workmen SET status = 'acc_returned' WHERE id = ?",
        'i',
        [$workmanId]
    );
    if (!$updated) {
        throw new Exception('Unable to update worker return status.');
    }

    db_execute(
        $conn,
        "UPDATE gate_passes SET status = 'blocked', remarks = 'ACC card returned' WHERE workman_id = ? AND status IN ('active', 'approved')",
        'i',
        [$workmanId]
    );

    $nextId = 1;
    $maxRow = db_single($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM acc_return_logs");
    if ($maxRow && isset($maxRow['next_id'])) {
        $nextId = (int)$maxRow['next_id'];
    }

    db_execute(
        $conn,
        "INSERT INTO acc_return_logs (id, workman_id, acc_no, return_date, received_by, condition_notes)
         VALUES (?, ?, ?, CURDATE(), ?, ?)",
        'iisis',
        [$nextId, $workmanId, $accNo, $receivedBy, $notes]
    );

    $conn->commit();
} catch (Throwable $e) {
    if ($started) {
        @clms_db_rollback($conn);
    }
    accReturnJson(false, 'ACC return failed: ' . $e->getMessage(), null, 500);
}

accReturnJson(true, 'ACC card marked as returned successfully.');
