<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

function training_json($payload, $statusCode = 200) {
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
    error_log('[submit_training_request] Uncaught: ' . $e->getMessage());
    training_json(['success' => false, 'message' => $e->getMessage()]);
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[submit_training_request] Fatal: ' . $error['message']);
        training_json(['success' => false, 'message' => 'Fatal server error: ' . $error['message']]);
    }
});

try {
    require_once 'api_helper.php';
    require_once '../include/config.php';
    require_once '../include/customer_portal_context.php';
    require_once '../include/payment_flow.php';

    // api_helper/include/session may replace handlers; restore JSON-safe handlers.
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) return false;
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    set_exception_handler(function($e) {
        error_log('[submit_training_request] Uncaught: ' . $e->getMessage());
        training_json(['success' => false, 'message' => $e->getMessage()]);
    });

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        training_json(['success' => false, 'message' => 'Only POST requests are allowed'], 405);
    }

    training_ensure_schema($conn);
    clms_get_portal_contractor($conn);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON input');
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $contractor = $userId ? db_single($conn, "SELECT id, application_no, status, is_blocked, block_reason FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]) : null;
    if (!$contractor) {
        throw new Exception('Contractor registration not found');
    }
    
    if ($contractor['is_blocked']) {
        throw new Exception('Your firm is BLOCKED. Access to Training Request is denied. Reason: ' . ($contractor['block_reason'] ?: 'Security/Disciplinary'));
    }

    $contractorId = (int)$contractor['id'];
    $applicationNo = $contractor['application_no'] ?: '';
    
    $workerIds = $input['workman_ids'] ?? $input['worker_ids'] ?? [];
    if (!is_array($workerIds) || count($workerIds) === 0) {
        throw new Exception('Select at least one workman for safety training');
    }

    $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
    if (!$workerIds) {
        throw new Exception('No valid workman ids supplied');
    }

    $preferredDate = trim($input['preferred_date'] ?? '');
    $trainingType = trim($input['training_type'] ?? 'Safety Induction');

    $conn->begin_transaction();
    $created = 0;
    $alreadyQueued = 0;
    $paymentWorkerIds = [];

    foreach ($workerIds as $workerId) {
        $worker = db_single(
            $conn,
            "SELECT id, training_status, execution_training_status, execution_training_reviewed_by FROM workmen WHERE id = ? AND contractor_id = ? LIMIT 1",
            'ii',
            [$workerId, $contractorId]
        );
        if (!$worker) {
            continue;
        }

        if (strtolower((string)($worker['execution_training_status'] ?? 'pending')) !== 'approved' || (int)($worker['execution_training_reviewed_by'] ?? 0) <= 0) {
            continue;
        }

        $status = strtolower((string)$worker['training_status']);
        if (in_array($status, ['training_scheduled', 'training_passed', 'pass', 'qualified', 'completed'], true)) {
            continue;
        }

        $existing = db_single(
            $conn,
            "SELECT id FROM training_requests
             WHERE workman_id = ?
               AND status IN ('welfare_pending','pending','scheduled','contractor_confirmed','passed')
             ORDER BY id DESC LIMIT 1",
            'i',
            [$workerId]
        );
        if ($existing) {
            $alreadyQueued++;
            continue;
        }

        $preferredShift = in_array($input['preferred_shift'] ?? '', ['morning','evening']) ? $input['preferred_shift'] : 'morning';
        $previousFailed = db_single(
            $conn,
            "SELECT id FROM training_requests
             WHERE workman_id = ?
               AND status IN ('failed','rejected','correction_required')
             ORDER BY id DESC LIMIT 1",
            'i',
            [$workerId]
        );

        if ($previousFailed) {
            db_execute(
                $conn,
                "UPDATE training_requests
                 SET training_type = ?,
                     requested_date = ?,
                     preferred_date = ?,
                     preferred_shift = ?,
                     remarks = ?,
                     source = 'contractor',
                     requested_by = ?,
                     status = 'welfare_pending',
                     contractor_confirmed = 0,
                     scheduled_date = NULL,
                     scheduled_shift = NULL,
                     scheduled_venue = NULL,
                     scheduled_time = NULL,
                     batch_number = NULL,
                     instructor = NULL,
                     safety_remarks = NULL,
                     conduct_remarks = NULL,
                     updated_at = NOW()
                 WHERE id = ?",
                'sssssii',
                [
                    $trainingType,
                    date('Y-m-d'),
                    $preferredDate !== '' ? $preferredDate : null,
                    $preferredShift,
                    trim($input['remarks'] ?? 'Re-training requested after failed Safety Induction.'),
                    $userId,
                    (int)$previousFailed['id'],
                ]
            );
            training_update_workman_status($conn, $workerId);
            $created++;
            $paymentWorkerIds[] = $workerId;
            continue;
        }

        training_insert_request($conn, [
            'workman_id' => $workerId,
            'contractor_id' => $contractorId,
            'training_type' => $trainingType,
            'requested_date' => date('Y-m-d'),
            'preferred_date' => $preferredDate !== '' ? $preferredDate : null,
            'preferred_shift' => $preferredShift,
            'remarks' => trim($input['remarks'] ?? ''),
            'source' => 'contractor',
            'requested_by' => $userId,
            'status' => 'welfare_pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        training_update_workman_status($conn, $workerId);
        $created++;
        $paymentWorkerIds[] = $workerId;
    }

    if ($created === 0 && $alreadyQueued > 0) {
        $conn->commit();
        training_json([
            'success' => true,
            'message' => 'Re-training request is already pending with Safety. Please wait for scheduling.',
            'data' => [
                'worker_count' => 0,
                'already_queued' => $alreadyQueued,
            ],
        ]);
    }

    if ($created === 0) {
        throw new Exception('No eligible workers found for training request');
    }

    if ($applicationNo !== '') {
        if (($contractor['status'] ?? '') === 'approved') {
            training_update_annexure_status($conn, $applicationNo);
        }
        training_upsert_workflow($conn, $applicationNo, $contractorId);
    }

    $paymentRequest = clms_create_training_payment_request(
        $conn,
        $contractorId,
        $paymentWorkerIds,
        $userId,
        'training_request'
    );

    $conn->commit();

    training_json([
        'success' => true,
        'message' => 'Safety training request submitted successfully and payment link generated.',
        'data' => [
            'request_id' => 'STR-' . date('Ymd') . '-' . random_int(1000, 9999),
            'worker_count' => $created,
            'payment' => $paymentRequest ? [
                'payment_ref' => $paymentRequest['payment_ref'],
                'amount' => $paymentRequest['total_amount'],
                'payment_link' => $paymentRequest['payment_link'],
                'link_expires_at' => $paymentRequest['link_expires_at'],
            ] : null,
        ],
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    training_json(['success' => false, 'message' => $e->getMessage()]);
}

function training_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function training_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function training_column_meta($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
}

function training_ensure_column($conn, $table, $column, $definition) {
    if (training_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    if (!mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
        throw new Exception("DB column `$table.$column` missing and auto-create failed: " . mysqli_error($conn));
    }
}

function training_ensure_schema($conn) {
    if (training_table_exists($conn, 'contractors')) {
        training_ensure_column($conn, 'contractors', 'user_id', 'INT NULL');
        training_ensure_column($conn, 'contractors', 'application_no', 'VARCHAR(50) NULL');
        training_ensure_column($conn, 'contractors', 'status', "VARCHAR(50) DEFAULT 'draft'");
        training_ensure_column($conn, 'contractors', 'is_blocked', 'TINYINT(1) DEFAULT 0');
        training_ensure_column($conn, 'contractors', 'block_reason', 'VARCHAR(255) NULL');
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        training_type VARCHAR(100) NULL,
        requested_date DATE NOT NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        remarks TEXT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'workman_id' => 'INT NOT NULL',
        'contractor_id' => 'INT NOT NULL',
        'training_type' => 'VARCHAR(100) NULL',
        'requested_date' => 'DATE NULL',
        'preferred_date' => 'DATE NULL',
        'preferred_shift' => "VARCHAR(20) DEFAULT 'morning'",
        'remarks' => 'TEXT NULL',
        'source' => 'VARCHAR(30) NULL',
        'requested_by' => 'INT NULL',
        'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
        'scheduled_date' => 'DATE NULL',
        'scheduled_shift' => 'VARCHAR(20) NULL',
        'scheduled_venue' => 'VARCHAR(300) NULL',
        'scheduled_time' => 'VARCHAR(20) NULL',
        'batch_number' => 'VARCHAR(100) NULL',
        'instructor' => 'VARCHAR(150) NULL',
        'safety_remarks' => 'TEXT NULL',
        'conduct_remarks' => 'TEXT NULL',
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        training_ensure_column($conn, 'training_requests', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

    $idMeta = training_column_meta($conn, 'training_requests', 'id');
    if ($idMeta && stripos($idMeta['Extra'] ?? '', 'auto_increment') === false) {
        @mysqli_query($conn, "ALTER TABLE training_requests MODIFY id INT NOT NULL AUTO_INCREMENT");
    }

    if (training_table_exists($conn, 'workmen')) {
        training_ensure_column($conn, 'workmen', 'training_status', "VARCHAR(50) DEFAULT 'pending'");
        training_ensure_column($conn, 'workmen', 'safety_training_status', "VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
        training_ensure_column($conn, 'workmen', 'execution_training_status', "VARCHAR(30) DEFAULT 'pending'");
        training_ensure_column($conn, 'workmen', 'execution_training_reviewed_by', 'BIGINT NULL');
        training_ensure_column($conn, 'workmen', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN execution_training_status VARCHAR(30) DEFAULT 'pending'");
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS application_workflow (
        id INT NOT NULL AUTO_INCREMENT,
        application_id VARCHAR(50) NULL,
        contractor_id INT NULL,
        current_stage VARCHAR(100) NULL,
        training_status VARCHAR(50) NULL,
        overall_status VARCHAR(50) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        'application_id' => 'VARCHAR(50) NULL',
        'contractor_id' => 'INT NULL',
        'current_stage' => 'VARCHAR(100) NULL',
        'training_status' => 'VARCHAR(50) NULL',
        'overall_status' => 'VARCHAR(50) NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        training_ensure_column($conn, 'application_workflow', $column, $definition);
    }
}

function training_filter_row($conn, $table, $row) {
    $safeTable = str_replace('`', '``', $table);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable`");
    $cols = [];
    if ($res) while ($c = mysqli_fetch_assoc($res)) $cols[$c['Field']] = true;
    return array_intersect_key($row, $cols);
}

function training_next_id($conn, $table) {
    $safeTable = str_replace('`', '``', $table);
    $res = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 next_id FROM `$safeTable`");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return (int)($row['next_id'] ?? 1);
}

function training_insert_row($conn, $table, $row) {
    $row = training_filter_row($conn, $table, $row);
    $idMeta = training_column_meta($conn, $table, 'id');
    if ($idMeta && stripos($idMeta['Extra'] ?? '', 'auto_increment') === false && !isset($row['id'])) {
        $row = ['id' => training_next_id($conn, $table)] + $row;
    }
    $cols = array_keys($row);
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("$table insert prepare failed: " . $conn->error);
    $values = array_values($row);
    $bind = [str_repeat('s', count($values))];
    foreach ($values as $i => $value) {
        $bind[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("$table insert failed: " . $error);
    }
    $id = (int)$stmt->insert_id;
    $stmt->close();
    return $id ?: (int)($row['id'] ?? 0);
}

function training_insert_request($conn, $row) {
    return training_insert_row($conn, 'training_requests', $row);
}

function training_update_workman_status($conn, $workerId) {
    if (!training_table_exists($conn, 'workmen')) return;
    $sets = [];
    if (training_column_exists($conn, 'workmen', 'training_status')) $sets[] = "training_status = 'training_pending'";
    if (training_column_exists($conn, 'workmen', 'safety_training_status')) $sets[] = "safety_training_status = 'PENDING_TRAINING'";
    if (training_column_exists($conn, 'workmen', 'updated_at')) $sets[] = "updated_at = NOW()";
    if (!$sets) return;
    mysqli_query($conn, "UPDATE workmen SET " . implode(', ', $sets) . " WHERE id = " . (int)$workerId);
}

function training_update_annexure_status($conn, $applicationNo) {
    if (!training_table_exists($conn, 'annexure2a') || !training_column_exists($conn, 'annexure2a', 'application_id')) return;
    $sets = [];
    if (training_column_exists($conn, 'annexure2a', 'workflow_status')) $sets[] = "workflow_status = 'enrolment_done'";
    if (training_column_exists($conn, 'annexure2a', 'updated_at')) $sets[] = "updated_at = NOW()";
    if (!$sets) return;
    $app = mysqli_real_escape_string($conn, $applicationNo);
    mysqli_query($conn, "UPDATE annexure2a SET " . implode(', ', $sets) . " WHERE application_id = '$app'");
}

function training_upsert_workflow($conn, $applicationNo, $contractorId) {
    if (!training_table_exists($conn, 'application_workflow')) return;
    $app = mysqli_real_escape_string($conn, $applicationNo);
    $existing = null;
    if (training_column_exists($conn, 'application_workflow', 'application_id')) {
        $res = mysqli_query($conn, "SELECT id FROM application_workflow WHERE application_id = '$app' LIMIT 1");
        $existing = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
    }
    $row = [
        'application_id' => $applicationNo,
        'contractor_id' => $contractorId,
        'current_stage' => 'enrolment_done',
        'training_status' => 'pending',
        'overall_status' => 'enrolment_done',
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    if ($existing && isset($existing['id'])) {
        $row = training_filter_row($conn, 'application_workflow', $row);
        unset($row['application_id']);
        $sets = [];
        foreach ($row as $col => $value) $sets[] = "`$col` = '" . mysqli_real_escape_string($conn, (string)$value) . "'";
        mysqli_query($conn, "UPDATE application_workflow SET " . implode(', ', $sets) . " WHERE id = " . (int)$existing['id']);
    } else {
        training_insert_row($conn, 'application_workflow', $row);
    }
}
?>
