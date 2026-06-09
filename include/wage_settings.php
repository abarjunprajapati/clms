<?php

function clms_wage_setting_column_exists($conn, $column) {
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `system_settings` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_wage_setting_table_exists($conn) {
    $result = clms_db_query($conn, "SHOW TABLES LIKE 'system_settings'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_wage_setting_id_is_auto_increment($conn) {
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `system_settings` LIKE 'id'");
    $row = $result ? clms_db_fetch_assoc($result) : null;
    return $row && stripos($row['Extra'] ?? '', 'auto_increment') !== false;
}

function clms_wage_setting_next_id($conn) {
    $result = clms_db_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `system_settings`");
    $row = $result ? clms_db_fetch_assoc($result) : null;
    return (int)($row['next_id'] ?? 1);
}

function clms_ensure_wage_settings($conn) {
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS system_settings (
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
            clms_db_query($conn, $sql);
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

function clms_normalize_wage_category($category) {
    $value = strtolower(trim((string)$category));
    $value = str_replace(['_', '-'], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    if ($value === 'skilled') return 'Skilled';
    if ($value === 'semi skilled' || $value === 'semiskilled') return 'Semi-Skilled';
    if ($value === 'unskilled' || $value === 'un skilled') return 'Unskilled';
    return '';
}

function clms_wage_rate_column_exists($conn, $column) {
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `certified_wage_rates` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_wage_rate_table_exists($conn) {
    $result = clms_db_query($conn, "SHOW TABLES LIKE 'certified_wage_rates'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_wage_rate_id_is_auto_increment($conn) {
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `certified_wage_rates` LIKE 'id'");
    $row = $result ? clms_db_fetch_assoc($result) : null;
    return $row && stripos($row['Extra'] ?? '', 'auto_increment') !== false;
}

function clms_wage_rate_next_id($conn) {
    $row = db_single($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM certified_wage_rates");
    return (int)($row['next_id'] ?? 1);
}

function clms_ensure_certified_wage_rates($conn) {
    $created = clms_db_query($conn, "CREATE TABLE IF NOT EXISTS certified_wage_rates (
        id INT NOT NULL AUTO_INCREMENT,
        category VARCHAR(50) NOT NULL,
        wage_from_date DATE NOT NULL,
        wage_to_date DATE NOT NULL DEFAULT '9999-12-31',
        wage_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        INDEX idx_category_status_dates (category, status, wage_from_date, wage_to_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created || !clms_wage_rate_table_exists($conn)) {
        return false;
    }

    $columns = [
        'category' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `category` VARCHAR(50) NOT NULL AFTER `id`",
        'wage_from_date' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `wage_from_date` DATE NOT NULL AFTER `category`",
        'wage_to_date' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `wage_to_date` DATE NOT NULL DEFAULT '9999-12-31' AFTER `wage_from_date`",
        'wage_rate' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `wage_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `wage_to_date`",
        'status' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `wage_rate`",
        'created_by' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `created_by` INT NULL AFTER `status`",
        'created_at' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `created_at` DATETIME NULL AFTER `created_by`",
        'updated_at' => "ALTER TABLE `certified_wage_rates` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`",
    ];

    foreach ($columns as $column => $sql) {
        if (!clms_wage_rate_column_exists($conn, $column)) {
            clms_db_query($conn, $sql);
        }
    }

    return true;
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

function clms_get_certified_wage_rates($conn) {
    if (!clms_ensure_certified_wage_rates($conn)) return [];
    return db_fetch_all(
        $conn,
        "SELECT id, category, wage_from_date, wage_to_date, wage_rate, status, created_at
         FROM certified_wage_rates
         ORDER BY FIELD(category, 'Skilled', 'Semi-Skilled', 'Unskilled'), wage_from_date DESC, id DESC"
    );
}

function clms_get_active_certified_wage_map($conn, $asOfDate = null) {
    if (!clms_ensure_certified_wage_rates($conn)) return [];
    $asOfDate = $asOfDate ?: date('Y-m-d');
    $rows = db_fetch_all(
        $conn,
        "SELECT id, category, wage_from_date, wage_to_date, wage_rate, status
         FROM certified_wage_rates
         WHERE LOWER(status) = 'active'
           AND wage_from_date <= ?
           AND wage_to_date >= ?
         ORDER BY FIELD(category, 'Skilled', 'Semi-Skilled', 'Unskilled'), wage_from_date DESC, id DESC",
        'ss',
        [$asOfDate, $asOfDate]
    );
    $map = [];
    foreach ($rows as $row) {
        $category = clms_normalize_wage_category($row['category'] ?? '');
        if ($category !== '' && !isset($map[$category])) {
            $row['category'] = $category;
            $row['wage_rate'] = (float)$row['wage_rate'];
            $map[$category] = $row;
        }
    }
    return $map;
}

function clms_get_active_certified_wage_for_category($conn, $category, $asOfDate = null) {
    $category = clms_normalize_wage_category($category);
    if ($category === '') return null;
    $map = clms_get_active_certified_wage_map($conn, $asOfDate);
    return $map[$category] ?? null;
}

function clms_add_certified_wage_rate($conn, $category, $fromDate, $toDate, $wageRate, $userId = 0) {
    if (!clms_ensure_certified_wage_rates($conn)) {
        throw new RuntimeException('Certified wage table could not be created. Please check database permissions.');
    }
    $category = clms_normalize_wage_category($category);
    if ($category === '') {
        throw new InvalidArgumentException('Please select a valid wage category.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fromDate)) {
        throw new InvalidArgumentException('Please enter a valid Wage From Date.');
    }
    $toDate = trim((string)$toDate) !== '' ? $toDate : '9999-12-31';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$toDate)) {
        throw new InvalidArgumentException('Please enter a valid Wage To Date.');
    }
    if (strtotime($toDate) < strtotime($fromDate)) {
        throw new InvalidArgumentException('Wage To Date cannot be before Wage From Date.');
    }

    $wageRate = max(0, (float)$wageRate);
    if ($wageRate <= 0) {
        throw new InvalidArgumentException('Please enter a valid wage rate.');
    }

    $previousToDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));
    db_execute(
        $conn,
        "UPDATE certified_wage_rates
         SET status = 'inactive',
             wage_to_date = CASE WHEN wage_from_date <= ? AND wage_to_date >= ? THEN ? ELSE wage_to_date END
         WHERE category = ?
           AND LOWER(status) = 'active'",
        'ssss',
        [$fromDate, $fromDate, $previousToDate, $category]
    );

    if (clms_wage_rate_id_is_auto_increment($conn)) {
        db_execute(
            $conn,
            "INSERT INTO certified_wage_rates (category, wage_from_date, wage_to_date, wage_rate, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'active', ?, NOW(), NOW())",
            'sssdi',
            [$category, $fromDate, $toDate, $wageRate, (int)$userId]
        );
    } else {
        db_execute(
            $conn,
            "INSERT INTO certified_wage_rates (id, category, wage_from_date, wage_to_date, wage_rate, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())",
            'isssdi',
            [clms_wage_rate_next_id($conn), $category, $fromDate, $toDate, $wageRate, (int)$userId]
        );
    }

    return clms_get_active_certified_wage_map($conn);
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
