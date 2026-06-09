<?php
ob_start();
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../api/WorkflowEngine.php';

header('Content-Type: application/json');

function safetyResultsJson($payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    error_log('[safety_save_results] ' . $e->getMessage());
    safetyResultsJson(['success' => false, 'message' => 'Result save failed: ' . $e->getMessage()], 500);
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[safety_save_results fatal] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        safetyResultsJson(['success' => false, 'message' => 'Result save failed on server. Please retry after refresh.'], 500);
    }
});

$session_id = intval($_POST['session_id'] ?? 0);
$results = $_POST['result'] ?? [];
$theory_scores = $_POST['theory_score'] ?? [];
$practical_scores = $_POST['practical_score'] ?? [];
$valid_tills = $_POST['valid_till'] ?? [];
$remarks_data = $_POST['result_remarks'] ?? [];

if (!$session_id || empty($results)) {
    safetyResultsJson(["success" => false, "message" => "No results to save"]);
}

// Check session status
$session = db_single($conn, "SELECT session_status FROM training_schedule WHERE id=?", 'i', [$session_id]);
if (!$session || $session['session_status'] == 'completed') {
    safetyResultsJson(["success" => false, "message" => "Session locked"]);
}

function safetyResultsTableExists($conn, $table) {
    $table = clms_db_real_escape_string($conn, $table);
    $res = clms_db_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && clms_db_num_rows($res) > 0;
}

function safetyResultsColumnExists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = clms_db_real_escape_string($conn, $column);
    $res = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && clms_db_num_rows($res) > 0;
}

