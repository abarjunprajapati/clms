<?php

function clms_training_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_training_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_training_ensure_column($conn, $table, $column, $definition) {
    if (!clms_training_table_exists($conn, $table) || clms_training_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function clms_training_ensure_schema($conn) {
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
        'training_type' => 'VARCHAR(100) NULL',
        'requested_date' => 'DATE NULL',
        'preferred_date' => 'DATE NULL',
        'preferred_shift' => "VARCHAR(20) DEFAULT 'morning'",
        'remarks' => 'TEXT NULL',
        'source' => 'VARCHAR(30) NULL',
        'requested_by' => 'INT NULL',
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'welfare_remarks' => 'TEXT NULL',
        'welfare_reviewed_by' => 'INT NULL',
        'welfare_reviewed_at' => 'DATETIME NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        clms_training_ensure_column($conn, 'training_requests', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

    if (clms_training_table_exists($conn, 'workmen')) {
        foreach ([
            'training_status' => "VARCHAR(50) DEFAULT 'pending'",
            'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
            'training_approval_doc' => 'VARCHAR(255) NULL',
            'executing_officer_code' => 'VARCHAR(50) NULL',
            'executing_officer_name' => 'VARCHAR(200) NULL',
            'executing_officer_id' => 'BIGINT NULL',
            'execution_training_status' => "VARCHAR(30) DEFAULT 'pending'",
            'execution_training_remarks' => 'TEXT NULL',
            'execution_training_reviewed_by' => 'BIGINT NULL',
            'execution_training_reviewed_at' => 'DATETIME NULL',
        ] as $column => $definition) {
            clms_training_ensure_column($conn, 'workmen', $column, $definition);
        }
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN execution_training_status VARCHAR(30) DEFAULT 'pending'");
    }
}

function clms_training_ensure_request($conn, $workmanId, $contractorId, $requestedBy = 0, $source = 'execution', $remarks = '') {
    clms_training_ensure_schema($conn);
    $existing = db_single(
        $conn,
        "SELECT id FROM training_requests WHERE workman_id = ? AND status IN ('welfare_pending','pending','scheduled','contractor_confirmed','passed') ORDER BY id DESC LIMIT 1",
        'i',
        [(int)$workmanId]
    );
    if ($existing) return (int)$existing['id'];

    $remarks = $remarks ?: 'Auto-created after Executing Officer approval. Waiting for Welfare check.';
    $ok = db_execute(
        $conn,
        "INSERT INTO training_requests
         (workman_id, contractor_id, training_type, requested_date, preferred_date, preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
         VALUES (?, ?, 'Safety Induction', CURDATE(), NULL, 'morning', ?, ?, ?, 'welfare_pending', NOW(), NOW())",
        'iissi',
        [(int)$workmanId, (int)$contractorId, $remarks, $source, (int)$requestedBy]
    );
    return $ok ? (int)mysqli_insert_id($conn) : 0;
}

function clms_training_auto_approve_attached_document($conn, $workmanId, $reviewedBy = 0, $remarks = '') {
    clms_training_ensure_schema($conn);
    $worker = db_single(
        $conn,
        "SELECT id, contractor_id, training_approval_doc, execution_training_status, execution_training_reviewed_by
         FROM workmen
         WHERE id = ? LIMIT 1",
        'i',
        [(int)$workmanId]
    );
    if (!$worker || trim((string)($worker['training_approval_doc'] ?? '')) === '') {
        return false;
    }

    $reviewedBy = (int)($reviewedBy ?: ($worker['execution_training_reviewed_by'] ?? 0));
    $remarks = $remarks ?: 'Auto-approved because Training Attendance Approval document is attached.';
    db_execute(
        $conn,
        "UPDATE workmen
         SET execution_training_status = 'approved',
             execution_training_remarks = ?,
             execution_training_reviewed_by = ?,
             execution_training_reviewed_at = COALESCE(execution_training_reviewed_at, NOW())
         WHERE id = ?",
        'sii',
        [$remarks, $reviewedBy, (int)$workmanId]
    );
    clms_training_ensure_request($conn, (int)$workmanId, (int)$worker['contractor_id'], $reviewedBy, 'attached_doc', 'Auto-created from attached Training Attendance Approval document.');
    return true;
}

function clms_training_seed_approved_queue($conn) {
    clms_training_ensure_schema($conn);
    if (
        !clms_training_table_exists($conn, 'workmen') ||
        !clms_training_table_exists($conn, 'training_requests') ||
        !clms_training_table_exists($conn, 'training_payment_requests') ||
        !clms_training_table_exists($conn, 'training_payment_request_workers')
    ) return;

    @mysqli_query($conn, "
        INSERT INTO training_requests
            (workman_id, contractor_id, training_type, requested_date, preferred_date, preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
        SELECT
            w.id,
            w.contractor_id,
            'Safety Induction',
            CURDATE(),
            NULL,
            'morning',
            'Auto-created for Welfare check after Executing Officer approval.',
            CASE WHEN COALESCE(w.training_approval_doc, '') <> '' THEN 'attached_doc' ELSE 'welfare_seed' END,
            COALESCE(w.execution_training_reviewed_by, 0),
            'welfare_pending',
            NOW(),
            NOW()
        FROM workmen w
        WHERE COALESCE(w.execution_training_status, '') = 'approved'
          AND COALESCE(w.contractor_id, 0) > 0
          AND EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
          )
          AND LOWER(TRIM(COALESCE(w.training_status, 'pending'))) IN ('', 'pending', 'training_pending', 'training_failed', 'fail', 'failed')
          AND NOT EXISTS (
              SELECT 1 FROM training_requests tr
              WHERE tr.workman_id = w.id
                AND tr.status IN ('welfare_pending', 'pending', 'scheduled', 'contractor_confirmed', 'passed')
          )
    ");
}
?>
