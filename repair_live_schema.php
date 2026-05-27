<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/include/config.php';

$summary = [
    'success' => true,
    'fixed' => [],
    'skipped' => [],
    'errors' => [],
];

function repair_json($payload) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function repair_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function repair_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function repair_column_meta($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
}

function repair_ensure_column($conn, $table, $column, $definition, &$summary) {
    if (!repair_table_exists($conn, $table) || repair_column_exists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    $sql = "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition";
    if (mysqli_query($conn, $sql)) {
        $summary['fixed'][] = "Added $table.$column";
    } else {
        $summary['errors'][] = "Add $table.$column failed: " . mysqli_error($conn);
    }
}

function repair_create_training_requests($conn, &$summary) {
    $sql = "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        remarks TEXT NULL,
        training_type VARCHAR(100) NULL,
        requested_date DATE NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $summary['fixed'][] = 'Ensured training_requests table';
    } else {
        $summary['errors'][] = 'Create training_requests failed: ' . mysqli_error($conn);
    }
}

function repair_create_application_workflow($conn, &$summary) {
    $sql = "CREATE TABLE IF NOT EXISTS application_workflow (
        id INT NOT NULL AUTO_INCREMENT,
        application_id VARCHAR(50) NULL,
        contractor_id INT NULL,
        current_stage VARCHAR(100) NULL,
        training_status VARCHAR(50) NULL,
        overall_status VARCHAR(50) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $summary['fixed'][] = 'Ensured application_workflow table';
    } else {
        $summary['errors'][] = 'Create application_workflow failed: ' . mysqli_error($conn);
    }
}

function repair_create_training_schedule($conn, &$summary) {
    $sql = "CREATE TABLE IF NOT EXISTS training_schedule (
        id INT NOT NULL AUTO_INCREMENT,
        session_date DATE NULL,
        session_time TIME NULL,
        location VARCHAR(255) NULL,
        capacity INT DEFAULT 30,
        enrolled_count INT DEFAULT 0,
        trainer_name VARCHAR(100) NULL,
        batch_number VARCHAR(50) NULL,
        training_type VARCHAR(50) DEFAULT 'induction',
        session_status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $summary['fixed'][] = 'Ensured training_schedule table';
    } else {
        $summary['errors'][] = 'Create training_schedule failed: ' . mysqli_error($conn);
    }
}

function repair_create_training_session_workers($conn, &$summary) {
    $sql = "CREATE TABLE IF NOT EXISTS training_session_workers (
        id INT NOT NULL AUTO_INCREMENT,
        session_id INT NOT NULL,
        workman_id INT NOT NULL,
        training_request_id INT NULL,
        attendance_status VARCHAR(20) DEFAULT 'pending',
        result VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($conn, $sql)) {
        $summary['fixed'][] = 'Ensured training_session_workers table';
    } else {
        $summary['errors'][] = 'Create training_session_workers failed: ' . mysqli_error($conn);
    }
}

function repair_fix_auto_increment($conn, $table, &$summary) {
    if (!repair_table_exists($conn, $table) || !repair_column_exists($conn, $table, 'id')) {
        return;
    }
    $meta = repair_column_meta($conn, $table, 'id');
    if (!$meta || stripos(isset($meta['Extra']) ? $meta['Extra'] : '', 'auto_increment') !== false) {
        return;
    }

    $safeTable = str_replace('`', '``', $table);
    $type = isset($meta['Type']) && preg_match('/bigint/i', $meta['Type']) ? 'BIGINT(20)' : 'INT(11)';

    @mysqli_query($conn, "UPDATE `$safeTable` SET id = (SELECT next_id FROM (SELECT COALESCE(MAX(id), 0) + 1 next_id FROM `$safeTable` WHERE id <> 0) x) WHERE id = 0 LIMIT 1");

    $sql = "ALTER TABLE `$safeTable` MODIFY `id` $type NOT NULL AUTO_INCREMENT";
    if (mysqli_query($conn, $sql)) {
        $summary['fixed'][] = "AUTO_INCREMENT fixed: $table.id";
    } else {
        $summary['errors'][] = "AUTO_INCREMENT $table.id failed: " . mysqli_error($conn);
    }
}

