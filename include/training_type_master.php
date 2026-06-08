<?php

function clms_training_type_column_exists($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `master_training_types` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_ensure_training_type_master($conn) {
    $created = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_training_types (
        id INT NOT NULL AUTO_INCREMENT,
        type_name VARCHAR(100) NOT NULL,
        duration_hours INT DEFAULT 8,
        pass_mark INT DEFAULT 60,
        description VARCHAR(255) NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_type_name (type_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created) return false;

    foreach ([
        'type_name' => "ALTER TABLE `master_training_types` ADD COLUMN `type_name` VARCHAR(100) NOT NULL AFTER `id`",
        'duration_hours' => "ALTER TABLE `master_training_types` ADD COLUMN `duration_hours` INT DEFAULT 8 AFTER `type_name`",
        'pass_mark' => "ALTER TABLE `master_training_types` ADD COLUMN `pass_mark` INT DEFAULT 60 AFTER `duration_hours`",
        'description' => "ALTER TABLE `master_training_types` ADD COLUMN `description` VARCHAR(255) NULL AFTER `pass_mark`",
        'status' => "ALTER TABLE `master_training_types` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' AFTER `description`",
        'created_at' => "ALTER TABLE `master_training_types` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`",
    ] as $column => $sql) {
        if (!clms_training_type_column_exists($conn, $column)) {
            mysqli_query($conn, $sql);
        }
    }

    if (db_count($conn, "SELECT COUNT(*) FROM master_training_types") === 0) {
        foreach (['Safety Induction', 'Fire Safety', 'Height Work', 'Confined Space', 'Electrical Safety', 'Chemical Handling'] as $type) {
            db_execute($conn, "INSERT IGNORE INTO master_training_types (type_name, status) VALUES (?, 'active')", 's', [$type]);
        }
    }
    return true;
}

function clms_get_training_type_rows($conn, $activeOnly = true) {
    if (!clms_ensure_training_type_master($conn)) return [];
    $where = $activeOnly ? "WHERE LOWER(status) = 'active'" : "";
    return db_fetch_all($conn, "SELECT id, type_name, duration_hours, pass_mark, status FROM master_training_types $where ORDER BY type_name ASC");
}

function clms_training_type_is_active($conn, $typeName) {
    if (!clms_ensure_training_type_master($conn)) return false;
    $row = db_single(
        $conn,
        "SELECT id FROM master_training_types WHERE LOWER(status) = 'active' AND LOWER(TRIM(type_name)) = LOWER(TRIM(?)) LIMIT 1",
        's',
        [trim((string)$typeName)]
    );
    return (bool)$row;
}

function clms_add_training_type($conn, $typeName, $durationHours = 8, $passMark = 60) {
    if (!clms_ensure_training_type_master($conn)) {
        throw new RuntimeException('Training type master table could not be initialized.');
    }
    $typeName = trim((string)$typeName);
    if ($typeName === '') {
        throw new InvalidArgumentException('Training type is required.');
    }
    db_execute(
        $conn,
        "INSERT INTO master_training_types (type_name, duration_hours, pass_mark, status)
         VALUES (?, ?, ?, 'active')
         ON DUPLICATE KEY UPDATE duration_hours = VALUES(duration_hours), pass_mark = VALUES(pass_mark), status = 'active'",
        'sii',
        [$typeName, max(1, (int)$durationHours), max(0, (int)$passMark)]
    );
}

function clms_inactivate_training_type($conn, $id) {
    if (!clms_ensure_training_type_master($conn)) return;
    db_execute($conn, "UPDATE master_training_types SET status = 'inactive' WHERE id = ?", 'i', [(int)$id]);
}
