<?php

function clms_temp_validity_column_exists($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `temporary_pass_validities` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_temp_validity_table_exists($conn) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'temporary_pass_validities'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_ensure_temporary_pass_validities($conn) {
    $created = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS temporary_pass_validities (
        id INT NOT NULL AUTO_INCREMENT,
        validity_days INT NOT NULL DEFAULT 7,
        validity_from_date DATE NOT NULL,
        validity_to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        INDEX idx_temp_validity_status_dates (status, validity_from_date, validity_to_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created || !clms_temp_validity_table_exists($conn)) {
        return false;
    }

    $columns = [
        'validity_days' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `validity_days` INT NOT NULL DEFAULT 7 AFTER `id`",
        'validity_from_date' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `validity_from_date` DATE NOT NULL AFTER `validity_days`",
        'validity_to_date' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `validity_to_date` DATE NOT NULL DEFAULT '9999-12-31' AFTER `validity_from_date`",
        'status' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `validity_to_date`",
        'created_by' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `created_by` INT NULL AFTER `status`",
        'created_at' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `created_at` DATETIME NULL AFTER `created_by`",
        'updated_at' => "ALTER TABLE `temporary_pass_validities` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`",
    ];
    foreach ($columns as $column => $sql) {
        if (!clms_temp_validity_column_exists($conn, $column)) {
            mysqli_query($conn, $sql);
        }
    }

    $count = db_count($conn, "SELECT COUNT(*) FROM temporary_pass_validities");
    if ($count === 0) {
        $legacy = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'temp_pass_validity_days' LIMIT 1");
        $days = max(1, (int)($legacy['setting_value'] ?? 7));
        db_execute(
            $conn,
            "INSERT INTO temporary_pass_validities (validity_days, validity_from_date, validity_to_date, status, created_at, updated_at)
             VALUES (?, CURDATE(), '9999-12-31', 'active', NOW(), NOW())",
            'i',
            [$days]
        );
    }

    return true;
}

function clms_get_temporary_pass_validity_rows($conn) {
    if (!clms_ensure_temporary_pass_validities($conn)) return [];
    return db_fetch_all(
        $conn,
        "SELECT id, validity_days, validity_from_date, validity_to_date, status, created_at
         FROM temporary_pass_validities
         ORDER BY validity_from_date DESC, id DESC"
    );
}

function clms_get_temporary_pass_validity_days($conn, $asOfDate = null) {
    if (!clms_ensure_temporary_pass_validities($conn)) return 7;
    $asOfDate = $asOfDate ?: date('Y-m-d');
    $row = db_single(
        $conn,
        "SELECT validity_days
         FROM temporary_pass_validities
         WHERE LOWER(status) = 'active'
           AND validity_from_date <= ?
           AND validity_to_date >= ?
         ORDER BY validity_from_date DESC, id DESC
         LIMIT 1",
        'ss',
        [$asOfDate, $asOfDate]
    );
    return max(1, (int)($row['validity_days'] ?? 7));
}

function clms_add_temporary_pass_validity($conn, $days, $fromDate, $toDate, $userId = 0) {
    if (!clms_ensure_temporary_pass_validities($conn)) {
        throw new RuntimeException('Temporary pass validity table could not be created.');
    }

    $days = (int)$days;
    if ($days < 1) {
        throw new InvalidArgumentException('Validity days must be at least 1.');
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
        "UPDATE temporary_pass_validities
         SET status = 'inactive',
             validity_to_date = CASE WHEN validity_from_date <= ? AND validity_to_date >= ? THEN ? ELSE validity_to_date END,
             updated_at = NOW()
         WHERE LOWER(status) = 'active'",
        'sss',
        [$fromDate, $fromDate, $previousToDate]
    );

    db_execute(
        $conn,
        "INSERT INTO temporary_pass_validities (validity_days, validity_from_date, validity_to_date, status, created_by, created_at, updated_at)
         VALUES (?, ?, ?, 'active', ?, NOW(), NOW())",
        'issi',
        [$days, $fromDate, $toDate, (int)$userId]
    );

    db_execute(
        $conn,
        "INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_by, updated_at)
         VALUES ('temp_pass_validity_days', ?, 'pass', 'Current temporary pass validity in days', ?, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()",
        'si',
        [(string)$days, (int)$userId]
    );
}
