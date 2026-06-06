<?php

function clms_age_range_column_exists($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `age_range_mappings` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function clms_ensure_age_range_mappings($conn) {
    $created = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS age_range_mappings (
        id INT NOT NULL AUTO_INCREMENT,
        min_age INT NOT NULL DEFAULT 18,
        max_age INT NOT NULL DEFAULT 60,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        effective_from DATE NOT NULL,
        effective_to DATE NOT NULL DEFAULT '9999-12-31',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        INDEX idx_age_range_active (status, effective_from, effective_to)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$created) return false;

    $columns = [
        'min_age' => "ALTER TABLE `age_range_mappings` ADD COLUMN `min_age` INT NOT NULL DEFAULT 18 AFTER `id`",
        'max_age' => "ALTER TABLE `age_range_mappings` ADD COLUMN `max_age` INT NOT NULL DEFAULT 60 AFTER `min_age`",
        'status' => "ALTER TABLE `age_range_mappings` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `max_age`",
        'effective_from' => "ALTER TABLE `age_range_mappings` ADD COLUMN `effective_from` DATE NOT NULL AFTER `status`",
        'effective_to' => "ALTER TABLE `age_range_mappings` ADD COLUMN `effective_to` DATE NOT NULL DEFAULT '9999-12-31' AFTER `effective_from`",
        'created_by' => "ALTER TABLE `age_range_mappings` ADD COLUMN `created_by` INT NULL AFTER `effective_to`",
        'created_at' => "ALTER TABLE `age_range_mappings` ADD COLUMN `created_at` DATETIME NULL AFTER `created_by`",
        'updated_at' => "ALTER TABLE `age_range_mappings` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`",
    ];
    foreach ($columns as $column => $sql) {
        if (!clms_age_range_column_exists($conn, $column)) {
            mysqli_query($conn, $sql);
        }
    }

    if (db_count($conn, "SELECT COUNT(*) FROM age_range_mappings") === 0) {
        db_execute(
            $conn,
            "INSERT INTO age_range_mappings (min_age, max_age, status, effective_from, effective_to, created_at, updated_at)
             VALUES (18, 60, 'active', CURDATE(), '9999-12-31', NOW(), NOW())"
        );
    }

    return true;
}

function clms_get_age_range_rows($conn) {
    if (!clms_ensure_age_range_mappings($conn)) return [];
    return db_fetch_all(
        $conn,
        "SELECT id, min_age, max_age, status, effective_from, effective_to, created_at
         FROM age_range_mappings
         ORDER BY effective_from DESC, id DESC"
    );
}

function clms_get_active_age_range($conn, $asOfDate = null) {
    if (!clms_ensure_age_range_mappings($conn)) {
        return ['min_age' => 18, 'max_age' => 60];
    }
    $asOfDate = $asOfDate ?: date('Y-m-d');
    $row = db_single(
        $conn,
        "SELECT min_age, max_age
         FROM age_range_mappings
         WHERE LOWER(status) = 'active'
           AND effective_from <= ?
           AND effective_to >= ?
         ORDER BY effective_from DESC, id DESC
         LIMIT 1",
        'ss',
        [$asOfDate, $asOfDate]
    );
    return [
        'min_age' => max(0, (int)($row['min_age'] ?? 18)),
        'max_age' => max(1, (int)($row['max_age'] ?? 60)),
    ];
}

function clms_add_age_range_mapping($conn, $minAge, $maxAge, $fromDate, $toDate, $userId = 0) {
    if (!clms_ensure_age_range_mappings($conn)) {
        throw new RuntimeException('Age range mapping table could not be created.');
    }
    $minAge = (int)$minAge;
    $maxAge = (int)$maxAge;
    if ($minAge < 0 || $maxAge < 1 || $minAge > $maxAge) {
        throw new InvalidArgumentException('Please enter a valid min/max age range.');
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
        "UPDATE age_range_mappings
         SET status = 'inactive',
             effective_to = CASE WHEN effective_from <= ? AND effective_to >= ? THEN ? ELSE effective_to END,
             updated_at = NOW()
         WHERE LOWER(status) = 'active'",
        'sss',
        [$fromDate, $fromDate, $previousToDate]
    );

    db_execute(
        $conn,
        "INSERT INTO age_range_mappings (min_age, max_age, status, effective_from, effective_to, created_by, created_at, updated_at)
         VALUES (?, ?, 'active', ?, ?, ?, NOW(), NOW())",
        'iissi',
        [$minAge, $maxAge, $fromDate, $toDate, (int)$userId]
    );
}
