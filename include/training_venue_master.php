<?php

function clms_training_venue_column_exists($conn, $column) {
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `training_venue_masters` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

function clms_ensure_training_venue_masters($conn) {
    $created = clms_db_query($conn, "CREATE TABLE IF NOT EXISTS training_venue_masters (
        id INT NOT NULL AUTO_INCREMENT,
        venue_name VARCHAR(300) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_venue_name (venue_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created) return false;

    foreach ([
        'venue_name' => "ALTER TABLE `training_venue_masters` ADD COLUMN `venue_name` VARCHAR(300) NOT NULL AFTER `id`",
        'status' => "ALTER TABLE `training_venue_masters` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `venue_name`",
        'created_by' => "ALTER TABLE `training_venue_masters` ADD COLUMN `created_by` INT NULL AFTER `status`",
        'created_at' => "ALTER TABLE `training_venue_masters` ADD COLUMN `created_at` DATETIME NULL AFTER `created_by`",
        'updated_at' => "ALTER TABLE `training_venue_masters` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`",
    ] as $column => $sql) {
        if (!clms_training_venue_column_exists($conn, $column)) {
            clms_db_query($conn, $sql);
        }
    }

    if (db_count($conn, "SELECT COUNT(*) FROM training_venue_masters") === 0) {
        foreach (['Safety Induction Hall A', 'Training Center - Block B', 'Main Conference Hall', 'On-Site Briefing Zone'] as $venue) {
            db_execute($conn, "INSERT IGNORE INTO training_venue_masters (venue_name, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())", 's', [$venue]);
        }
    }
    return true;
}

function clms_get_training_venue_rows($conn, $activeOnly = true) {
    if (!clms_ensure_training_venue_masters($conn)) return [];
    $where = $activeOnly ? "WHERE LOWER(status) = 'active'" : "";
    return db_fetch_all($conn, "SELECT id, venue_name, status, created_at FROM training_venue_masters $where ORDER BY venue_name ASC");
}

function clms_training_venue_is_active($conn, $venueName) {
    if (!clms_ensure_training_venue_masters($conn)) return false;
    $row = db_single(
        $conn,
        "SELECT id FROM training_venue_masters WHERE LOWER(status) = 'active' AND LOWER(TRIM(venue_name)) = LOWER(TRIM(?)) LIMIT 1",
        's',
        [trim((string)$venueName)]
    );
    return (bool)$row;
}

function clms_add_training_venue($conn, $venueName, $userId = 0) {
    if (!clms_ensure_training_venue_masters($conn)) {
        throw new RuntimeException('Training venue master table could not be initialized.');
    }
    $venueName = trim((string)$venueName);
    if ($venueName === '') {
        throw new InvalidArgumentException('Venue name is required.');
    }
    db_execute(
        $conn,
        "INSERT INTO training_venue_masters (venue_name, status, created_by, created_at, updated_at)
         VALUES (?, 'active', ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()",
        'si',
        [$venueName, (int)$userId]
    );
}

function clms_inactivate_training_venue($conn, $id) {
    if (!clms_ensure_training_venue_masters($conn)) return;
    db_execute($conn, "UPDATE training_venue_masters SET status = 'inactive', updated_at = NOW() WHERE id = ?", 'i', [(int)$id]);
}
