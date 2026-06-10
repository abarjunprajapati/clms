<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../../include/training_venue_master.php';
require_once __DIR__ . '/../../include/training_type_master.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safetyDashTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function safetyDashColumnExists($conn, $table, $column) {
    if (!safetyDashTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function safetyDashCol($conn, $table, $alias, $column, $fallback = 'NULL') {
    return safetyDashColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function safetyDashEnsureColumn($conn, $table, $column, $definition) {
    if (!safetyDashTableExists($conn, $table) || safetyDashColumnExists($conn, $table, $column)) {
        return;
    }

    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function safetyDashEnsureControlSchema($conn) {
    clms_ensure_training_venue_masters($conn);
    clms_ensure_training_type_master($conn);

    foreach ([
        'venue_code' => "VARCHAR(30) NULL AFTER id",
        'seats' => "INT NOT NULL DEFAULT 35 AFTER venue_name",
    ] as $column => $definition) {
        safetyDashEnsureColumn($conn, 'training_venue_masters', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS safety_instructor_masters (
        id INT NOT NULL AUTO_INCREMENT,
        instructor_code VARCHAR(30) NULL,
        instructor_name VARCHAR(150) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_instructor_name (instructor_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_language_masters (
        id INT NOT NULL AUTO_INCREMENT,
        language_name VARCHAR(80) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_language (language_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_fee_masters (
        id INT NOT NULL AUTO_INCREMENT,
        fee_source VARCHAR(20) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_fee_source (fee_source)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_class_batches (
        id INT NOT NULL AUTO_INCREMENT,
        batch_token VARCHAR(6) NOT NULL,
        batch_number VARCHAR(50) NOT NULL,
        training_date DATE NOT NULL,
        venue_id INT NULL,
        venue_name VARCHAR(300) NOT NULL,
        capacity INT NOT NULL DEFAULT 35,
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

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_batch_workers (
        id INT NOT NULL AUTO_INCREMENT,
        batch_id INT NOT NULL,
        training_request_id INT NOT NULL,
        workman_id INT NOT NULL,
        ticked TINYINT(1) NOT NULL DEFAULT 1,
        attempt_no INT NOT NULL DEFAULT 1,
        status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
        created_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_batch_workman (batch_id, workman_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach (['Malayalam', 'English', 'Kannada', 'Tamil', 'Hindi'] as $idx => $language) {
        db_execute(
            $conn,
            "INSERT IGNORE INTO training_language_masters (language_name, status, sort_order, created_at, updated_at)
             VALUES (?, 'active', ?, NOW(), NOW())",
            'si',
            [$language, ($idx + 1) * 10]
        );
    }
    foreach ([['PWO', 100.00], ['PO', 0.00], ['SO', 0.00]] as $fee) {
        db_execute(
            $conn,
            "INSERT IGNORE INTO training_fee_masters (fee_source, amount, status, created_at, updated_at)
             VALUES (?, ?, 'active', NOW(), NOW())",
            'sd',
            [$fee[0], $fee[1]]
        );
    }
}

function safetyDashGenerateBatchToken($conn) {
    for ($i = 0; $i < 20; $i++) {
        $token = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if (!db_single($conn, "SELECT id FROM training_class_batches WHERE batch_token = ? LIMIT 1", 's', [$token])) {
            return $token;
        }
    }
    return substr((string)time(), -6);
}

function safetyDashRedirect($message, $type = 'success') {
    $_SESSION[$type] = $message;
    header('Location: dashboard.php');
    exit;
}

function safetyDashHandlePost($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    safetyDashEnsureControlSchema($conn);

    $action = $_POST['safety_action'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    try {
        if ($action === 'add_location') {
            $code = trim((string)($_POST['venue_code'] ?? ''));
            $name = trim((string)($_POST['venue_name'] ?? ''));
            $seats = max(1, (int)($_POST['seats'] ?? 35));
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            if ($name === '') throw new RuntimeException('Training location name is required.');
            db_execute(
                $conn,
                "INSERT INTO training_venue_masters (venue_code, venue_name, seats, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE venue_code = VALUES(venue_code), seats = VALUES(seats), status = VALUES(status), updated_at = NOW()",
                'ssisi',
                [$code, $name, $seats, $status, $userId]
            );
            safetyDashRedirect('Training location master saved.');
        }

        if ($action === 'add_instructor') {
            $code = trim((string)($_POST['instructor_code'] ?? ''));
            $name = trim((string)($_POST['instructor_name'] ?? ''));
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            if ($name === '') throw new RuntimeException('Instructor name is required.');
            db_execute(
                $conn,
                "INSERT INTO safety_instructor_masters (instructor_code, instructor_name, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE instructor_code = VALUES(instructor_code), status = VALUES(status), updated_at = NOW()",
                'sssi',
                [$code, $name, $status, $userId]
            );
            safetyDashRedirect('Instructor master saved.');
        }

        if ($action === 'add_language') {
            $name = trim((string)($_POST['language_name'] ?? ''));
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            if ($name === '') throw new RuntimeException('Training language is required.');
            db_execute(
                $conn,
                "INSERT INTO training_language_masters (language_name, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()",
                'ssi',
                [$name, $status, $userId]
            );
            safetyDashRedirect('Training language master saved.');
        }

        if ($action === 'add_fee') {
            $source = strtoupper(trim((string)($_POST['fee_source'] ?? '')));
            $amount = max(0, (float)($_POST['amount'] ?? 0));
            if (!in_array($source, ['PWO', 'PO', 'SO'], true)) throw new RuntimeException('Fee source must be PWO, PO or SO.');
            db_execute(
                $conn,
                "INSERT INTO training_fee_masters (fee_source, amount, status, created_by, created_at, updated_at)
                 VALUES (?, ?, 'active', ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = 'active', updated_at = NOW()",
                'sdi',
                [$source, $amount, $userId]
            );
            safetyDashRedirect('Training fee master saved.');
        }

        if ($action === 'add_type') {
            clms_add_training_type($conn, trim((string)($_POST['type_name'] ?? '')), (int)($_POST['duration_hours'] ?? 8), (int)($_POST['pass_mark'] ?? 60));
            safetyDashRedirect('Training type master saved.');
        }

        if ($action === 'create_batch') {
            $trainingDate = trim((string)($_POST['training_date'] ?? ''));
            $venueId = (int)($_POST['venue_id'] ?? 0);
            $languageId = (int)($_POST['language_id'] ?? 0);
            $sessionName = strtoupper(trim((string)($_POST['session_name'] ?? 'FN')));
            $timeFrom = trim((string)($_POST['time_from'] ?? ''));
            $timeTo = trim((string)($_POST['time_to'] ?? ''));
            $typeId = (int)($_POST['training_type_id'] ?? 0);
            $instructorId = (int)($_POST['instructor_id'] ?? 0);
            $saveMode = ($_POST['save_mode'] ?? 'schedule') === 'draft' ? 'draft' : 'scheduled';

            if (!$trainingDate || !$venueId || !$languageId || !$typeId) {
                throw new RuntimeException('Training date, location, language and type are required.');
            }

            $venue = db_single($conn, "SELECT id, venue_name, COALESCE(seats, 35) seats FROM training_venue_masters WHERE id = ? LIMIT 1", 'i', [$venueId]);
            $language = db_single($conn, "SELECT id, language_name FROM training_language_masters WHERE id = ? LIMIT 1", 'i', [$languageId]);
            $type = db_single($conn, "SELECT id, type_name FROM master_training_types WHERE id = ? LIMIT 1", 'i', [$typeId]);
            $instructor = $instructorId ? db_single($conn, "SELECT id, instructor_name FROM safety_instructor_masters WHERE id = ? LIMIT 1", 'i', [$instructorId]) : null;
            if (!$venue || !$language || !$type) throw new RuntimeException('Invalid master selection.');

            $capacity = max(1, (int)$venue['seats']);
            $token = safetyDashGenerateBatchToken($conn);
            $batchNumber = date('Ymd', strtotime($trainingDate)) . '-' . $sessionName . '-' . $token;
            $finalTime = $timeFrom ?: ($sessionName === 'AN' ? '14:00' : '09:00');
            $shift = $sessionName === 'AN' ? 'evening' : 'morning';

            $conn->begin_transaction();

            db_execute(
                $conn,
                "INSERT INTO training_schedule (session_date, session_time, location, capacity, trainer_name, batch_number, training_type, session_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                'sssissss',
                [
                    $trainingDate,
                    $finalTime,
                    $venue['venue_name'],
                    $capacity,
                    $instructor['instructor_name'] ?? '',
                    $batchNumber,
                    $type['type_name'],
                    $saveMode === 'draft' ? 'draft' : 'open',
                ]
            );
            $sessionId = (int)mysqli_insert_id($conn);

            db_execute(
                $conn,
                "INSERT INTO training_class_batches
                 (batch_token, batch_number, training_date, venue_id, venue_name, capacity, language_id, language_name, session_name, time_from, time_to, training_type_id, training_type, instructor_id, instructor_name, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                'sssisiissssisissi',
                [
                    $token,
                    $batchNumber,
                    $trainingDate,
                    $venueId,
                    $venue['venue_name'],
                    $capacity,
                    $languageId,
                    $language['language_name'],
                    $sessionName,
                    $timeFrom ?: null,
                    $timeTo ?: null,
                    $typeId,
                    $type['type_name'],
                    $instructorId ?: null,
                    $instructor['instructor_name'] ?? '',
                    $saveMode,
                    $userId,
                ]
            );
            $batchId = (int)mysqli_insert_id($conn);

            $candidates = [];
            if ($saveMode === 'scheduled') {
                $candidates = db_fetch_all($conn, "
                    SELECT tr.id AS request_id, tr.workman_id,
                           (
                               SELECT COUNT(*)
                               FROM training_requests r2
                               WHERE r2.workman_id = tr.workman_id
                                 AND r2.status IN ('failed', 'passed')
                                 AND r2.created_at >= DATE_SUB(?, INTERVAL 30 DAY)
                           ) + 1 AS attempt_no
                    FROM training_requests tr
                    JOIN workmen w ON w.id = tr.workman_id
                    WHERE tr.status IN ('pending', 'welfare_pending', 'failed')
                      AND LOWER(TRIM(COALESCE(w.safety_language, ''))) = LOWER(TRIM(?))
                    ORDER BY COALESCE(tr.preferred_date, tr.requested_date, DATE(tr.created_at)) ASC, tr.id ASC
                    LIMIT $capacity
                ", 'ss', [$trainingDate, $language['language_name']]);

                foreach ($candidates as $row) {
                    if ((int)$row['attempt_no'] > 3) {
                        continue;
                    }
                    db_execute(
                        $conn,
                        "INSERT IGNORE INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, attempt_no, status, created_at)
                         VALUES (?, ?, ?, 1, ?, 'scheduled', NOW())",
                        'iiii',
                        [$batchId, (int)$row['request_id'], (int)$row['workman_id'], (int)$row['attempt_no']]
                    );
                    db_execute(
                        $conn,
                        "UPDATE training_requests
                         SET training_type = ?, scheduled_date = ?, scheduled_shift = ?, scheduled_venue = ?, scheduled_time = ?,
                             batch_number = ?, instructor = ?, scheduled_by = ?, scheduled_session_id = ?, status = 'scheduled', updated_at = NOW()
                         WHERE id = ?",
                        'sssssssiii',
                        [
                            $type['type_name'],
                            $trainingDate,
                            $shift,
                            $venue['venue_name'],
                            $finalTime,
                            $batchNumber,
                            $instructor['instructor_name'] ?? '',
                            $userId,
                            $sessionId,
                            (int)$row['request_id'],
                        ]
                    );
                    db_execute(
                        $conn,
                        "UPDATE workmen SET training_status = 'scheduled', safety_training_status = 'TRAINING_SCHEDULED' WHERE id = ?",
                        'i',
                        [(int)$row['workman_id']]
                    );
                }
            }

            db_execute(
                $conn,
                "UPDATE training_schedule
                 SET enrolled_count = (SELECT COUNT(*) FROM training_batch_workers WHERE batch_id = ? AND ticked = 1)
                 WHERE id = ?",
                'ii',
                [$batchId, $sessionId]
            );

            $conn->commit();
            safetyDashRedirect('Batch ' . $batchNumber . ' created. Auto-selected ' . count($candidates) . ' worker(s) as per seats and language.');
        }
    } catch (Throwable $e) {
        if (method_exists($conn, 'rollback')) {
            @$conn->rollback();
        }
        safetyDashRedirect($e->getMessage(), 'error');
    }
}

safetyDashHandlePost($conn);

function safetyDashCount($conn, $sql, $types = '', $params = []) {
    return db_count($conn, $sql, $types, $params);
}

function safetyDashRepairConfirmedSessions($conn) {
    if (
        !safetyDashTableExists($conn, 'training_requests') ||
        !safetyDashTableExists($conn, 'training_schedule') ||
        !safetyDashTableExists($conn, 'training_session_workers') ||
        !safetyDashColumnExists($conn, 'training_requests', 'workman_id') ||
        !safetyDashColumnExists($conn, 'training_requests', 'scheduled_date') ||
        !safetyDashColumnExists($conn, 'training_requests', 'scheduled_venue') ||
        !safetyDashColumnExists($conn, 'training_session_workers', 'training_request_id')
    ) {
        return;
    }

    safetyDashEnsureColumn($conn, 'training_requests', 'scheduled_session_id', 'INT NULL');

    $sessionIdExpr = safetyDashColumnExists($conn, 'training_requests', 'scheduled_session_id') ? 'tr.scheduled_session_id' : 'NULL';
    $scheduledTimeExpr = safetyDashColumnExists($conn, 'training_requests', 'scheduled_time') ? 'tr.scheduled_time' : 'NULL';
    $scheduledShiftExpr = safetyDashColumnExists($conn, 'training_requests', 'scheduled_shift') ? 'tr.scheduled_shift' : 'NULL';
    $instructorExpr = safetyDashColumnExists($conn, 'training_requests', 'instructor') ? 'tr.instructor' : 'NULL';
    $batchExpr = safetyDashColumnExists($conn, 'training_requests', 'batch_number') ? 'tr.batch_number' : 'NULL';

    $stuckRows = db_fetch_all($conn, "
        SELECT
            tr.id,
            tr.workman_id,
            tr.status,
            tr.scheduled_date,
            tr.scheduled_venue,
            $sessionIdExpr AS scheduled_session_id,
            $scheduledTimeExpr AS scheduled_time,
            $scheduledShiftExpr AS scheduled_shift,
            $instructorExpr AS instructor,
            $batchExpr AS batch_number,
            tsw.id AS session_worker_id,
            tsw.session_id AS existing_session_id
        FROM training_requests tr
        LEFT JOIN training_session_workers tsw ON tsw.training_request_id = tr.id
        WHERE tr.status IN ('scheduled', 'contractor_confirmed')
          AND tr.scheduled_date IS NOT NULL
          AND COALESCE(TRIM(tr.scheduled_venue), '') <> ''
        ORDER BY tr.updated_at DESC, tr.id DESC
        LIMIT 200
    ");

    foreach ($stuckRows as $row) {
        try {
            $finalTime = $row['scheduled_time'] ?: (($row['scheduled_shift'] ?? '') === 'morning' ? '09:00:00' : '14:00:00');
            $session = null;

            if (!empty($row['scheduled_session_id'])) {
                $session = db_single($conn, "SELECT id, session_status FROM training_schedule WHERE id = ? LIMIT 1", 'i', [(int)$row['scheduled_session_id']]);
            }

            if (!$session) {
                $session = db_single(
                    $conn,
                    "SELECT id, session_status
                     FROM training_schedule
                     WHERE session_date = ?
                       AND LOWER(TRIM(location)) = LOWER(TRIM(?))
                       AND session_time = ?
                       AND LOWER(COALESCE(session_status, 'open')) <> 'cancelled'
                     LIMIT 1",
                    'sss',
                    [$row['scheduled_date'], trim((string)$row['scheduled_venue']), $finalTime]
                );
            }

            if (!$session) {
                db_execute(
                    $conn,
                    "INSERT INTO training_schedule (session_date, session_time, location, capacity, trainer_name, batch_number, training_type, session_status, created_at)
                     VALUES (?, ?, ?, 30, ?, ?, 'induction', 'open', NOW())",
                    'sssss',
                    [
                        $row['scheduled_date'],
                        $finalTime,
                        trim((string)$row['scheduled_venue']),
                        (string)($row['instructor'] ?? ''),
                        (string)($row['batch_number'] ?? '')
                    ]
                );
                $session = ['id' => mysqli_insert_id($conn), 'session_status' => 'open'];
            }

            $sessionId = (int)($session['id'] ?? 0);
            if (!$sessionId || strtolower((string)($session['session_status'] ?? 'open')) === 'cancelled') {
                continue;
            }

            if (safetyDashColumnExists($conn, 'training_requests', 'scheduled_session_id')) {
                db_execute($conn, "UPDATE training_requests SET scheduled_session_id = ? WHERE id = ?", 'ii', [$sessionId, (int)$row['id']]);
            }

            if (($row['status'] ?? '') === 'contractor_confirmed') {
                if (!empty($row['session_worker_id'])) {
                    db_execute(
                        $conn,
                        "UPDATE training_session_workers SET session_id = ? WHERE id = ?",
                        'ii',
                        [$sessionId, (int)$row['session_worker_id']]
                    );
                } else {
                    db_execute(
                        $conn,
                        "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
                         VALUES (?, ?, ?, 'pending', 'pending', NOW())",
                        'iii',
                        [$sessionId, (int)$row['workman_id'], (int)$row['id']]
                    );
                }
            }

            if (safetyDashColumnExists($conn, 'training_schedule', 'enrolled_count')) {
                db_execute(
                    $conn,
                    "UPDATE training_schedule
                     SET enrolled_count = (
                         SELECT COUNT(*)
                         FROM training_session_workers tsw2
                         JOIN training_requests tr2 ON tr2.id = tsw2.training_request_id
                         WHERE tsw2.session_id = ? AND tr2.status = 'contractor_confirmed'
                     )
                     WHERE id = ?",
                    'ii',
                    [$sessionId, $sessionId]
                );
            }
        } catch (Throwable $e) {
            error_log('[safety dashboard repair] ' . $e->getMessage());
        }
    }
}

function renderContent() {
    global $conn;
    clms_training_seed_approved_queue($conn);
    safetyDashEnsureControlSchema($conn);
    safetyDashRepairConfirmedSessions($conn);

    $hasRequests = safetyDashTableExists($conn, 'training_requests');
    $hasSchedule = safetyDashTableExists($conn, 'training_schedule');
    $hasSessionWorkers = safetyDashTableExists($conn, 'training_session_workers');
    $hasWorkmen = safetyDashTableExists($conn, 'workmen');

    $requestPending = $hasRequests
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM training_requests WHERE LOWER(COALESCE(status, 'pending')) IN ('pending', 'welfare_pending', 'failed')")
        : 0;
    $activeSessions = $hasSchedule
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM training_schedule WHERE LOWER(COALESCE(session_status, 'open')) IN ('open', 'scheduled')")
        : 0;
    $completedToday = $hasSchedule && safetyDashColumnExists($conn, 'training_schedule', 'session_date')
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM training_schedule WHERE LOWER(COALESCE(session_status, 'open')) = 'completed' AND session_date = CURDATE()")
        : 0;
    $failedWorkers = $hasWorkmen
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM workmen WHERE LOWER(COALESCE(training_status, '')) IN ('fail', 'failed', 'training_failed')")
        : 0;
    $passedWorkers = $hasWorkmen
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM workmen WHERE LOWER(COALESCE(training_status, '')) IN ('pass', 'passed', 'qualified', 'completed', 'training_passed')")
        : 0;
    $scheduledWorkers = $hasWorkmen
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM workmen WHERE LOWER(COALESCE(training_status, '')) IN ('scheduled', 'training_scheduled')")
        : 0;
    $pendingResults = $hasSessionWorkers && $hasRequests
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM training_session_workers tsw JOIN training_requests tr ON tr.id = tsw.training_request_id WHERE tr.status = 'contractor_confirmed' AND LOWER(COALESCE(tsw.attendance_status, 'pending')) = 'present' AND LOWER(COALESCE(tsw.result, 'pending')) NOT IN ('pass', 'fail', 'passed', 'failed')")
        : 0;
    $expiringSoon = $hasWorkmen && safetyDashColumnExists($conn, 'workmen', 'training_valid_till')
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM workmen WHERE training_valid_till BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")
        : 0;
    $gatePassEligible = $passedWorkers;

    $venues = db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, status FROM training_venue_masters ORDER BY status ASC, venue_name ASC");
    $activeVenues = [];
    foreach ($venues as $row) {
        if (strtolower((string)($row['status'] ?? '')) === 'active') {
            $activeVenues[] = $row;
        }
    }
    $instructors = db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, status FROM safety_instructor_masters ORDER BY status ASC, instructor_name ASC");
    $activeInstructors = [];
    foreach ($instructors as $row) {
        if (strtolower((string)($row['status'] ?? '')) === 'active') {
            $activeInstructors[] = $row;
        }
    }
    $languages = db_fetch_all($conn, "SELECT id, language_name, status FROM training_language_masters ORDER BY sort_order ASC, language_name ASC");
    $activeLanguages = [];
    foreach ($languages as $row) {
        if (strtolower((string)($row['status'] ?? '')) === 'active') {
            $activeLanguages[] = $row;
        }
    }
    $trainingTypes = clms_get_training_type_rows($conn, false);
    $activeTrainingTypes = [];
    foreach ($trainingTypes as $row) {
        if (strtolower((string)($row['status'] ?? '')) === 'active') {
            $activeTrainingTypes[] = $row;
        }
    }
    $feeRows = db_fetch_all($conn, "SELECT fee_source, amount, status FROM training_fee_masters ORDER BY FIELD(fee_source, 'PWO', 'PO', 'SO'), fee_source");
    $recentBatches = db_fetch_all($conn, "
        SELECT b.*,
               COALESCE(wc.total_workers, 0) AS total_workers
        FROM training_class_batches b
        LEFT JOIN (
            SELECT batch_id, COUNT(*) AS total_workers
            FROM training_batch_workers
            WHERE ticked = 1
            GROUP BY batch_id
        ) wc ON wc.batch_id = b.id
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT 5
    ");
    $latestBatch = $recentBatches[0] ?? null;
    $batchContractorNameParts = [];
    foreach (['contractor_name', 'vendor_name', 'name'] as $column) {
        if (safetyDashColumnExists($conn, 'contractors', $column)) $batchContractorNameParts[] = "c.`$column`";
    }
    $batchContractorNameParts[] = "CONCAT('Contractor #', w.contractor_id)";
    $batchContractorNameExpr = "COALESCE(" . implode(', ', $batchContractorNameParts) . ")";
    $batchContractorCodeParts = [];
    foreach (['contractor_code', 'vendor_code', 'vendor_id'] as $column) {
        if (safetyDashColumnExists($conn, 'contractors', $column)) $batchContractorCodeParts[] = "c.`$column`";
    }
    $batchContractorCodeParts[] = "CONCAT('C-', w.contractor_id)";
    $batchContractorCodeExpr = "COALESCE(" . implode(', ', $batchContractorCodeParts) . ")";
    $latestBatchWorkers = $latestBatch ? db_fetch_all($conn, "
        SELECT tbw.attempt_no, tbw.status AS batch_worker_status,
               w.id AS workman_id, w.name, w.aadhaar, w.temp_id, w.safety_language,
               $batchContractorCodeExpr AS contractor_code,
               $batchContractorNameExpr AS contractor_name,
               tr.requested_date
        FROM training_batch_workers tbw
        JOIN workmen w ON w.id = tbw.workman_id
        JOIN training_requests tr ON tr.id = tbw.training_request_id
        LEFT JOIN contractors c ON c.id = w.contractor_id
        WHERE tbw.batch_id = ?
        ORDER BY COALESCE(tr.requested_date, DATE(tr.created_at)) ASC, tbw.id ASC
    ", 'i', [(int)$latestBatch['id']]) : [];

    $activitySteps = [
        [
            'icon' => 'fa-envelope-open-text',
            'title' => 'Training Requests',
            'count' => $requestPending,
            'detail' => 'View contractors and workmen requesting Safety Induction.',
            'link' => 'training_requests.php',
            'action' => 'Open Requests',
        ],
        [
            'icon' => 'fa-calendar-plus',
            'title' => 'Schedule Classes',
            'count' => $scheduledWorkers,
            'detail' => 'Enter training date, time, venue, batch and instructor.',
            'link' => 'training_requests.php',
            'action' => 'Assign Batch',
        ],
        [
            'icon' => 'fa-calendar-alt',
            'title' => 'Schedule Changes',
            'count' => $activeSessions,
            'detail' => 'Manage open sessions for postponement, advancement, cancellation and attendee changes.',
            'link' => 'training_schedule.php',
            'action' => 'Manage Schedule',
        ],
        [
            'icon' => 'fa-clipboard-check',
            'title' => 'Conduct Training',
            'count' => $pendingResults,
            'detail' => 'Capture attendance and upload marks/status after the safety class.',
            'link' => 'conduct_results.php',
            'action' => 'Upload Results',
        ],
        [
            'icon' => 'fa-rotate-left',
            'title' => 'Re-Training',
            'count' => $failedWorkers,
            'detail' => 'Track failed workmen and route them for another induction request.',
            'link' => 'retraining.php',
            'action' => 'Review Failed',
        ],
        [
            'icon' => 'fa-id-card',
            'title' => 'Gate Pass Eligibility',
            'count' => $gatePassEligible,
            'detail' => 'Passed workers become available to contractor and Welfare for gate pass processing.',
            'link' => 'training_status.php?status=pass',
            'action' => 'View Eligible',
        ],
    ];

    $dateExpr = $hasSchedule ? safetyDashCol($conn, 'training_schedule', 'ts', 'session_date', 'CURDATE()') : 'CURDATE()';
    $timeExpr = $hasSchedule ? safetyDashCol($conn, 'training_schedule', 'ts', 'session_time', "'00:00:00'") : "'00:00:00'";
    $locationExpr = $hasSchedule ? safetyDashCol($conn, 'training_schedule', 'ts', 'location', "'Training Venue'") : "'Training Venue'";
    $typeExpr = $hasSchedule ? safetyDashCol($conn, 'training_schedule', 'ts', 'training_type', "'induction'") : "'induction'";
    $statusExpr = $hasSchedule ? safetyDashCol($conn, 'training_schedule', 'ts', 'session_status', "'open'") : "'open'";
    $capacityExpr = $hasSchedule ? safetyDashCol($conn, 'training_schedule', 'ts', 'capacity', '0') : '0';

    $workerStatsJoin = '';
    $workerStatsSelect = '0 AS assigned_count, 0 AS present_count, 0 AS result_done_count';
    if ($hasSessionWorkers && safetyDashColumnExists($conn, 'training_session_workers', 'session_id')) {
        $workerStatsJoin = "
            LEFT JOIN (
                SELECT
                    tsw.session_id,
                    COUNT(*) AS assigned_count,
                    SUM(CASE WHEN LOWER(COALESCE(tsw.attendance_status, '')) = 'present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN LOWER(COALESCE(tsw.result, 'pending')) IN ('pass','fail','passed','failed') THEN 1 ELSE 0 END) AS result_done_count
                FROM training_session_workers tsw
                JOIN training_requests tr ON tr.id = tsw.training_request_id
                WHERE tr.status = 'contractor_confirmed'
                GROUP BY tsw.session_id
            ) sws ON sws.session_id = ts.id
        ";
        $workerStatsSelect = "COALESCE(sws.assigned_count, 0) AS assigned_count, COALESCE(sws.present_count, 0) AS present_count, COALESCE(sws.result_done_count, 0) AS result_done_count";
    }

    $upcomingSessions = $hasSchedule ? db_fetch_all($conn, "
        SELECT ts.id, $dateExpr AS session_date, $timeExpr AS session_time, $locationExpr AS location,
               $typeExpr AS training_type, $statusExpr AS session_status, $capacityExpr AS capacity,
               $workerStatsSelect
        FROM training_schedule ts
        $workerStatsJoin
        WHERE LOWER(COALESCE($statusExpr, 'open')) IN ('open', 'scheduled')
        ORDER BY $dateExpr ASC, $timeExpr ASC
        LIMIT 6
    ") : [];

    $recentRequests = [];
    if ($hasRequests && $hasWorkmen) {
        $contractorNameExpr = safetyDashTableExists($conn, 'contractors') && safetyDashColumnExists($conn, 'contractors', 'contractor_name')
            ? 'c.contractor_name'
            : "CONCAT('Contractor #', tr.contractor_id)";
        $workerNameExpr = safetyDashColumnExists($conn, 'workmen', 'name') ? 'w.name' : "CONCAT('Worker #', w.id)";
        $createdExpr = safetyDashColumnExists($conn, 'training_requests', 'created_at') ? 'tr.created_at' : 'tr.id';
        $recentRequests = db_fetch_all($conn, "
            SELECT tr.id, tr.workman_id, COALESCE(tr.status, 'pending') AS status,
                   $workerNameExpr AS worker_name, $contractorNameExpr AS contractor_name
            FROM training_requests tr
            JOIN workmen w ON w.id = tr.workman_id
            LEFT JOIN contractors c ON c.id = tr.contractor_id
            WHERE LOWER(COALESCE(tr.status, 'pending')) IN ('pending', 'welfare_pending', 'failed')
            ORDER BY $createdExpr DESC
            LIMIT 6
        ");
    }
    ?>
    <div class="content-header safety-header">
      <div>
        <h2 class="page-title"><i class="fas fa-helmet-safety"></i> Safety Training Dashboard</h2>
        <p class="page-subtitle">Safety Induction control desk for request scheduling, session changes, attendance, marks, pass/fail results and eligibility.</p>
      </div>
      <div class="safety-actions">
        <a href="training_class_master.php" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Create Batch</a>
        <a href="training_requests.php" class="btn btn-primary"><i class="fas fa-list-check"></i> Requests</a>
        <a href="conduct_results.php" class="btn btn-outline"><i class="fas fa-clipboard-check"></i> Conduct</a>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#d97706"><i class="fas fa-inbox"></i></div>
        <div class="stat-value"><?= $requestPending ?></div>
        <div class="stat-label">Requests to Schedule</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:#2563eb"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-value"><?= $activeSessions ?></div>
        <div class="stat-label">Active Sessions</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(168,85,247,0.1);color:#7c3aed"><i class="fas fa-pen-to-square"></i></div>
        <div class="stat-value"><?= $pendingResults ?></div>
        <div class="stat-label">Marks Pending</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(16,185,129,0.1);color:#059669"><i class="fas fa-check-double"></i></div>
        <div class="stat-value"><?= $completedToday ?></div>
        <div class="stat-label">Completed Today</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:#dc2626"><i class="fas fa-rotate-left"></i></div>
        <div class="stat-value"><?= $failedWorkers ?></div>
        <div class="stat-label">Retraining Required</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(20,184,166,0.1);color:#0f766e"><i class="fas fa-id-card"></i></div>
        <div class="stat-value"><?= $expiringSoon ?></div>
        <div class="stat-label">Certificates Expiring</div>
      </div>
    </div>

    <div class="activity-flow glass">
      <div class="activity-flow-head">
        <div>
          <h3><i class="fas fa-route"></i> Safety Induction Activities</h3>
          <p>Flow aligned to the CSL CLMS scope: receive requests, schedule classes, manage changes, conduct training, publish results and release only passed workers for gate pass processing.</p>
        </div>
      </div>
      <div class="activity-grid">
        <?php foreach ($activitySteps as $idx => $step): ?>
        <a class="activity-card" href="<?= htmlspecialchars($step['link']) ?>">
          <div class="activity-index"><?= $idx + 1 ?></div>
          <div class="activity-icon"><i class="fas <?= htmlspecialchars($step['icon']) ?>"></i></div>
          <div class="activity-body">
            <strong><?= htmlspecialchars($step['title']) ?></strong>
            <span><?= htmlspecialchars($step['detail']) ?></span>
          </div>
          <div class="activity-meta">
            <b><?= (int)$step['count'] ?></b>
            <em><?= htmlspecialchars($step['action']) ?></em>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid grid-2" style="margin-top:20px;gap:20px">
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-clock"></i> Upcoming / Open Sessions</div>
          <a href="training_schedule.php" class="btn btn-sm btn-primary">Plan Session</a>
        </div>
        <div class="card-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Venue</th>
                <th>Workers</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($upcomingSessions as $session):
                $assigned = (int)($session['assigned_count'] ?? 0);
                $done = (int)($session['result_done_count'] ?? 0);
              ?>
              <tr>
                <td>
                  <strong><?= !empty($session['session_date']) ? date('d M Y', strtotime($session['session_date'])) : '-' ?></strong>
                  <div style="font-size:11px;color:var(--text-muted)"><?= !empty($session['session_time']) ? date('H:i', strtotime($session['session_time'])) : '-' ?></div>
                </td>
                <td>
                  <strong><?= htmlspecialchars($session['location'] ?? 'Training Venue') ?></strong>
                  <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars(ucfirst((string)($session['training_type'] ?? 'induction'))) ?></div>
                </td>
                <td><?= $done ?> / <?= $assigned ?> results</td>
                <td><span class="badge badge-info"><?= htmlspecialchars(ucfirst((string)($session['session_status'] ?? 'open'))) ?></span></td>
                <td><a href="manage_session.php?id=<?= (int)$session['id'] ?>" class="btn btn-sm btn-outline">Manage</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($upcomingSessions)): ?>
              <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">No open sessions.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-envelope-open-text"></i> Requests Needing Action</div>
          <a href="training_requests.php" class="btn btn-sm btn-primary">Open Desk</a>
        </div>
        <div class="card-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Contractor</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentRequests as $request):
                $requestStatus = strtolower((string)($request['status'] ?? 'pending'));
                $statusLabelMap = [
                    'welfare_pending' => 'Ready for Scheduling',
                    'pending' => 'Ready for Scheduling',
                    'failed' => 'Retraining Required',
                ];
                $statusLabel = $statusLabelMap[$requestStatus] ?? ucwords(str_replace('_', ' ', $requestStatus));
                $statusClass = $requestStatus === 'failed' ? 'badge-danger' : 'badge-warning';
                $actionLabel = $requestStatus === 'failed' ? 'Re-schedule' : 'Assign Batch';
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($request['worker_name'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($request['contractor_name'] ?? 'N/A') ?></td>
                <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                <td><a href="training_requests.php" class="btn btn-sm btn-outline"><?= htmlspecialchars($actionLabel) ?></a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recentRequests)): ?>
              <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted)">No pending training requests.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="quick-grid">
      <a href="training_class_master.php" class="quick-link"><i class="fas fa-calendar-plus"></i><strong>Training Class Master</strong><span>Create date, venue, language, session, trainer and batch token.</span></a>
      <a href="training_location_master.php" class="quick-link"><i class="fas fa-location-dot"></i><strong>Location Master</strong><span>Maintain training hall code, name, seats and status.</span></a>
      <a href="instructor_master.php" class="quick-link"><i class="fas fa-person-chalkboard"></i><strong>Instructor Master</strong><span>Maintain safety trainer code, name and active status.</span></a>
      <a href="safety_training_type_master.php" class="quick-link"><i class="fas fa-list-check"></i><strong>Training Type Master</strong><span>Maintain HSE induction and other training types.</span></a>
      <a href="training_fee_master.php" class="quick-link"><i class="fas fa-indian-rupee-sign"></i><strong>Fee Master</strong><span>Maintain PWO, PO and SO training fee amounts.</span></a>
      <a href="training_language_master.php" class="quick-link"><i class="fas fa-language"></i><strong>Language Master</strong><span>Maintain Malayalam, English, Kannada, Tamil and more.</span></a>
      <a href="training_requests.php#attachment-requests" class="quick-link"><i class="fas fa-file-alt"></i><strong>Document Attached</strong><span>Schedule workers submitted with approval attachment.</span></a>
      <a href="training_requests.php#eo-approved-requests" class="quick-link"><i class="fas fa-user-check"></i><strong>EO Online Approved</strong><span>Schedule workers approved online without attachment.</span></a>
      <a href="training_schedule.php" class="quick-link"><i class="fas fa-calendar-alt"></i><strong>Manage Schedule</strong><span>Control postponement, advancement, cancellation and attendee updates.</span></a>
      <a href="conduct_results.php" class="quick-link"><i class="fas fa-clipboard-user"></i><strong>Attendance & Marks</strong><span>Save attendance, marks and final pass/fail results.</span></a>
      <a href="retraining.php" class="quick-link"><i class="fas fa-rotate-left"></i><strong>Re-Training</strong><span>Review failed workmen and route repeat induction requests.</span></a>
      <a href="training_batch_report.php" class="quick-link"><i class="fas fa-file-lines"></i><strong>Batch Report</strong><span>Download attendee list in XL or PDF with signature space.</span></a>
    </div>

    <style>
      .safety-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px}
      .safety-header .page-title{display:flex;align-items:center;gap:10px}
      .safety-actions{display:flex;gap:8px;flex-wrap:wrap}
      .activity-flow{margin-top:20px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;overflow:hidden}
      .activity-flow-head{padding:16px 18px;border-bottom:1px solid #e5e7eb;background:#f8fafc}
      .activity-flow-head h3{margin:0;display:flex;align-items:center;gap:8px;font-size:16px;color:#111827}
      .activity-flow-head p{margin:5px 0 0;color:#64748b;font-size:12px;line-height:1.45}
      .activity-grid{display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:0}
      .activity-card{position:relative;display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:flex-start;padding:16px 14px;border-right:1px solid #eef2f7;border-bottom:1px solid #eef2f7;text-decoration:none;color:inherit;background:#fff}
      .activity-card:hover{background:#f8fafc}
      .activity-index{width:24px;height:24px;border-radius:50%;display:grid;place-items:center;background:#eef2ff;color:#3730a3;font-weight:800;font-size:11px}
      .activity-icon{width:34px;height:34px;border-radius:8px;display:grid;place-items:center;background:#eff6ff;color:#2563eb}
      .activity-body{display:flex;flex-direction:column;gap:4px;min-width:0}
      .activity-body strong{font-size:13px;color:#111827}
      .activity-body span{font-size:12px;color:#64748b;line-height:1.35}
      .activity-meta{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
      .activity-meta b{font-size:20px;color:#111827}
      .activity-meta em{font-style:normal;font-size:10px;color:#2563eb;font-weight:800;white-space:nowrap}
      .quick-grid{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin-top:20px}
      .quick-link{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:7px;min-height:108px}
      .quick-link i{font-size:19px;color:var(--primary)}
      .quick-link strong{font-size:13px;color:#111827}
      .quick-link span{font-size:12px;color:#64748b;line-height:1.35}
      .quick-link:hover{border-color:#c7d2fe;background:#f8fafc}
      @media(max-width:1100px){.activity-grid{grid-template-columns:repeat(2,minmax(220px,1fr))}.quick-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}}
      @media(max-width:640px){.safety-header{flex-direction:column;align-items:stretch}.safety-actions .btn,.activity-grid,.quick-grid{grid-template-columns:1fr}.activity-card{grid-template-columns:auto 1fr}.activity-meta{grid-column:2;align-items:flex-start}}
    </style>
    <?php
}

renderLayout("Safety Dashboard", 'renderContent', $role, $name);
?>
