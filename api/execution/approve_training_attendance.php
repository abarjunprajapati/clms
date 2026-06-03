<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/execution_context.php';
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

    foreach ([
        'training_status' => "VARCHAR(50) DEFAULT 'pending'",
        'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        executionTrainingEnsureColumn($conn, 'workmen', $column, $definition);
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

    $workmanId = (int)($input['workman_id'] ?? 0);
    $decision = strtolower(trim((string)($input['decision'] ?? '')));
    $remarks = trim((string)($input['remarks'] ?? ''));

    if (!$workmanId || !in_array($decision, ['approved', 'rejected'], true)) {
        executionTrainingJson(['status' => false, 'message' => 'Workman and decision are required.'], 400);
    }

    $worker = db_single(
        $conn,
        "SELECT w.id, w.name, w.contractor_id, w.training_approval_doc, w.training_status
         FROM workmen w
         WHERE w.id = ?
           AND EXISTS (
               SELECT 1
               FROM execution_officer_contractors eoc
               WHERE eoc.execution_officer_id = ?
                 AND eoc.contractor_id = w.contractor_id
           )
         LIMIT 1",
        'ii',
        [$workmanId, $officerId]
    );

    if (!$worker) {
        executionTrainingJson(['status' => false, 'message' => 'Worker is not assigned to this officer.'], 403);
    }

    if (trim((string)($worker['training_approval_doc'] ?? '')) === '') {
        executionTrainingJson(['status' => false, 'message' => 'Training approval document is not uploaded for this worker.'], 400);
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

    $conn->commit();

    executionTrainingJson([
        'status' => true,
        'message' => $decision === 'approved'
            ? 'Training attendance approved. Contractor can now submit the safety training request.'
            : 'Training attendance rejected. Contractor can upload the corrected document again.',
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    error_log('[EXECUTION_TRAINING_APPROVAL] ' . $e->getMessage());
    executionTrainingJson(['status' => false, 'message' => 'Training approval action failed on server.'], 500);
}
