<?php

function clms_wage_setting_column_exists($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `system_settings` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_wage_setting_table_exists($conn) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_wage_setting_id_is_auto_increment($conn) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `system_settings` LIKE 'id'");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row && stripos($row['Extra'] ?? '', 'auto_increment') !== false;
}

function clms_wage_setting_next_id($conn) {
    $result = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `system_settings`");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int)($row['next_id'] ?? 1);
}

function clms_ensure_wage_settings($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS system_settings (
        id INT NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_group VARCHAR(50) DEFAULT 'general',
        description TEXT,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'setting_key' => "ALTER TABLE `system_settings` ADD COLUMN `setting_key` VARCHAR(100) NOT NULL UNIQUE AFTER `id`",
        'setting_value' => "ALTER TABLE `system_settings` ADD COLUMN `setting_value` TEXT AFTER `setting_key`",
        'setting_group' => "ALTER TABLE `system_settings` ADD COLUMN `setting_group` VARCHAR(50) DEFAULT 'general' AFTER `setting_value`",
        'description' => "ALTER TABLE `system_settings` ADD COLUMN `description` TEXT AFTER `setting_group`",
        'updated_by' => "ALTER TABLE `system_settings` ADD COLUMN `updated_by` INT NULL AFTER `description`",
        'updated_at' => "ALTER TABLE `system_settings` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `updated_by`",
    ];

    foreach ($columns as $column => $sql) {
        if (!clms_wage_setting_column_exists($conn, $column)) {
            mysqli_query($conn, $sql);
        }
    }

    $existing = db_single($conn, "SELECT setting_key FROM system_settings WHERE setting_key = 'minimum_certified_wage_rate' LIMIT 1");
    if ($existing) {
        return;
    }

    $value = '0';
    $group = 'welfare';
    $description = 'Minimum certified wage rate allowed during worker enrolment';
    if (clms_wage_setting_id_is_auto_increment($conn)) {
        db_execute(
            $conn,
            "INSERT INTO system_settings (setting_key, setting_value, setting_group, description) VALUES ('minimum_certified_wage_rate', ?, ?, ?)",
            'sss',
            [$value, $group, $description]
        );
        return;
    }

    db_execute(
        $conn,
        "INSERT INTO system_settings (id, setting_key, setting_value, setting_group, description) VALUES (?, 'minimum_certified_wage_rate', ?, ?, ?)",
        'isss',
        [clms_wage_setting_next_id($conn), $value, $group, $description]
    );
}

function clms_get_minimum_certified_wage($conn) {
    clms_ensure_wage_settings($conn);
    $row = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'minimum_certified_wage_rate' LIMIT 1");
    return max(0, (float)($row['setting_value'] ?? 0));
}

function clms_set_minimum_certified_wage($conn, $value, $userId = 0) {
    clms_ensure_wage_settings($conn);
    $value = max(0, (float)$value);
    db_execute(
        $conn,
        "UPDATE system_settings SET setting_value = ?, setting_group = 'welfare', description = 'Minimum certified wage rate allowed during worker enrolment', updated_by = ? WHERE setting_key = 'minimum_certified_wage_rate'",
        'si',
        [(string)$value, (int)$userId]
    );
    return $value;
}

function clms_parse_wage_amount($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $normalized = preg_replace('/[^0-9.]/', '', $value);
    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }
    return (float)$normalized;
}
