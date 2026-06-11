<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/execution_context.php';
require_once __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json; charset=utf-8');
enforceRole(['execution_officer', 'execution', 'super_admin']);

function executionTrainingJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function executionTrainingColumnExists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function executionTrainingEnsureColumn($conn, $table, $column, $definition) {
    if (executionTrainingColumnExists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function executionTrainingEnsureFlowSchema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        training_type VARCHAR(100) NULL,
        requested_date DATE NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        remarks TEXT NULL,
        source VARCHAR(30) NULL,
        requested_by INT NULL,
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
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        executionTrainingEnsureColumn($conn, 'training_requests', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

    foreach ([
        'training_status' => "VARCHAR(50) DEFAULT 'pending'",
        'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
        'executing_officer_code' => 'VARCHAR(50) NULL',
        'executing_officer_id' => 'BIGINT NULL',
        'work_order_source' => 'VARCHAR(20) NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        executionTrainingEnsureColumn($conn, 'workmen', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
    @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
    @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN execution_training_status VARCHAR(30) DEFAULT 'pending'");
}

function executionTrainingEnsureNotificationsSchema($conn) {
    // Create table if it doesn't exist
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL DEFAULT 0,
        role_target VARCHAR(50) NULL,
        title VARCHAR(255) NULL,
        message TEXT NULL,
        type VARCHAR(50) DEFAULT 'info',
        notification_type VARCHAR(50) DEFAULT 'info',
        related_id VARCHAR(100) NULL,
        is_read TINYINT(1) DEFAULT 0,
        is_deleted TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure required columns exist
    foreach ([
        'role_target'       => "VARCHAR(50) NULL",
        'title'             => "VARCHAR(255) NULL",
        'notification_type' => "VARCHAR(50) DEFAULT 'info'",
        'related_id'        => "VARCHAR(100) NULL",
        'is_deleted'        => "TINYINT(1) DEFAULT 0",
    ] as $column => $definition) {
        if (!executionTrainingColumnExists($conn, 'notifications', $column)) {
            $safeColumn = str_replace('`', '``', $column);
            @mysqli_query($conn, "ALTER TABLE `notifications` ADD COLUMN `$safeColumn` $definition");
        }
    }
}

try {
    executionTrainingEnsureFlowSchema($conn);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        executionTrainingJson(['status' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $officerId = clms_execution_get_officer_id($conn, (int)($_SESSION['user_id'] ?? 0));
    if (!$officerId) {
        executionTrainingJson(['status' => false, 'message' => 'Officer record not found.'], 403);
    }
    $officer = db_single($conn, "SELECT employee_code FROM execution_officers WHERE id = ? LIMIT 1", 'i', [$officerId]);
    $employeeExpr = executionTrainingColumnExists($conn, 'users', 'employee_code') ? 'employee_code' : "'' AS employee_code";
    $loginUser = db_single($conn, "SELECT contractor_id, $employeeExpr FROM users WHERE id = ? LIMIT 1", 'i', [(int)($_SESSION['user_id'] ?? 0)]);
    $officerCodes = array_values(array_unique(array_filter(array_map(function($code) {
        return strtoupper(trim((string)$code));
    }, [
        $officer['employee_code'] ?? '',
        $loginUser['employee_code'] ?? '',
        $loginUser['contractor_id'] ?? '',
    ]))));
    $officerNames = array_values(array_unique(array_filter(array_map(function($name) {
        return strtoupper(trim((string)$name));
    }, [
        $_SESSION['name'] ?? '',
    ]))));
    $codePlaceholders = implode(',', array_fill(0, max(1, count($officerCodes)), '?'));
    $namePlaceholders = implode(',', array_fill(0, max(1, count($officerNames)), '?'));

    $workmanId = (int)($input['workman_id'] ?? 0);
    $decision = strtolower(trim((string)($input['decision'] ?? '')));
    $remarks = trim((string)($input['remarks'] ?? ''));

    if (!$workmanId || !in_array($decision, ['approved', 'rejected'], true)) {
        executionTrainingJson(['status' => false, 'message' => 'Workman and decision are required.'], 400);
    }

    $worker = db_single(
        $conn,
        "SELECT w.id, w.name, w.contractor_id, w.training_approval_doc, w.training_status, w.work_order_source
         FROM workmen w
         WHERE w.id = ?
           AND (
               w.executing_officer_id IN (?, ?)
               OR UPPER(COALESCE(w.executing_officer_code, '')) IN ($codePlaceholders)
               OR UPPER(COALESCE(w.executing_officer_name, '')) IN ($namePlaceholders)
           )
         LIMIT 1",
        'iii' . str_repeat('s', max(1, count($officerCodes))) . str_repeat('s', max(1, count($officerNames))),
        array_merge([$workmanId, (int)$officerId, (int)($_SESSION['user_id'] ?? 0)], $officerCodes ?: [''], $officerNames ?: [''])
    );

    if (!$worker) {
        executionTrainingJson(['status' => false, 'message' => 'Worker is not assigned to this officer.'], 403);
    }

    $requiresPayment = strtoupper(trim((string)($worker['work_order_source'] ?? ''))) === 'PWO';
    $paidPayment = $requiresPayment ? db_single(
        $conn,
        "SELECT pr.id
         FROM training_payment_request_workers pw
         JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
         WHERE pw.workman_id = ?
           AND pr.status = 'paid'
         LIMIT 1",
        'i',
        [$workmanId]
    ) : ['id' => 0];
    if ($requiresPayment && !$paidPayment) {
        executionTrainingJson(['status' => false, 'message' => 'Payment is not verified by Welfare yet.'], 400);
    }

    $submittedTraining = db_single(
        $conn,
        "SELECT id, status
         FROM training_requests
         WHERE workman_id = ?
           AND status IN ('pending_eo','pending_safety','welfare_pending','pending','scheduled','contractor_confirmed','passed')
         ORDER BY id DESC
         LIMIT 1",
        'i',
        [$workmanId]
    );
    if (!$submittedTraining) {
        executionTrainingJson(['status' => false, 'message' => 'Safety seat booking / enrolment submission is not completed yet.'], 400);
    }

    $conn->begin_transaction();

    db_execute(
        $conn,
        "UPDATE workmen
         SET execution_training_status = ?,
             execution_training_remarks = ?,
             execution_training_reviewed_by = ?,
             execution_training_reviewed_at = NOW()
         WHERE id = ?",
        'ssii',
        [$decision, $remarks, $officerId, $workmanId]
    );

    db_execute(
        $conn,
        "INSERT INTO execution_audit_logs (execution_officer_id, action, entity_type, entity_id, new_value, created_at)
         VALUES (?, 'TRAINING_ATTENDANCE_REVIEW', 'workman', ?, ?, NOW())",
        'iis',
        [$officerId, $workmanId, json_encode(['decision' => $decision, 'remarks' => $remarks], JSON_UNESCAPED_SLASHES)]
    );

    if ($decision === 'approved') {
        clms_training_ensure_request($conn, $workmanId, (int)$worker['contractor_id'], (int)($_SESSION['user_id'] ?? 0), 'execution', 'Forwarded after Executing Officer approval. Waiting for Safety Department approval.');

        // ── Send notification to Safety Officer's inbox ──
        try {
            // Ensure notifications table has required columns
            executionTrainingEnsureNotificationsSchema($conn);

            $workerName = htmlspecialchars($worker['name'] ?? 'Worker', ENT_QUOTES);
            $notifTitle = 'New Training Enrollment Approval Pending';
            $notifMessage = "Executing Officer approved training attendance for {$workerName} (ID: {$workmanId}). Please review and approve the enrollment in Safety Department Enrollment Inbox.";

            // Notify all safety_user role users
            @db_execute(
                $conn,
                "INSERT INTO notifications (user_id, role_target, title, message, notification_type, related_id, is_read, is_deleted, created_at)
                 VALUES (0, 'safety_user', ?, ?, 'info', ?, 0, 0, NOW())",
                'sss',
                [$notifTitle, $notifMessage, (string)$workmanId]
            );
        } catch (Throwable $notifErr) {
            error_log('[EO_TRAINING_APPROVE_NOTIF] ' . $notifErr->getMessage());
        }
    } else {
        db_execute(
            $conn,
            "UPDATE training_requests
             SET status = 'correction_required',
                 remarks = ?,
                 updated_at = NOW()
             WHERE workman_id = ?
               AND status IN ('pending_eo','pending_safety','welfare_pending','pending','safety_rejected')
             ORDER BY id DESC
             LIMIT 1",
            'si',
            [$remarks ?: 'Returned by Executing Officer for contractor correction/resubmission.', $workmanId]
        );

        // ── Notify contractor about rejection ──
        try {
            executionTrainingEnsureNotificationsSchema($conn);

            $contractorUser = db_single(
                $conn,
                "SELECT user_id FROM contractors WHERE id = ? LIMIT 1",
                'i',
                [(int)$worker['contractor_id']]
            );
            $contractorUserId = (int)($contractorUser['user_id'] ?? 0);
            if ($contractorUserId > 0) {
                $workerName = htmlspecialchars($worker['name'] ?? 'Worker', ENT_QUOTES);
                $rejectTitle = 'Training Attendance Rejected by EO';
                $rejectMessage = "Executing Officer rejected training attendance for {$workerName} (ID: {$workmanId}). Remarks: " . ($remarks ?: 'Please correct and resubmit.');

                @db_execute(
                    $conn,
                    "INSERT INTO notifications (user_id, role_target, title, message, notification_type, related_id, is_read, is_deleted, created_at)
                     VALUES (?, 'contractor', ?, ?, 'warning', ?, 0, 0, NOW())",
                    'isss',
                    [$contractorUserId, $rejectTitle, $rejectMessage, (string)$workmanId]
                );
            }
        } catch (Throwable $notifErr) {
            error_log('[EO_TRAINING_REJECT_NOTIF] ' . $notifErr->getMessage());
        }
    }

    $conn->commit();

    executionTrainingJson([
        'status' => true,
        'message' => $decision === 'approved'
            ? 'Executing Officer approval completed. Request forwarded to Safety Department Approval.'
            : 'Executing Officer rejected the enrolment approval request. Returned to Contractor for Correction / Resubmission.',
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    error_log('[EXECUTION_TRAINING_APPROVAL] ' . $e->getMessage());
    executionTrainingJson(['status' => false, 'message' => 'Training approval action failed on server.'], 500);
}
