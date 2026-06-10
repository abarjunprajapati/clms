<?php

function clms_nlm_query($conn, $sql) {
    try {
        return @mysqli_query($conn, $sql);
    } catch (Throwable $e) {
        error_log('[NATIONALITY_MASTER] ' . $e->getMessage());
        return false;
    }
}

function clms_nlm_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = clms_nlm_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_nlm_column_exists($conn, $table, $column) {
    $table = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = clms_nlm_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_ensure_nationality_location_masters($conn) {
    $ok1 = clms_nlm_query($conn, "CREATE TABLE IF NOT EXISTS master_nationalities (
        id INT NOT NULL AUTO_INCREMENT,
        nationality VARCHAR(100) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ok2 = clms_nlm_query($conn, "CREATE TABLE IF NOT EXISTS master_religions (
        id INT NOT NULL AUTO_INCREMENT,
        religion VARCHAR(100) NOT NULL UNIQUE,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ok3 = clms_nlm_query($conn, "CREATE TABLE IF NOT EXISTS master_state_districts (
        id INT NOT NULL AUTO_INCREMENT,
        state_name VARCHAR(120) NOT NULL,
        district_name VARCHAR(120) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_state_district (state_name, district_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!$ok1 || !$ok2 || !$ok3) {
        return false;
    }

    $columns = [
        'master_nationalities' => [
            'nationality' => "ALTER TABLE `master_nationalities` ADD COLUMN `nationality` VARCHAR(100) NOT NULL AFTER `id`",
            'status' => "ALTER TABLE `master_nationalities` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active'",
            'created_at' => "ALTER TABLE `master_nationalities` ADD COLUMN `created_at` DATETIME NULL",
            'updated_at' => "ALTER TABLE `master_nationalities` ADD COLUMN `updated_at` DATETIME NULL",
        ],
        'master_religions' => [
            'religion' => "ALTER TABLE `master_religions` ADD COLUMN `religion` VARCHAR(100) NOT NULL AFTER `id`",
            'status' => "ALTER TABLE `master_religions` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active'",
            'created_at' => "ALTER TABLE `master_religions` ADD COLUMN `created_at` DATETIME NULL",
            'updated_at' => "ALTER TABLE `master_religions` ADD COLUMN `updated_at` DATETIME NULL",
        ],
        'master_state_districts' => [
            'state_name' => "ALTER TABLE `master_state_districts` ADD COLUMN `state_name` VARCHAR(120) NOT NULL AFTER `id`",
            'district_name' => "ALTER TABLE `master_state_districts` ADD COLUMN `district_name` VARCHAR(120) NOT NULL AFTER `state_name`",
            'status' => "ALTER TABLE `master_state_districts` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active'",
            'created_at' => "ALTER TABLE `master_state_districts` ADD COLUMN `created_at` DATETIME NULL",
            'updated_at' => "ALTER TABLE `master_state_districts` ADD COLUMN `updated_at` DATETIME NULL",
        ],
    ];
    foreach ($columns as $table => $tableColumns) {
        foreach ($tableColumns as $column => $sql) {
            if (!clms_nlm_column_exists($conn, $table, $column)) {
                clms_nlm_query($conn, $sql);
            }
        }
    }

    if (db_count($conn, "SELECT COUNT(*) FROM master_nationalities") === 0) {
        foreach (['Indian', 'Nepalese', 'Bangladeshi', 'Sri Lankan', 'American', 'British'] as $nationality) {
            db_execute($conn, "INSERT IGNORE INTO master_nationalities (nationality, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())", 's', [$nationality]);
        }
    }

    if (db_count($conn, "SELECT COUNT(*) FROM master_religions") === 0) {
        foreach (['Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist', 'Jain', 'Other'] as $religion) {
            db_execute($conn, "INSERT IGNORE INTO master_religions (religion, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())", 's', [$religion]);
        }
    }

    if (db_count($conn, "SELECT COUNT(*) FROM master_state_districts") === 0) {
        $defaults = [
            'Kerala' => ['Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod', 'Kollam', 'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad', 'Pathanamthitta', 'Thiruvananthapuram', 'Thrissur', 'Wayanad'],
            'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Salem', 'Tiruchirappalli', 'Tirunelveli'],
            'Karnataka' => ['Bengaluru Urban', 'Dakshina Kannada', 'Mysuru', 'Udupi'],
            'Maharashtra' => ['Mumbai City', 'Mumbai Suburban', 'Nagpur', 'Pune', 'Thane'],
            'Delhi' => ['Central Delhi', 'New Delhi', 'South Delhi'],
        ];
        foreach ($defaults as $state => $districts) {
            foreach ($districts as $district) {
                db_execute($conn, "INSERT IGNORE INTO master_state_districts (state_name, district_name, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())", 'ss', [$state, $district]);
            }
        }
    }

    return true;
}

function clms_get_nationality_options($conn) {
    if (!clms_ensure_nationality_location_masters($conn)) return ['Indian'];
    $rows = db_fetch_all($conn, "SELECT nationality FROM master_nationalities WHERE LOWER(status) = 'active' ORDER BY nationality ASC");
    return array_map(function($row) {
        return $row['nationality'];
    }, $rows);
}

function clms_get_religion_options($conn) {
    if (!clms_ensure_nationality_location_masters($conn)) return ['Hindu', 'Muslim', 'Christian', 'Other'];
    $rows = db_fetch_all($conn, "SELECT religion FROM master_religions WHERE LOWER(status) = 'active' ORDER BY religion ASC");
    return array_map(function($row) {
        return $row['religion'];
    }, $rows);
}

function clms_get_state_district_map($conn) {
    if (!clms_ensure_nationality_location_masters($conn)) return [];
    $rows = db_fetch_all($conn, "SELECT state_name, district_name FROM master_state_districts WHERE LOWER(status) = 'active' ORDER BY state_name ASC, district_name ASC");
    $map = [];
    foreach ($rows as $row) {
        $state = trim((string)$row['state_name']);
        $district = trim((string)$row['district_name']);
        if ($state === '' || $district === '') continue;
        if (!isset($map[$state])) $map[$state] = [];
        $map[$state][] = $district;
    }
    return $map;
}