function safetyResultsEnsureColumn($conn, $table, $column, $definition) {
    if (!safetyResultsTableExists($conn, $table) || safetyResultsColumnExists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    clms_db_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function safetyResultsAvailableColumns($conn, $table) {
    $safeTable = str_replace('`', '``', $table);
    $res = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable`");
    $cols = [];
    if ($res) {
        while ($row = clms_db_fetch_assoc($res)) {
            $cols[$row['Field']] = true;
        }
    }
    return $cols;
}

function safetyResultsUpdateExistingColumns($conn, $table, array $values, $whereSql, $whereTypes = '', array $whereParams = []) {
    $cols = safetyResultsAvailableColumns($conn, $table);
    $set = [];
    $types = '';
    $params = [];

    foreach ($values as $column => $spec) {
        if (!isset($cols[$column])) {
            continue;
        }
        if (is_array($spec) && array_key_exists('raw', $spec)) {
            $set[] = "`$column` = " . $spec['raw'];
            continue;
        }
        $type = is_array($spec) ? ($spec['type'] ?? 's') : 's';
        $value = is_array($spec) ? ($spec['value'] ?? null) : $spec;
        $set[] = "`$column` = ?";
        $types .= $type;
        $params[] = $value;
    }

    if (!$set) {
        return false;
    }

    return db_execute(
        $conn,
        "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $whereSql",
        $types . $whereTypes,
        array_merge($params, $whereParams)
    );
}

function safetyResultsInsertExistingColumns($conn, $table, array $values) {
    $cols = safetyResultsAvailableColumns($conn, $table);
    $insertCols = [];
    $placeholders = [];
    $types = '';
    $params = [];

    foreach ($values as $column => $spec) {
        if (!isset($cols[$column])) {
            continue;
        }
        $insertCols[] = "`$column`";
        if (is_array($spec) && array_key_exists('raw', $spec)) {
            $placeholders[] = $spec['raw'];
            continue;
        }
        $type = is_array($spec) ? ($spec['type'] ?? 's') : 's';
        $value = is_array($spec) ? ($spec['value'] ?? null) : $spec;
        $placeholders[] = '?';
        $types .= $type;
        $params[] = $value;
    }

    if (!$insertCols) {
        return false;
    }

    return db_execute(
        $conn,
        "INSERT INTO `$table` (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")",
        $types,
        $params
    );
}

function safetyResultsSetting($conn, $key, $default) {
    if (!safetyResultsTableExists($conn, 'system_settings')) {
        return $default;
    }

    $row = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1", 's', [$key]);
    return isset($row['setting_value']) && $row['setting_value'] !== '' ? $row['setting_value'] : $default;
}

function safetyResultsSystemSettingIdIsAutoIncrement($conn) {
    if (!safetyResultsTableExists($conn, 'system_settings')) {
        return true;
    }

    $res = clms_db_query($conn, "SHOW COLUMNS FROM `system_settings` LIKE 'id'");
    $row = $res ? clms_db_fetch_assoc($res) : null;
    return !$row || stripos($row['Extra'] ?? '', 'auto_increment') !== false;
}

function safetyResultsNextSystemSettingId($conn) {
    $res = clms_db_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `system_settings`");
    $row = $res ? clms_db_fetch_assoc($res) : null;
    return (int)($row['next_id'] ?? 1);
}

function safetyResultsEnsureSetting($conn, $key, $value, $group, $description) {
    if (!safetyResultsTableExists($conn, 'system_settings')) {
        return;
    }

    $exists = db_count($conn, "SELECT COUNT(*) c FROM system_settings WHERE setting_key = ?", 's', [$key]);
    if ($exists > 0) {
        return;
    }

    if (safetyResultsSystemSettingIdIsAutoIncrement($conn)) {
        db_execute(
            $conn,
            "INSERT INTO system_settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, ?, ?)",
            'ssss',
            [$key, $value, $group, $description]
        );
        return;
    }

    db_execute(
        $conn,
        "INSERT INTO system_settings (id, setting_key, setting_value, setting_group, description) VALUES (?, ?, ?, ?, ?)",
        'issss',
        [safetyResultsNextSystemSettingId($conn), $key, $value, $group, $description]
    );
}

function safetyResultsEnsureSchema($conn) {
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS training_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        training_session_id VARCHAR(50) DEFAULT NULL,
        application_id VARCHAR(50) DEFAULT NULL,
        workman_id INT DEFAULT NULL,
        worker_name VARCHAR(100) DEFAULT NULL,
        trade VARCHAR(100) DEFAULT NULL,
        attendance_status VARCHAR(20) DEFAULT 'absent',
        theory_score INT DEFAULT 0,
        practical_score INT DEFAULT 0,
        total_score INT DEFAULT 0,
        pass_mark INT DEFAULT 60,
        result VARCHAR(20) DEFAULT 'pending',
        valid_till DATE NULL,
        remarks TEXT NULL,
        recorded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_session (training_session_id),
        INDEX idx_workman (workman_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'valid_till' => 'DATE NULL',
        'remarks' => 'TEXT NULL',
        'theory_score' => 'INT DEFAULT 0',
        'practical_score' => 'INT DEFAULT 0',
        'total_score' => 'INT DEFAULT 0',
        'pass_mark' => 'INT DEFAULT 60',
    ] as $column => $definition) {
        safetyResultsEnsureColumn($conn, 'training_session_workers', $column, $definition);
    }

    foreach ([
        'training_status' => "VARCHAR(50) DEFAULT 'pending'",
        'eligibility_status' => "VARCHAR(50) DEFAULT 'NOT ELIGIBLE'",
        'training_valid_till' => 'DATE NULL',
        'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL',
    ] as $column => $definition) {
        safetyResultsEnsureColumn($conn, 'workmen', $column, $definition);
    }

    foreach ([
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'conduct_remarks' => 'TEXT NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL',
    ] as $column => $definition) {
        safetyResultsEnsureColumn($conn, 'training_requests', $column, $definition);
    }

    foreach ([
        'training_session_id' => 'VARCHAR(50) DEFAULT NULL',
        'application_id' => 'VARCHAR(50) DEFAULT NULL',
        'workman_id' => 'INT DEFAULT NULL',
        'worker_name' => 'VARCHAR(100) DEFAULT NULL',
        'trade' => 'VARCHAR(100) DEFAULT NULL',
        'attendance_status' => "VARCHAR(20) DEFAULT 'absent'",
        'theory_score' => 'INT DEFAULT 0',
        'practical_score' => 'INT DEFAULT 0',
        'total_score' => 'INT DEFAULT 0',
        'pass_mark' => 'INT DEFAULT 60',
        'result' => "VARCHAR(20) DEFAULT 'pending'",
        'valid_till' => 'DATE NULL',
        'remarks' => 'TEXT NULL',
        'recorded_by' => 'INT NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL',
    ] as $column => $definition) {
        safetyResultsEnsureColumn($conn, 'training_results', $column, $definition);
    }

    safetyResultsEnsureSetting($conn, 'training_validity_days', '365', 'training', 'Safety training validity in days');
}

safetyResultsEnsureSchema($conn);
$passMark = max(1, (int)safetyResultsSetting($conn, 'training_pass_mark', 60));
$validityDays = max(1, (int)safetyResultsSetting($conn, 'training_validity_days', 365));

clms_db_begin_transaction($conn);
try {
    foreach ($results as $workman_id => $res) {
        $workman_id = (int)$workman_id;
        $theory = max(0, (int)($theory_scores[$workman_id] ?? 0));
        $practical = max(0, (int)($practical_scores[$workman_id] ?? 0));
        $total = $theory + $practical;
        $rem = $remarks_data[$workman_id] ?? '';
        
        // Fetch attendance status for this worker in this session
        $workerNameExpr = safetyResultsColumnExists($conn, 'workmen', 'name') ? 'w.name' : "CONCAT('Worker #', w.id)";
        $workerTradeExpr = safetyResultsColumnExists($conn, 'workmen', 'trade') ? 'w.trade' : "''";
        $workerAppExpr = safetyResultsColumnExists($conn, 'workmen', 'application_no') ? 'w.application_no' : "''";
        $mapping = db_single(
            $conn,
            "SELECT tsw.attendance_status, tsw.training_request_id, $workerNameExpr AS name, $workerTradeExpr AS trade, $workerAppExpr AS application_no
             FROM training_session_workers tsw
             JOIN training_requests tr ON tr.id = tsw.training_request_id
             JOIN workmen w ON w.id = tsw.workman_id
             WHERE tsw.session_id=? AND tsw.workman_id=? AND tr.status = 'contractor_confirmed'",
            'ii',
            [$session_id, $workman_id]
        );
        
        if ($mapping && $mapping['attendance_status'] == 'present') {
            if ($total > 100) {
                throw new Exception("Total marks cannot exceed 100 for " . ($mapping['name'] ?: "worker #$workman_id"));
            }

            $res = ($total >= $passMark) ? 'pass' : 'fail';
            $valid = ($res === 'pass') ? ($valid_tills[$workman_id] ?? date('Y-m-d', strtotime('+' . $validityDays . ' days'))) : null;

            // Update mapping table
            db_execute(
                $conn,
                "UPDATE training_session_workers
                 SET result=?, valid_till=?, remarks=?, theory_score=?, practical_score=?, total_score=?, pass_mark=?
                 WHERE session_id=? AND workman_id=?",
                'sssiiiiii',
                [$res, $valid, $rem, $theory, $practical, $total, $passMark, $session_id, $workman_id]
            );
            
            // Update workmen status if result is pass or fail
            $final_status = ($res == 'pass') ? 'training_passed' : 'training_failed';
            $safetyStatus = ($res == 'pass') ? 'TRAINING_PASSED' : 'TRAINING_FAILED';
            $eligibility = ($res == 'pass') ? 'ELIGIBLE' : 'NOT ELIGIBLE';
            safetyResultsUpdateExistingColumns(
                $conn,
                'workmen',
                [
                    'training_status' => ['value' => $final_status, 'type' => 's'],
                    'eligibility_status' => ['value' => $eligibility, 'type' => 's'],
                    'training_valid_till' => ['value' => $valid, 'type' => 's'],
                    'safety_training_status' => ['value' => $safetyStatus, 'type' => 's'],
                    'updated_at' => ['raw' => 'NOW()'],
                ],
                'id = ?',
                'i',
                [$workman_id]
            );
            
            // Sync with training_requests status
            $req_status = ($res == 'pass') ? 'passed' : 'failed';
            db_execute($conn, "UPDATE training_requests SET status = ?, conduct_remarks = ?, updated_at = NOW() WHERE id = ? AND status = 'contractor_confirmed'", 'ssi', [$req_status, $rem, (int)$mapping['training_request_id']]);

            $existingResult = db_single(
                $conn,
                "SELECT id FROM training_results WHERE workman_id = ? AND training_session_id = ? ORDER BY id DESC LIMIT 1",
                'is',
                [$workman_id, (string)$session_id]
            );
            if ($existingResult) {
                safetyResultsUpdateExistingColumns(
                    $conn,
                    'training_results',
                    [
                        'application_id' => ['value' => (string)($mapping['application_no'] ?? ''), 'type' => 's'],
                        'worker_name' => ['value' => (string)($mapping['name'] ?? ''), 'type' => 's'],
                        'trade' => ['value' => (string)($mapping['trade'] ?? ''), 'type' => 's'],
                        'attendance_status' => ['value' => 'present', 'type' => 's'],
                        'result' => ['value' => $res, 'type' => 's'],
                        'theory_score' => ['value' => $theory, 'type' => 'i'],
                        'practical_score' => ['value' => $practical, 'type' => 'i'],
                        'total_score' => ['value' => $total, 'type' => 'i'],
                        'pass_mark' => ['value' => $passMark, 'type' => 'i'],
                        'valid_till' => ['value' => $valid, 'type' => 's'],
                        'remarks' => ['value' => $rem, 'type' => 's'],
                        'recorded_by' => ['value' => (int)($_SESSION['user_id'] ?? 0), 'type' => 'i'],
                        'updated_at' => ['raw' => 'NOW()'],
                    ],
                    'id = ?',
                    'i',
                    [(int)$existingResult['id']]
                );
            } else {
                safetyResultsInsertExistingColumns(
                    $conn,
                    'training_results',
                    [
                        'training_session_id' => ['value' => (string)$session_id, 'type' => 's'],
                        'application_id' => ['value' => (string)($mapping['application_no'] ?? ''), 'type' => 's'],
                        'workman_id' => ['value' => $workman_id, 'type' => 'i'],
                        'worker_name' => ['value' => (string)($mapping['name'] ?? ''), 'type' => 's'],
                        'trade' => ['value' => (string)($mapping['trade'] ?? ''), 'type' => 's'],
                        'attendance_status' => ['value' => 'present', 'type' => 's'],
                        'result' => ['value' => $res, 'type' => 's'],
                        'theory_score' => ['value' => $theory, 'type' => 'i'],
                        'practical_score' => ['value' => $practical, 'type' => 'i'],
                        'total_score' => ['value' => $total, 'type' => 'i'],
                        'pass_mark' => ['value' => $passMark, 'type' => 'i'],
                        'valid_till' => ['value' => $valid, 'type' => 's'],
                        'remarks' => ['value' => $rem, 'type' => 's'],
                        'recorded_by' => ['value' => (int)($_SESSION['user_id'] ?? 0), 'type' => 'i'],
                        'created_at' => ['raw' => 'NOW()'],
                        'updated_at' => ['raw' => 'NOW()'],
                    ]
                );
            }
        } else {
            // If absent, result is automatically failed
            db_execute($conn, "UPDATE training_session_workers SET result='fail', theory_score=0, practical_score=0, total_score=0, pass_mark=?, remarks='Marked Fail due to Absence' WHERE session_id=? AND workman_id=?", 'iii', [$passMark, $session_id, $workman_id]);
            safetyResultsUpdateExistingColumns(
                $conn,
                'workmen',
                [
                    'training_status' => ['value' => 'training_failed', 'type' => 's'],
                    'eligibility_status' => ['value' => 'NOT ELIGIBLE', 'type' => 's'],
                    'training_valid_till' => ['raw' => 'NULL'],
                    'safety_training_status' => ['value' => 'TRAINING_FAILED', 'type' => 's'],
                    'updated_at' => ['raw' => 'NOW()'],
                ],
                'id = ?',
                'i',
                [$workman_id]
            );
            
            // Sync with training_requests status
            if ($mapping && !empty($mapping['training_request_id'])) {
                db_execute($conn, "UPDATE training_requests SET status = 'failed', conduct_remarks = 'Absent in session', updated_at = NOW() WHERE id = ? AND status = 'contractor_confirmed'", 'i', [(int)$mapping['training_request_id']]);
            }
        }
    }

    $apps = db_fetch_all(
        $conn,
        "SELECT DISTINCT application_no FROM workmen WHERE id IN (" . implode(',', array_fill(0, count($results), '?')) . ")",
        str_repeat('i', count($results)),
        array_map('intval', array_keys($results))
    );
    foreach ($apps as $app) {
        $appNo = $app['application_no'] ?? '';
        if (empty($appNo)) continue;
        
        $pending = db_count(
            $conn,
            "SELECT COUNT(*) FROM workmen WHERE application_no = ? AND training_status NOT IN ('training_passed','pass','qualified','completed')",
            's',
            [$appNo]
        );
        if ($pending === 0) {
            WorkflowEngine::performAction($conn, $appNo, 'complete_training', $_SESSION['role'], (int)($_SESSION['user_id'] ?? 0), 'All workers passed safety training');
        }
    }
    clms_db_commit($conn);
    safetyResultsJson(["success" => true, "message" => "Results updated successfully"]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    safetyResultsJson(["success" => false, "message" => $e->getMessage()], 500);
}

