<?php

require_once __DIR__ . '/training_venue_master.php';
require_once __DIR__ . '/training_type_master.php';

function clms_safety_table_exists($conn, $table) {
    $safe = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$safe'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_safety_column_exists($conn, $table, $column) {
    if (!clms_safety_table_exists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && mysqli_num_rows($res) > 0;
}

function clms_safety_ensure_column($conn, $table, $column, $definition) {
    if (!clms_safety_table_exists($conn, $table) || clms_safety_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function clms_safety_ensure_index($conn, $table, $indexName, $sql) {
    if (!clms_safety_table_exists($conn, $table)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeIndex = mysqli_real_escape_string($conn, $indexName);
    $res = mysqli_query($conn, "SHOW INDEX FROM `$safeTable` WHERE Key_name = '$safeIndex'");
    if ($res && mysqli_num_rows($res) > 0) return;
    @mysqli_query($conn, $sql);
}

function clms_safety_ensure_control_schema($conn) {
    clms_ensure_training_venue_masters($conn);
    clms_ensure_training_type_master($conn);

    clms_safety_ensure_column($conn, 'training_venue_masters', 'venue_code', 'VARCHAR(30) NULL');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'seats', 'INT NOT NULL DEFAULT 35');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'training_venue_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    @mysqli_query($conn, "UPDATE training_venue_masters SET seats = 35 WHERE seats IS NULL OR seats <= 0");
    @mysqli_query($conn, "UPDATE training_venue_masters SET venue_code = CONCAT('LOC', LPAD(id, 3, '0')) WHERE COALESCE(TRIM(venue_code), '') = ''");
    clms_safety_ensure_index($conn, 'training_venue_masters', 'uq_training_venue_code', "ALTER TABLE training_venue_masters ADD UNIQUE KEY uq_training_venue_code (venue_code)");
    clms_safety_ensure_column($conn, 'workmen', 'safety_language', 'VARCHAR(50) NULL');
    clms_safety_ensure_column($conn, 'workmen', 'training_status', "VARCHAR(50) DEFAULT 'pending'");
    clms_safety_ensure_column($conn, 'workmen', 'safety_training_status', "VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
    clms_safety_ensure_column($conn, 'workmen', 'eligibility_status', "VARCHAR(50) DEFAULT 'NOT ELIGIBLE'");
    clms_safety_ensure_column($conn, 'workmen', 'training_valid_till', 'DATE NULL');
    clms_safety_ensure_column($conn, 'workmen', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'mobile', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'email', 'VARCHAR(120) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    @mysqli_query($conn, "UPDATE safety_instructor_masters SET instructor_code = CONCAT('INS', LPAD(id, 3, '0')) WHERE COALESCE(TRIM(instructor_code), '') = ''");
    clms_safety_ensure_index($conn, 'safety_instructor_masters', 'uq_instructor_code', "ALTER TABLE safety_instructor_masters ADD UNIQUE KEY uq_instructor_code (instructor_code)");
    foreach (array(
        'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
        'scheduled_session_id' => 'INT NULL',
        'batch_number' => 'VARCHAR(100) NULL',
        'scheduled_date' => 'DATE NULL',
        'scheduled_shift' => 'VARCHAR(20) NULL',
        'scheduled_venue' => 'VARCHAR(300) NULL',
        'scheduled_time' => 'VARCHAR(20) NULL',
        'instructor' => 'VARCHAR(150) NULL',
        'scheduled_by' => 'INT NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_requests', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS safety_instructor_masters (
        id INT NOT NULL AUTO_INCREMENT,
        instructor_code VARCHAR(30) NULL,
        instructor_name VARCHAR(150) NOT NULL,
        mobile VARCHAR(20) NULL,
        email VARCHAR(120) NULL,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_instructor_name (instructor_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'mobile', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'email', 'VARCHAR(120) NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'safety_instructor_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");
    @mysqli_query($conn, "UPDATE safety_instructor_masters SET instructor_code = CONCAT('INS', LPAD(id, 3, '0')) WHERE COALESCE(TRIM(instructor_code), '') = ''");
    clms_safety_ensure_index($conn, 'safety_instructor_masters', 'uq_instructor_code', "ALTER TABLE safety_instructor_masters ADD UNIQUE KEY uq_instructor_code (instructor_code)");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_language_masters (
        id INT NOT NULL AUTO_INCREMENT,
        language_name VARCHAR(80) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        sort_order INT DEFAULT 0,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_language (language_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_language_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'training_language_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_fee_masters (
        id INT NOT NULL AUTO_INCREMENT,
        fee_source VARCHAR(20) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        from_date DATE NULL,
        to_date DATE NOT NULL DEFAULT '9999-12-31',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_fee_source (fee_source)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_fee_masters', 'from_date', 'DATE NULL');
    clms_safety_ensure_column($conn, 'training_fee_masters', 'to_date', "DATE NOT NULL DEFAULT '9999-12-31'");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_class_batches (
        id INT NOT NULL AUTO_INCREMENT,
        batch_token VARCHAR(6) NOT NULL,
        batch_number VARCHAR(50) NOT NULL,
        training_date DATE NOT NULL,
        venue_id INT NULL,
        venue_name VARCHAR(300) NOT NULL,
        capacity INT NOT NULL DEFAULT 35,
        emergency_seats INT NOT NULL DEFAULT 5,
        language_id INT NULL,
        language_name VARCHAR(80) NOT NULL,
        session_name VARCHAR(20) NOT NULL,
        time_from TIME NULL,
        time_to TIME NULL,
        training_type_id INT NULL,
        training_type VARCHAR(100) NOT NULL,
        instructor_id INT NULL,
        instructor_name VARCHAR(150) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_batch_token (batch_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_class_batches', 'emergency_seats', 'INT NOT NULL DEFAULT 5');
    @mysqli_query($conn, "UPDATE training_class_batches SET emergency_seats = 5 WHERE emergency_seats = 0 AND capacity >= 5");
    @mysqli_query($conn, "
        UPDATE training_class_batches b
        JOIN training_venue_masters v ON v.id = b.venue_id
        SET b.capacity = v.seats
        WHERE b.emergency_seats > 0
          AND b.capacity = v.seats + b.emergency_seats
    ");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_schedule (
        id INT NOT NULL AUTO_INCREMENT,
        session_date DATE NULL,
        session_time TIME NULL,
        location VARCHAR(255) NULL,
        capacity INT DEFAULT 30,
        enrolled_count INT DEFAULT 0,
        trainer_name VARCHAR(100) NULL,
        batch_number VARCHAR(50) NULL,
        training_type VARCHAR(100) DEFAULT 'Safety Induction',
        session_status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        'session_date' => 'DATE NULL',
        'session_time' => 'TIME NULL',
        'location' => 'VARCHAR(255) NULL',
        'capacity' => 'INT DEFAULT 30',
        'enrolled_count' => 'INT DEFAULT 0',
        'trainer_name' => 'VARCHAR(100) NULL',
        'batch_number' => 'VARCHAR(50) NULL',
        'training_type' => "VARCHAR(100) DEFAULT 'Safety Induction'",
        'session_status' => "VARCHAR(50) DEFAULT 'open'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_schedule', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_batch_workers (
        id INT NOT NULL AUTO_INCREMENT,
        batch_id INT NOT NULL,
        training_request_id INT NOT NULL,
        workman_id INT NOT NULL,
        ticked TINYINT(1) NOT NULL DEFAULT 1,
        token_number VARCHAR(6) NULL,
        training_token VARCHAR(20) NULL,
        attempt_no INT NOT NULL DEFAULT 1,
        status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
        scheduled_at DATETIME NULL,
        created_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_batch_workman (batch_id, workman_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_safety_ensure_column($conn, 'training_batch_workers', 'token_number', 'VARCHAR(6) NULL');
    clms_safety_ensure_column($conn, 'training_batch_workers', 'training_token', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'training_batch_workers', 'external_reference', 'VARCHAR(100) NULL');
    clms_safety_ensure_column($conn, 'training_batch_workers', 'scheduled_at', 'DATETIME NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_session_workers (
        id INT NOT NULL AUTO_INCREMENT,
        session_id INT NOT NULL,
        workman_id INT NOT NULL,
        training_request_id INT NULL,
        attendance_status VARCHAR(20) DEFAULT 'pending',
        result VARCHAR(20) DEFAULT 'pending',
        valid_till DATE NULL,
        remarks TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        'session_id' => 'INT NOT NULL',
        'workman_id' => 'INT NOT NULL',
        'training_request_id' => 'INT NULL',
        'attendance_status' => "VARCHAR(20) DEFAULT 'pending'",
        'result' => "VARCHAR(20) DEFAULT 'pending'",
        'valid_till' => 'DATE NULL',
        'theory_score' => 'INT DEFAULT 0',
        'practical_score' => 'INT DEFAULT 0',
        'total_score' => 'INT DEFAULT 0',
        'external_reference' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_session_workers', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_session_workers ADD UNIQUE KEY uq_training_request (training_request_id)");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_results (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        application_no VARCHAR(100) NULL,
        result VARCHAR(20) NOT NULL,
        recorded_by INT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (array(
        'workman_id' => 'INT NOT NULL',
        'application_no' => 'VARCHAR(100) NULL',
        'training_request_id' => 'INT NULL',
        'training_token' => 'VARCHAR(20) NULL',
        'attendance_status' => "VARCHAR(30) DEFAULT 'present'",
        'total_score' => 'INT NULL',
        'external_reference' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'result' => 'VARCHAR(20) NOT NULL',
        'recorded_by' => 'INT NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ) as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_results', $column, $definition);
    }

    foreach (array('Malayalam', 'English', 'Kannada', 'Tamil', 'Hindi') as $idx => $language) {
        db_execute($conn, "INSERT IGNORE INTO training_language_masters (language_name, status, sort_order, created_at, updated_at) VALUES (?, 'active', ?, NOW(), NOW())", 'si', array($language, ($idx + 1) * 10));
    }
    foreach (array(array('PWO', 100.00), array('PO', 0.00), array('SO', 0.00)) as $fee) {
        db_execute($conn, "INSERT IGNORE INTO training_fee_masters (fee_source, amount, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW())", 'sd', array($fee[0], $fee[1]));
    }
}

function clms_safety_generate_batch_token($conn) {
    for ($i = 0; $i < 20; $i++) {
        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!db_single($conn, "SELECT id FROM training_class_batches WHERE batch_token = ? LIMIT 1", 's', array($token))) return $token;
    }
    return substr((string)time(), -6);
}

function clms_safety_generate_batch_number($conn, $trainingDate) {
    $stamp = date('Ymd', strtotime($trainingDate));
    $prefix = 'B' . $stamp;
    $row = db_single($conn, "SELECT COUNT(*) + 1 AS next_no FROM training_class_batches WHERE batch_number LIKE ?", 's', array($prefix . '%'));
    return $prefix . str_pad((string)(int)($row['next_no'] ?? 1), 3, '0', STR_PAD_LEFT);
}

function clms_safety_generate_training_token($trainingDate, $counter) {
    $year = date('Y', strtotime($trainingDate ?: 'now'));
    return 'TRN' . $year . str_pad((string)max(1, (int)$counter), 5, '0', STR_PAD_LEFT);
}

function clms_safety_contractors_name_sql($conn, $alias) {
    $parts = array();
    foreach (array('contractor_name', 'vendor_name', 'name') as $col) {
        if (clms_safety_column_exists($conn, 'contractors', $col)) $parts[] = "$alias.`$col`";
    }
    $parts[] = "CONCAT('Contractor #', $alias.id)";
    return 'COALESCE(' . implode(', ', $parts) . ')';
}

function clms_safety_contractors_code_sql($conn, $alias) {
    $parts = array();
    foreach (array('contractor_code', 'vendor_code', 'vendor_id') as $col) {
        if (clms_safety_column_exists($conn, 'contractors', $col)) $parts[] = "$alias.`$col`";
    }
    $parts[] = "CONCAT('C-', $alias.id)";
    return 'COALESCE(' . implode(', ', $parts) . ')';
}

function clms_safety_batch_candidates($conn, $batchId, $forceRequestId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId));
    if (!$batch) return array();

    $contractorName = clms_safety_contractors_name_sql($conn, 'c');
    $contractorCode = clms_safety_contractors_code_sql($conn, 'c');

    return db_fetch_all($conn, "
        SELECT
            tr.id AS training_request_id,
            tr.requested_date,
            tr.created_at AS request_created_at,
            tr.status AS request_status,
            w.id AS workman_id,
            w.name,
            w.aadhaar,
            w.temp_id,
            w.safety_language,
            w.contractor_id,
            $contractorCode AS contractor_code,
            $contractorName AS contractor_name,
            COALESCE(tbw.ticked, 0) AS ticked,
            tbw.token_number,
            tbw.training_token,
            COALESCE(tbw.attempt_no,
                (
                    SELECT COUNT(*)
                    FROM training_results r
                    WHERE r.workman_id = w.id
                      AND r.created_at >= DATE_SUB(?, INTERVAL 30 DAY)
                ) + 1
            ) AS attempt_no,
            tbw.status AS batch_worker_status
        FROM training_requests tr
        JOIN workmen w ON w.id = tr.workman_id
        LEFT JOIN contractors c ON c.id = COALESCE(tr.contractor_id, w.contractor_id)
        LEFT JOIN training_batch_workers tbw ON tbw.batch_id = ? AND tbw.training_request_id = tr.id
        WHERE (
              tr.id = ?
              OR (
                  (
                      tbw.id IS NOT NULL
                      OR LOWER(COALESCE(tr.status, 'pending')) IN ('pending', 'welfare_pending', 'failed', 'training_failed')
                  )
                  AND (
                      tbw.id IS NOT NULL
                      OR LOWER(TRIM(COALESCE(w.safety_language, ?))) = LOWER(TRIM(?))
                  )
              )
          )
          AND NOT EXISTS (
              SELECT 1
              FROM training_batch_workers used
              WHERE used.training_request_id = tr.id
                AND used.batch_id <> ?
                AND used.ticked = 1
                AND LOWER(COALESCE(used.status, 'scheduled')) IN ('scheduled', 'completed')
          )
        ORDER BY COALESCE(tr.requested_date, DATE(tr.created_at)) ASC, tr.id ASC
    ", 'siissi', array($batch['training_date'], $batchId, (int)$forceRequestId, $batch['language_name'], $batch['language_name'], $batchId));
}

function clms_safety_active_rows($rows) {
    $out = array();
    foreach ($rows as $row) {
        if (strtolower((string)($row['status'] ?? '')) === 'active') $out[] = $row;
    }
    return $out;
}

function clms_safety_delete_batch($conn, $batchId) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array((int)$batchId));
    if (!$batch) {
        throw new RuntimeException('Batch not found.');
    }

    $selectedCount = db_count($conn, "SELECT COUNT(*) FROM training_batch_workers WHERE batch_id = ? AND ticked = 1", 'i', array((int)$batchId));
    if ($selectedCount > 0) {
        throw new RuntimeException('This batch has assigned workers. Remove/untick workers before deleting it.');
    }

    $session = db_single($conn, "SELECT id FROM training_schedule WHERE batch_number = ? LIMIT 1", 's', array((string)$batch['batch_number']));
    if ($session) {
        $sessionWorkers = db_count($conn, "SELECT COUNT(*) FROM training_session_workers WHERE session_id = ?", 'i', array((int)$session['id']));
        if ($sessionWorkers > 0) {
            throw new RuntimeException('This batch session has workers. It cannot be deleted.');
        }
    }

    $conn->begin_transaction();
    try {
        db_execute($conn, "DELETE FROM training_batch_workers WHERE batch_id = ?", 'i', array((int)$batchId));
        if ($session) {
            db_execute($conn, "DELETE FROM training_schedule WHERE id = ?", 'i', array((int)$session['id']));
        }
        db_execute($conn, "DELETE FROM training_class_batches WHERE id = ?", 'i', array((int)$batchId));
        $conn->commit();
        return (string)$batch['batch_number'];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_create_batch($conn, $data, $userId) {
    clms_safety_ensure_control_schema($conn);

    $trainingDate = trim((string)($data['training_date'] ?? ''));
    $venueId = (int)($data['venue_id'] ?? 0);
    $languageId = (int)($data['language_id'] ?? 0);
    $sessionName = strtoupper(trim((string)($data['session_name'] ?? 'FN')));
    $timeFrom = trim((string)($data['time_from'] ?? ''));
    $timeTo = trim((string)($data['time_to'] ?? ''));
    $typeId = (int)($data['training_type_id'] ?? 0);
    $instructorId = (int)($data['instructor_id'] ?? 0);
    $emergencySeats = max(0, (int)($data['emergency_seats'] ?? 5));
    $saveMode = (($data['save_mode'] ?? 'schedule') === 'draft') ? 'draft' : 'open';

    if (!$trainingDate || !$venueId || !$languageId || !$typeId) {
        throw new RuntimeException('Training date, location, language and type are required.');
    }

    $venue = db_single($conn, "SELECT id, venue_name, COALESCE(seats, 35) seats FROM training_venue_masters WHERE id = ? LIMIT 1", 'i', array($venueId));
    $language = db_single($conn, "SELECT id, language_name FROM training_language_masters WHERE id = ? LIMIT 1", 'i', array($languageId));
    $type = db_single($conn, "SELECT id, type_name FROM master_training_types WHERE id = ? LIMIT 1", 'i', array($typeId));
    $instructor = $instructorId ? db_single($conn, "SELECT id, instructor_name FROM safety_instructor_masters WHERE id = ? LIMIT 1", 'i', array($instructorId)) : null;
    if (!$venue || !$language || !$type) throw new RuntimeException('Invalid master selection.');

    $capacity = max(1, (int)$venue['seats']);
    $emergencySeats = min($emergencySeats, $capacity);
    $token = clms_safety_generate_batch_token($conn);
    $batchNumber = clms_safety_generate_batch_number($conn, $trainingDate);

    $conn->begin_transaction();
    try {
        db_execute(
            $conn,
            "INSERT INTO training_class_batches (batch_token, batch_number, training_date, venue_id, venue_name, capacity, emergency_seats, language_id, language_name, session_name, time_from, time_to, training_type_id, training_type, instructor_id, instructor_name, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            'sssisiiissssisissi',
            array($token, $batchNumber, $trainingDate, $venueId, $venue['venue_name'], $capacity, $emergencySeats, $languageId, $language['language_name'], $sessionName, $timeFrom ?: null, $timeTo ?: null, $typeId, $type['type_name'], $instructorId ?: null, $instructor['instructor_name'] ?? '', $saveMode, $userId)
        );
        $batchId = (int)mysqli_insert_id($conn);
        $conn->commit();
        return array('batch_id' => $batchId, 'batch_number' => $batchNumber, 'selected' => 0);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_schedule_batch($conn, $batchId, $selectedRequestIds, $userId, $forceRequestId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId));
    if (!$batch) throw new RuntimeException('Invalid batch selection.');

    $capacity = max(1, (int)$batch['capacity']);
    $selectedRequestIds = array_values(array_unique(array_map('intval', (array)$selectedRequestIds)));
    $selectedRequestIds = array_filter($selectedRequestIds, function($id) { return $id > 0; });
    if (count($selectedRequestIds) > $capacity) {
        throw new RuntimeException('Maximum seat limit exceeded.');
    }
    if (!$selectedRequestIds) {
        throw new RuntimeException('Please select at least one worker to schedule.');
    }

    $candidates = clms_safety_batch_candidates($conn, $batchId, (int)$forceRequestId);
    $candidateMap = array();
    foreach ($candidates as $candidate) {
        $candidateMap[(int)$candidate['training_request_id']] = $candidate;
    }

    $finalTime = $batch['time_from'] ?: ($batch['session_name'] === 'AN' ? '14:00:00' : '09:00:00');
    $shift = $batch['session_name'] === 'AN' ? 'evening' : 'morning';

    $conn->begin_transaction();
    try {
        $session = db_single($conn, "SELECT id FROM training_schedule WHERE batch_number = ? LIMIT 1", 's', array($batch['batch_number']));
        if (!$session) {
            db_execute(
                $conn,
                "INSERT INTO training_schedule (session_date, session_time, location, capacity, trainer_name, batch_number, training_type, session_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())",
                'sssisss',
                array($batch['training_date'], $finalTime, $batch['venue_name'], $capacity, $batch['instructor_name'] ?? '', $batch['batch_number'], $batch['training_type'])
            );
            $sessionId = (int)mysqli_insert_id($conn);
        } else {
            $sessionId = (int)$session['id'];
            db_execute(
                $conn,
                "UPDATE training_schedule
                 SET session_date = ?, session_time = ?, location = ?, capacity = ?, trainer_name = ?, training_type = ?, session_status = 'open'
                 WHERE id = ?",
                'sssissi',
                array($batch['training_date'], $finalTime, $batch['venue_name'], $capacity, $batch['instructor_name'] ?? '', $batch['training_type'], $sessionId)
            );
        }

        $selectedMap = array();
        foreach ($selectedRequestIds as $selectedId) {
            $selectedMap[(int)$selectedId] = true;
        }

        $existingRows = db_fetch_all(
            $conn,
            "SELECT tbw.training_request_id, tbw.workman_id,
                    COALESCE(tr.status, '') AS request_status,
                    COALESCE(tr.contractor_confirmed, 0) AS contractor_confirmed,
                    EXISTS (
                        SELECT 1
                        FROM training_session_workers tsw
                        WHERE tsw.training_request_id = tbw.training_request_id
                          AND (
                              LOWER(COALESCE(tsw.attendance_status, 'pending')) NOT IN ('pending', '')
                              OR LOWER(COALESCE(tsw.result, 'pending')) NOT IN ('pending', '')
                          )
                    ) AS training_started
             FROM training_batch_workers tbw
             LEFT JOIN training_requests tr ON tr.id = tbw.training_request_id
             WHERE tbw.batch_id = ?",
            'i',
            array($batchId)
        );

        foreach ($existingRows as $existing) {
            $existingRequestId = (int)$existing['training_request_id'];
            $isLocked = strtolower((string)$existing['request_status']) === 'contractor_confirmed'
                || (int)$existing['contractor_confirmed'] === 1
                || (int)$existing['training_started'] === 1;

            if ($isLocked && !isset($selectedMap[$existingRequestId])) {
                throw new RuntimeException('Already confirmed/started workers cannot be removed from this batch. Keep them selected and create another batch if seats are full.');
            }

            if (!$isLocked && !isset($selectedMap[$existingRequestId])) {
                db_execute(
                    $conn,
                    "UPDATE training_batch_workers SET ticked = 0, token_number = NULL, training_token = NULL, status = 'waiting' WHERE batch_id = ? AND training_request_id = ?",
                    'ii',
                    array($batchId, $existingRequestId)
                );
                db_execute($conn, "DELETE FROM training_session_workers WHERE training_request_id = ?", 'i', array($existingRequestId));
                db_execute(
                    $conn,
                    "UPDATE training_requests
                     SET status = 'pending',
                         contractor_confirmed = 0,
                         scheduled_session_id = NULL,
                         batch_number = NULL,
                         updated_at = NOW()
                     WHERE id = ? AND status IN ('scheduled', 'pending', 'welfare_pending')",
                    'i',
                    array($existingRequestId)
                );
                db_execute(
                    $conn,
                    "UPDATE workmen SET training_status = 'pending', safety_training_status = 'PENDING_TRAINING' WHERE id = ?",
                    'i',
                    array((int)$existing['workman_id'])
                );
            }
        }

        $counter = 1;
        foreach ($selectedRequestIds as $requestId) {
            if (!isset($candidateMap[$requestId])) {
                throw new RuntimeException('One selected worker is not eligible for this batch language.');
            }
            $candidate = $candidateMap[$requestId];
            $attemptNo = max(1, (int)$candidate['attempt_no']);
            if ($attemptNo > 3) {
                throw new RuntimeException(($candidate['name'] ?? 'Worker') . ' has reached maximum 3 attempts. Please apply for training again.');
            }

            $token = str_pad((string)$counter, 6, '0', STR_PAD_LEFT);
            $trainingToken = clms_safety_generate_training_token($batch['training_date'], $counter);
            db_execute(
                $conn,
                "INSERT INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, token_number, training_token, attempt_no, status, scheduled_at, created_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?, 'scheduled', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE training_request_id = VALUES(training_request_id), ticked = 1, token_number = VALUES(token_number), training_token = VALUES(training_token), attempt_no = VALUES(attempt_no), status = 'scheduled', scheduled_at = NOW()",
                'iiissi',
                array($batchId, $requestId, (int)$candidate['workman_id'], $token, $trainingToken, $attemptNo)
            );
            $currentReq = db_single($conn, "SELECT status, contractor_confirmed FROM training_requests WHERE id = ? LIMIT 1", 'i', array($requestId));
            $isConfirmed = $currentReq && (strtolower((string)$currentReq['status']) === 'contractor_confirmed' || (int)($currentReq['contractor_confirmed'] ?? 0) === 1);
            if ($isConfirmed) {
                db_execute(
                    $conn,
                    "UPDATE training_requests
                     SET training_type = ?, scheduled_date = ?, scheduled_shift = ?, scheduled_venue = ?, scheduled_time = ?,
                         batch_number = ?, instructor = ?, contractor_confirmed = 1, scheduled_by = ?, scheduled_session_id = ?,
                         status = 'contractor_confirmed', updated_at = NOW()
                     WHERE id = ?",
                    'sssssssiii',
                    array($batch['training_type'], $batch['training_date'], $shift, $batch['venue_name'], $finalTime, $batch['batch_number'], $batch['instructor_name'] ?? '', $userId, $sessionId, $requestId)
                );
                db_execute(
                    $conn,
                    "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
                     VALUES (?, ?, ?, 'pending', 'pending', NOW())
                     ON DUPLICATE KEY UPDATE session_id = VALUES(session_id)",
                    'iii',
                    array($sessionId, (int)$candidate['workman_id'], $requestId)
                );
            } else {
                db_execute(
                    $conn,
                    "UPDATE training_requests
                     SET training_type = ?, scheduled_date = ?, scheduled_shift = ?, scheduled_venue = ?, scheduled_time = ?,
                         batch_number = ?, instructor = ?, contractor_confirmed = 0, scheduled_by = ?, scheduled_session_id = ?,
                         status = 'scheduled', updated_at = NOW()
                     WHERE id = ?",
                    'sssssssiii',
                    array($batch['training_type'], $batch['training_date'], $shift, $batch['venue_name'], $finalTime, $batch['batch_number'], $batch['instructor_name'] ?? '', $userId, $sessionId, $requestId)
                );
                db_execute(
                    $conn,
                    "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
                     VALUES (?, ?, ?, 'pending', 'pending', NOW())
                     ON DUPLICATE KEY UPDATE session_id = VALUES(session_id)",
                    'iii',
                    array($sessionId, (int)$candidate['workman_id'], $requestId)
                );
            }
            db_execute(
                $conn,
                "UPDATE workmen SET training_status = 'scheduled', safety_training_status = 'TRAINING_SCHEDULED' WHERE id = ?",
                'i',
                array((int)$candidate['workman_id'])
            );
            $counter++;
        }

        db_execute(
            $conn,
            "UPDATE training_schedule
             SET enrolled_count = (
                 SELECT COUNT(*)
                 FROM training_session_workers tsw
                 JOIN training_requests tr ON tr.id = tsw.training_request_id
                 WHERE tsw.session_id = ? AND tr.status = 'contractor_confirmed'
             )
             WHERE id = ?",
            'ii',
            array($sessionId, $sessionId)
        );
        db_execute($conn, "UPDATE training_class_batches SET status = 'scheduled', updated_at = NOW() WHERE id = ?", 'i', array($batchId));
        $conn->commit();
        return array('batch_number' => $batch['batch_number'], 'scheduled' => count($selectedRequestIds), 'session_id' => $sessionId);
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function clms_safety_save_batch_selection($conn, $batchId, $selectedRequestIds, $userId, $forceRequestId = 0) {
    clms_safety_ensure_control_schema($conn);
    $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId));
    if (!$batch) throw new RuntimeException('Invalid batch selection.');

    $capacity = max(1, (int)$batch['capacity']);
    $selectedRequestIds = array_values(array_unique(array_map('intval', (array)$selectedRequestIds)));
    $selectedRequestIds = array_filter($selectedRequestIds, function($id) { return $id > 0; });
    if (count($selectedRequestIds) > $capacity) {
        throw new RuntimeException('Maximum seat limit exceeded.');
    }

    $candidates = clms_safety_batch_candidates($conn, $batchId, (int)$forceRequestId);
    $candidateMap = array();
    foreach ($candidates as $candidate) {
        $candidateMap[(int)$candidate['training_request_id']] = $candidate;
    }

    $conn->begin_transaction();
    try {
        db_execute($conn, "UPDATE training_batch_workers SET ticked = 0, status = 'waiting', token_number = NULL, training_token = NULL WHERE batch_id = ?", 'i', array($batchId));

        $counter = 1;
        foreach ($selectedRequestIds as $requestId) {
            if (!isset($candidateMap[$requestId])) {
                throw new RuntimeException('One selected worker is not eligible for this batch language.');
            }
            $candidate = $candidateMap[$requestId];
            $attemptNo = max(1, (int)$candidate['attempt_no']);
            if ($attemptNo > 3) {
                throw new RuntimeException(($candidate['name'] ?? 'Worker') . ' has reached maximum 3 attempts. Please apply for training again.');
            }
            $token = str_pad((string)$counter, 6, '0', STR_PAD_LEFT);
            $trainingToken = clms_safety_generate_training_token($batch['training_date'], $counter);
            db_execute(
                $conn,
                "INSERT INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, token_number, training_token, attempt_no, status, created_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?, 'draft', NOW())
                 ON DUPLICATE KEY UPDATE training_request_id = VALUES(training_request_id), ticked = 1, token_number = VALUES(token_number), training_token = VALUES(training_token), attempt_no = VALUES(attempt_no), status = 'draft'",
                'iiissi',
                array($batchId, $requestId, (int)$candidate['workman_id'], $token, $trainingToken, $attemptNo)
            );
            $counter++;
        }

        db_execute($conn, "UPDATE training_class_batches SET status = 'draft', updated_at = NOW() WHERE id = ?", 'i', array($batchId));
        $conn->commit();
        return array('batch_number' => $batch['batch_number'], 'selected' => count($selectedRequestIds));
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
?>
