<?php

function clms_labour_threshold_column_exists($conn, $column) {
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `labour_license_thresholds` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_labour_threshold_table_exists($conn) {
    $result = clms_db_query($conn, "SHOW TABLES LIKE 'labour_license_thresholds'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_ensure_labour_license_thresholds($conn) {
    $created = clms_db_query($conn, "CREATE TABLE IF NOT EXISTS labour_license_thresholds (
        id INT NOT NULL AUTO_INCREMENT,
        threshold_value INT NOT NULL DEFAULT 20,
        threshold_from_date DATE NOT NULL,
        threshold_to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        INDEX idx_threshold_status_dates (status, threshold_from_date, threshold_to_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created || !clms_labour_threshold_table_exists($conn)) {
        return false;
    }

    $columns = [
        'threshold_value' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `threshold_value` INT NOT NULL DEFAULT 20 AFTER `id`",
        'threshold_from_date' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `threshold_from_date` DATE NOT NULL AFTER `threshold_value`",
        'threshold_to_date' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `threshold_to_date` DATE NOT NULL DEFAULT '9999-12-31' AFTER `threshold_from_date`",
        'status' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `threshold_to_date`",
        'created_by' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `created_by` INT NULL AFTER `status`",
        'created_at' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `created_at` DATETIME NULL AFTER `created_by`",
        'updated_at' => "ALTER TABLE `labour_license_thresholds` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`",
    ];
    foreach ($columns as $column => $sql) {
        if (!clms_labour_threshold_column_exists($conn, $column)) {
            clms_db_query($conn, $sql);
        }
    }

    $count = db_count($conn, "SELECT COUNT(*) FROM labour_license_thresholds");
    if ($count === 0) {
        $legacy = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'labour_license_threshold' LIMIT 1");
        $value = max(1, (int)($legacy['setting_value'] ?? 20));
        db_execute(
            $conn,
            "INSERT INTO labour_license_thresholds (threshold_value, threshold_from_date, threshold_to_date, status, created_at, updated_at)
             VALUES (?, CURDATE(), '9999-12-31', 'active', NOW(), NOW())",
            'i',
            [$value]
        );
    }

    return true;
}

function clms_get_labour_license_threshold_rows($conn) {
    if (!clms_ensure_labour_license_thresholds($conn)) return [];
    return db_fetch_all(
        $conn,
        "SELECT id, threshold_value, threshold_from_date, threshold_to_date, status, created_at
         FROM labour_license_thresholds
         ORDER BY threshold_from_date DESC, id DESC"
    );
}

function clms_get_labour_license_threshold($conn, $asOfDate = null) {
    if (!clms_ensure_labour_license_thresholds($conn)) return 20;
    $asOfDate = $asOfDate ?: date('Y-m-d');
    $row = db_single(
        $conn,
        "SELECT threshold_value
         FROM labour_license_thresholds
         WHERE LOWER(status) = 'active'
           AND threshold_from_date <= ?
           AND threshold_to_date >= ?
         ORDER BY threshold_from_date DESC, id DESC
         LIMIT 1",
        'ss',
        [$asOfDate, $asOfDate]
    );
    return max(1, (int)($row['threshold_value'] ?? 20));
}

function clms_add_labour_license_threshold($conn, $threshold, $fromDate, $toDate, $userId = 0) {
    if (!clms_ensure_labour_license_thresholds($conn)) {
        throw new RuntimeException('Labour license threshold table could not be created.');
    }

    $threshold = (int)$threshold;
    if ($threshold < 1) {
        throw new InvalidArgumentException('Threshold must be at least 1.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fromDate)) {
        throw new InvalidArgumentException('Please enter a valid From Date.');
    }
    $toDate = trim((string)$toDate) !== '' ? $toDate : '9999-12-31';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$toDate)) {
        throw new InvalidArgumentException('Please enter a valid To Date.');
    }
    if (strtotime($toDate) < strtotime($fromDate)) {
        throw new InvalidArgumentException('To Date cannot be before From Date.');
    }

    $previousToDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));
    db_execute(
        $conn,
        "UPDATE labour_license_thresholds
         SET status = 'inactive',
             threshold_to_date = CASE WHEN threshold_from_date <= ? AND threshold_to_date >= ? THEN ? ELSE threshold_to_date END,
             updated_at = NOW()
         WHERE LOWER(status) = 'active'",
        'sss',
        [$fromDate, $fromDate, $previousToDate]
    );

    db_execute(
        $conn,
        "INSERT INTO labour_license_thresholds (threshold_value, threshold_from_date, threshold_to_date, status, created_by, created_at, updated_at)
         VALUES (?, ?, ?, 'active', ?, NOW(), NOW())",
        'issi',
        [$threshold, $fromDate, $toDate, (int)$userId]
    );

    db_execute(
        $conn,
        "INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_by, updated_at)
         VALUES ('labour_license_threshold', ?, 'welfare', 'Current worker count requiring labour licence', ?, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()",
        'si',
        [(string)$threshold, (int)$userId]
    );
}