try {
    repair_create_training_requests($conn, $summary);
    repair_create_application_workflow($conn, $summary);
    repair_create_training_schedule($conn, $summary);
    repair_create_training_session_workers($conn, $summary);

    $columns = [
        'contractors' => [
            'user_id' => 'INT NULL',
            'application_no' => 'VARCHAR(50) NULL',
            'status' => "VARCHAR(50) DEFAULT 'draft'",
            'is_blocked' => 'TINYINT(1) DEFAULT 0',
            'block_reason' => 'VARCHAR(255) NULL',
        ],
        'workmen' => [
            'contractor_id' => 'INT NULL',
            'training_status' => "VARCHAR(50) DEFAULT 'pending'",
            'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'training_requests' => [
            'workman_id' => 'INT NOT NULL',
            'contractor_id' => 'INT NOT NULL',
            'training_type' => 'VARCHAR(100) NULL',
            'requested_date' => 'DATE NULL',
            'preferred_date' => 'DATE NULL',
            'preferred_shift' => "VARCHAR(20) DEFAULT 'morning'",
            'scheduled_date' => 'DATE NULL',
            'scheduled_shift' => 'VARCHAR(20) NULL',
            'scheduled_venue' => 'VARCHAR(300) NULL',
            'scheduled_time' => 'VARCHAR(20) NULL',
            'safety_remarks' => 'TEXT NULL',
            'batch_number' => 'VARCHAR(100) NULL',
            'instructor' => 'VARCHAR(150) NULL',
            'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
            'scheduled_by' => 'INT NULL',
            'remarks' => 'TEXT NULL',
            'status' => "VARCHAR(50) DEFAULT 'pending'",
            'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'training_schedule' => [
            'session_date' => 'DATE NULL',
            'session_time' => 'TIME NULL',
            'location' => 'VARCHAR(255) NULL',
            'capacity' => 'INT DEFAULT 30',
            'enrolled_count' => 'INT DEFAULT 0',
            'trainer_name' => 'VARCHAR(100) NULL',
            'batch_number' => 'VARCHAR(50) NULL',
            'training_type' => "VARCHAR(50) DEFAULT 'induction'",
            'session_status' => "VARCHAR(50) DEFAULT 'open'",
            'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'training_session_workers' => [
            'session_id' => 'INT NOT NULL',
            'workman_id' => 'INT NOT NULL',
            'training_request_id' => 'INT NULL',
            'attendance_status' => "VARCHAR(20) DEFAULT 'pending'",
            'result' => "VARCHAR(20) DEFAULT 'pending'",
            'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'application_workflow' => [
            'application_id' => 'VARCHAR(50) NULL',
            'contractor_id' => 'INT NULL',
            'current_stage' => 'VARCHAR(100) NULL',
            'training_status' => 'VARCHAR(50) NULL',
            'overall_status' => 'VARCHAR(50) NULL',
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'annexure2a' => [
            'application_id' => 'VARCHAR(50) NULL',
            'contractor_id' => 'INT NULL',
            'workflow_status' => "VARCHAR(50) DEFAULT 'draft'",
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'documents' => [
            'contractor_id' => 'INT NULL',
            'workman_id' => 'INT NULL',
            'application_id' => 'VARCHAR(50) NULL',
            'document_type' => 'VARCHAR(100) NULL',
            'doc_type' => 'VARCHAR(100) NULL',
            'file_path' => 'VARCHAR(255) NULL',
            'original_name' => 'VARCHAR(255) NULL',
            'status' => "VARCHAR(50) DEFAULT 'pending'",
            'uploaded_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'contractor_documents' => [
            'contractor_id' => 'INT NULL',
            'annexure3a_id' => 'INT NULL',
            'doc_type' => 'VARCHAR(100) NULL',
            'file_path' => 'VARCHAR(255) NULL',
            'original_name' => 'VARCHAR(255) NULL',
            'status' => "VARCHAR(50) DEFAULT 'pending'",
            'uploaded_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'gate_pass_request_workers' => [
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'sap_logs' => [
            'activity' => 'TEXT NULL',
            'status' => 'VARCHAR(50) NULL',
            'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
    ];

    foreach ($columns as $table => $defs) {
        foreach ($defs as $column => $definition) {
            repair_ensure_column($conn, $table, $column, $definition, $summary);
        }
    }

    $dbRow = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT DATABASE() db'));
    $schema = mysqli_real_escape_string($conn, $dbRow['db']);
    $res = mysqli_query($conn, "SELECT table_name FROM information_schema.columns WHERE table_schema = '$schema' AND column_name = 'id'");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            repair_fix_auto_increment($conn, $row['table_name'], $summary);
        }
    }

    $summary['success'] = count($summary['errors']) === 0;
    repair_json($summary);
} catch (Throwable $e) {
    $summary['success'] = false;
    $summary['errors'][] = $e->getMessage();
    repair_json($summary);
}
