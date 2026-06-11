<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safetyReportsTableExists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $safeTable = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
    $cache[$table] = $result && mysqli_num_rows($result) > 0;
    return $cache[$table];
}

function safetyReportsColumnExists($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (!safetyReportsTableExists($conn, $table)) {
        $cache[$key] = false;
        return false;
    }

    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && mysqli_num_rows($result) > 0;
    return $cache[$key];
}

function safetyReportsColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return safetyReportsColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function safetyReportsAddRows($rows, &$reportRows, &$seen) {
    foreach ($rows as $row) {
        $workerId = (int)($row['worker_id'] ?? 0);
        $sourceId = (string)($row['source_id'] ?? '');
        $status = strtolower((string)($row['status'] ?? 'pending'));
        $key = ($workerId ?: 'w0') . '|' . ($sourceId !== '' ? $sourceId : $status . '|' . ($row['report_date'] ?? ''));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $reportRows[] = $row;
    }
}

function safetyReportsStatusBadge($status) {
    $status = strtolower((string)$status);
    if (in_array($status, ['passed', 'pass', 'training_passed', 'completed'], true)) {
        return 'badge-success';
    }
    if (in_array($status, ['failed', 'fail', 'training_failed', 'absent'], true)) {
        return 'badge-danger';
    }
    if (in_array($status, ['scheduled', 'training_scheduled', 'contractor_confirmed'], true)) {
        return 'badge-info';
    }
    return 'badge-warning';
}

function safetyReportsNormalStatus($status) {
    $status = strtolower(trim((string)$status));
    if (in_array($status, ['pass', 'passed', 'training_passed'], true)) return 'passed';
    if (in_array($status, ['fail', 'failed', 'training_failed'], true)) return 'failed';
    if (in_array($status, ['scheduled', 'training_scheduled', 'contractor_confirmed'], true)) return 'scheduled';
    if ($status === '') return 'pending';
    return $status;
}

function safetyReportsRepairConfirmedSessions($conn) {
    if (
        !safetyReportsTableExists($conn, 'training_requests') ||
        !safetyReportsTableExists($conn, 'training_schedule') ||
        !safetyReportsTableExists($conn, 'training_session_workers') ||
        !safetyReportsColumnExists($conn, 'training_requests', 'workman_id') ||
        !safetyReportsColumnExists($conn, 'training_requests', 'scheduled_date') ||
        !safetyReportsColumnExists($conn, 'training_requests', 'scheduled_venue') ||
        !safetyReportsColumnExists($conn, 'training_session_workers', 'training_request_id')
    ) {
        return;
    }

    $sessionIdExpr = safetyReportsColumnExists($conn, 'training_requests', 'scheduled_session_id') ? 'tr.scheduled_session_id' : 'NULL';
    $scheduledTimeExpr = safetyReportsColumnExists($conn, 'training_requests', 'scheduled_time') ? 'tr.scheduled_time' : 'NULL';
    $scheduledShiftExpr = safetyReportsColumnExists($conn, 'training_requests', 'scheduled_shift') ? 'tr.scheduled_shift' : 'NULL';
    $instructorExpr = safetyReportsColumnExists($conn, 'training_requests', 'instructor') ? 'tr.instructor' : 'NULL';
    $batchExpr = safetyReportsColumnExists($conn, 'training_requests', 'batch_number') ? 'tr.batch_number' : 'NULL';

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

            if (safetyReportsColumnExists($conn, 'training_requests', 'scheduled_session_id')) {
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
            if (safetyReportsColumnExists($conn, 'training_schedule', 'enrolled_count')) {
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
        } catch (Throwable $ignored) {
            error_log('[safety reports repair] ' . $ignored->getMessage());
        }
    }
}

function renderContent() {
    global $conn;

    $from_date = $_GET['from_date'] ?? date('Y-m-01');
    $to_date = $_GET['to_date'] ?? date('Y-m-d');
    $application_date = trim((string)($_GET['application_date'] ?? ''));
    $training_date = trim((string)($_GET['training_date'] ?? ''));
    $batch_no = trim((string)($_GET['batch_no'] ?? ''));
    $token_no = trim((string)($_GET['token_no'] ?? ''));
    $aadhaar = trim((string)($_GET['aadhaar'] ?? ''));
    $vendor = trim((string)($_GET['vendor'] ?? ''));
    $global_search = trim((string)($_GET['q'] ?? ''));
    $status_filter = strtolower(trim((string)($_GET['status'] ?? '')));
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $all_trainings = (int)($_GET['all'] ?? 0) === 1;
    $fromDateTime = $from_date . ' 00:00:00';
    $toDateTime = $to_date . ' 23:59:59';

    $contractors = safetyReportsTableExists($conn, 'contractors')
        ? db_fetch_all($conn, "SELECT id, contractor_name AS name FROM contractors ORDER BY contractor_name ASC")
        : [];

    $reportRows = [];
    $seen = [];

    safetyReportsRepairConfirmedSessions($conn);

    if (safetyReportsTableExists($conn, 'training_requests') && safetyReportsColumnExists($conn, 'training_requests', 'workman_id')) {
        $dateExprs = [];
        foreach (['updated_at', 'scheduled_date', 'preferred_date', 'requested_date', 'created_at'] as $column) {
            if (safetyReportsColumnExists($conn, 'training_requests', $column)) {
                $dateExprs[] = "tr.`$column`";
            }
        }
        $dateExpr = $dateExprs ? 'COALESCE(' . implode(', ', $dateExprs) . ')' : 'CURDATE()';
        $statusExpr = safetyReportsColumnSql($conn, 'training_requests', 'tr', 'status', "'pending'");
        $typeExpr = safetyReportsColumnSql($conn, 'training_requests', 'tr', 'training_type', "'induction'");
        $workerName = safetyReportsColumnSql($conn, 'workmen', 'w', 'name', "'Worker'");
        $workerCode = safetyReportsColumnSql($conn, 'workmen', 'w', 'temp_id', "CONCAT('W-', w.id)");
        $workerAadhaar = safetyReportsColumnSql($conn, 'workmen', 'w', 'aadhaar', "''");
        $contractorName = safetyReportsColumnSql($conn, 'contractors', 'c', 'contractor_name', "'N/A'");
        $contractorJoin = safetyReportsColumnExists($conn, 'training_requests', 'contractor_id') ? 'LEFT JOIN contractors c ON tr.contractor_id = c.id' : 'LEFT JOIN contractors c ON w.contractor_id = c.id';
        $attendanceJoin = '';
        $attendanceExpr = 'NULL';

        if (
            safetyReportsTableExists($conn, 'training_session_workers') &&
            safetyReportsColumnExists($conn, 'training_session_workers', 'training_request_id')
        ) {
            $attendanceJoin = "
            LEFT JOIN (
                SELECT training_request_id, MAX(id) AS id
                FROM training_session_workers
                WHERE training_request_id IS NOT NULL
                GROUP BY training_request_id
            ) sw_latest ON tr.id = sw_latest.training_request_id
            LEFT JOIN training_session_workers sw ON sw.id = sw_latest.id";
            $attendanceExpr = safetyReportsColumnSql($conn, 'training_session_workers', 'sw', 'attendance_status', 'NULL');
        }

        $where = $all_trainings ? '1=1' : "$dateExpr BETWEEN ? AND ?";
        $params = $all_trainings ? [] : [$fromDateTime, $toDateTime];
        $types = $all_trainings ? '' : 'ss';
        if ($training_date && safetyReportsColumnExists($conn, 'training_requests', 'scheduled_date')) {
            $where .= ' AND tr.scheduled_date = ?';
            $params[] = $training_date;
            $types .= 's';
        }
        if ($application_date) {
            $where .= ' AND DATE(COALESCE(tr.requested_date, tr.created_at)) = ?';
            $params[] = $application_date;
            $types .= 's';
        }
        if ($aadhaar) {
            $where .= " AND $workerAadhaar LIKE ?";
            $params[] = '%' . $aadhaar . '%';
            $types .= 's';
        }
        if ($vendor) {
            $where .= " AND $contractorName LIKE ?";
            $params[] = '%' . $vendor . '%';
            $types .= 's';
        }
        if ($batch_no && safetyReportsColumnExists($conn, 'training_requests', 'batch_number')) {
            $where .= ' AND tr.batch_number LIKE ?';
            $params[] = '%' . $batch_no . '%';
            $types .= 's';
        }
        if ($status_filter) {
            $where .= " AND LOWER(COALESCE($statusExpr, 'pending')) = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
        if ($contractor_id) {
            $where .= safetyReportsColumnExists($conn, 'training_requests', 'contractor_id') ? ' AND tr.contractor_id = ?' : ' AND w.contractor_id = ?';
            $params[] = $contractor_id;
            $types .= 'i';
        }
        if ($token_no && safetyReportsTableExists($conn, 'training_batch_workers') && safetyReportsColumnExists($conn, 'training_batch_workers', 'token_number')) {
            $where .= ' AND EXISTS (SELECT 1 FROM training_batch_workers tbw_filter WHERE tbw_filter.training_request_id = tr.id AND tbw_filter.token_number LIKE ?)';
            $params[] = '%' . $token_no . '%';
            $types .= 's';
        }

        if ($global_search) {
            $like = '%' . $global_search . '%';
            $searchParts = [
                "$workerName LIKE ?",
                "$workerCode LIKE ?",
                "$workerAadhaar LIKE ?",
                "$contractorName LIKE ?",
                "$typeExpr LIKE ?",
                "$statusExpr LIKE ?"
            ];
            $searchParams = [$like, $like, $like, $like, $like, $like];
            if (safetyReportsColumnExists($conn, 'training_requests', 'batch_number')) {
                $searchParts[] = 'tr.batch_number LIKE ?';
                $searchParams[] = $like;
            }
            if (safetyReportsTableExists($conn, 'training_batch_workers') && safetyReportsColumnExists($conn, 'training_batch_workers', 'token_number')) {
                $searchParts[] = 'EXISTS (SELECT 1 FROM training_batch_workers tbw_search WHERE tbw_search.training_request_id = tr.id AND tbw_search.token_number LIKE ?)';
                $searchParams[] = $like;
            }
            $where .= ' AND (' . implode(' OR ', $searchParts) . ')';
            foreach ($searchParams as $searchParam) {
                $params[] = $searchParam;
                $types .= 's';
            }
        }

        $batchSelect = safetyReportsColumnExists($conn, 'training_requests', 'batch_number') ? 'tr.batch_number' : 'NULL';
        $tokenSelect = (safetyReportsTableExists($conn, 'training_batch_workers') && safetyReportsColumnExists($conn, 'training_batch_workers', 'token_number'))
            ? "(
                SELECT tbw_token.token_number
                FROM training_batch_workers tbw_token
                WHERE tbw_token.training_request_id = tr.id
                ORDER BY tbw_token.id DESC
                LIMIT 1
              )"
            : 'NULL';

        $requestRows = db_fetch_all($conn, "
            SELECT
                tr.id AS source_id,
                w.id AS worker_id,
                $workerName AS worker_name,
                $workerCode AS worker_code,
                $workerAadhaar AS aadhaar,
                c.id AS contractor_id,
                $contractorName AS contractor_name,
                $typeExpr AS training_type,
                $attendanceExpr AS attendance_status,
                $statusExpr AS status,
                $dateExpr AS report_date,
                $batchSelect AS batch_number,
                $tokenSelect AS token_number,
                'Training Request' AS source
            FROM training_requests tr
            JOIN workmen w ON tr.workman_id = w.id
            $contractorJoin
            $attendanceJoin
            WHERE $where
            ORDER BY $dateExpr DESC
        ", $types, $params);
        safetyReportsAddRows($requestRows, $reportRows, $seen);
    }

    if (safetyReportsTableExists($conn, 'workmen') && !$training_date && !$batch_no && !$token_no) {
        $workerDateExprs = [];
        foreach (['updated_at', 'created_at'] as $column) {
            if (safetyReportsColumnExists($conn, 'workmen', $column)) {
                $workerDateExprs[] = "w.`$column`";
            }
        }
        $workerDateExpr = $workerDateExprs ? 'COALESCE(' . implode(', ', $workerDateExprs) . ')' : 'CURDATE()';
        $workerName = safetyReportsColumnSql($conn, 'workmen', 'w', 'name', "'Worker'");
        $workerCode = safetyReportsColumnSql($conn, 'workmen', 'w', 'temp_id', "CONCAT('W-', w.id)");
        $workerAadhaar = safetyReportsColumnSql($conn, 'workmen', 'w', 'aadhaar', "''");
        $trainingStatus = safetyReportsColumnSql($conn, 'workmen', 'w', 'training_status', "'pending'");
        $safetyStatus = safetyReportsColumnSql($conn, 'workmen', 'w', 'safety_training_status', 'NULL');
        $contractorName = safetyReportsColumnSql($conn, 'contractors', 'c', 'contractor_name', "'N/A'");

        $where = $all_trainings ? '1=1' : "$workerDateExpr BETWEEN ? AND ?";
        $params = $all_trainings ? [] : [$fromDateTime, $toDateTime];
        $types = $all_trainings ? '' : 'ss';
        if ($status_filter) {
            $where .= " AND LOWER(COALESCE($trainingStatus, $safetyStatus, 'pending')) = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
        if ($contractor_id) {
            $where .= ' AND w.contractor_id = ?';
            $params[] = $contractor_id;
            $types .= 'i';
        }
        if ($application_date) {
            $where .= " AND DATE($workerDateExpr) = ?";
            $params[] = $application_date;
            $types .= 's';
        }
        if ($aadhaar) {
            $where .= " AND $workerAadhaar LIKE ?";
            $params[] = '%' . $aadhaar . '%';
            $types .= 's';
        }
        if ($vendor) {
            $where .= " AND $contractorName LIKE ?";
            $params[] = '%' . $vendor . '%';
            $types .= 's';
        }
        if ($global_search) {
            $like = '%' . $global_search . '%';
            $where .= " AND ($workerName LIKE ? OR $workerCode LIKE ? OR $workerAadhaar LIKE ? OR $contractorName LIKE ? OR $trainingStatus LIKE ? OR $safetyStatus LIKE ?)";
            foreach ([$like, $like, $like, $like, $like, $like] as $searchParam) {
                $params[] = $searchParam;
                $types .= 's';
            }
        }

        $requestSuppressSql = '';
        if (safetyReportsTableExists($conn, 'training_requests') && safetyReportsColumnExists($conn, 'training_requests', 'workman_id')) {
            $requestSuppressSql = "
              AND NOT EXISTS (
                  SELECT 1
                  FROM training_requests tr2
                  WHERE tr2.workman_id = w.id
                    AND LOWER(COALESCE(tr2.status, 'pending')) IN (
                        'pending_safety', 'welfare_pending', 'pending', 'scheduled', 'contractor_confirmed',
                        'passed', 'failed', 'completed', 'training_scheduled',
                        'training_passed', 'training_failed'
                    )
              )";
        }

        $workerRows = db_fetch_all($conn, "
            SELECT
                CONCAT('workman-', w.id) AS source_id,
                w.id AS worker_id,
                $workerName AS worker_name,
                $workerCode AS worker_code,
                $workerAadhaar AS aadhaar,
                c.id AS contractor_id,
                $contractorName AS contractor_name,
                'induction' AS training_type,
                NULL AS attendance_status,
                COALESCE($trainingStatus, $safetyStatus, 'pending') AS status,
                $workerDateExpr AS report_date,
                NULL AS batch_number,
                NULL AS token_number,
                'Worker Status' AS source
            FROM workmen w
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE $where
              AND LOWER(COALESCE($trainingStatus, $safetyStatus, 'pending')) IN ('pass','passed','fail','failed','training_passed','training_failed','training_pending','training_scheduled','pending','scheduled')
              $requestSuppressSql
            ORDER BY $workerDateExpr DESC
        ", $types, $params);
        safetyReportsAddRows($workerRows, $reportRows, $seen);
    }

    usort($reportRows, function($a, $b) {
        return strtotime($b['report_date'] ?? '') <=> strtotime($a['report_date'] ?? '');
    });

    $stats = ['total_trained' => 0, 'passed' => 0, 'failed' => 0, 'scheduled' => 0, 'pending' => 0];
    foreach ($reportRows as $row) {
        $status = safetyReportsNormalStatus($row['status'] ?? '');
        if ($status === 'passed') {
            $stats['passed']++;
            $stats['total_trained']++;
        } elseif ($status === 'failed') {
            $stats['failed']++;
            $stats['total_trained']++;
        } elseif ($status === 'scheduled') {
            $stats['scheduled']++;
        } else {
            $stats['pending']++;
        }
    }
    $pass_ratio = $stats['total_trained'] > 0 ? round(($stats['passed'] / $stats['total_trained']) * 100, 1) : 0;
    ?>
    <div class="content-header safety-report-header">
      <div>
        <h2 class="page-title"><i class="fas fa-chart-bar"></i> Training Details / All Trainings</h2>
      </div>
      <div class="sr-actions">
        <a href="training_requests.php" class="btn btn-outline"><i class="fas fa-envelope-open-text"></i> Requests</a>
        <a href="reports.php?all=1" class="btn btn-outline"><i class="fas fa-list"></i> All Trainings</a>
        <button type="button" class="btn btn-outline" onclick="exportTrainingReportCsv()"><i class="fas fa-file-excel"></i> XL</button>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>

    <div class="sr-stats">
      <div class="sr-stat"><i class="fas fa-user-check" style="color:#059669"></i><strong><?= (int)$stats['total_trained'] ?></strong><span>Total Trained</span></div>
      <div class="sr-stat"><i class="fas fa-check-circle" style="color:#16a34a"></i><strong><?= (int)$stats['passed'] ?></strong><span>Passed</span></div>
      <div class="sr-stat"><i class="fas fa-times-circle" style="color:#dc2626"></i><strong><?= (int)$stats['failed'] ?></strong><span>Failed</span></div>
      <div class="sr-stat"><i class="fas fa-calendar-check" style="color:#2563eb"></i><strong><?= (int)$stats['scheduled'] ?></strong><span>Scheduled</span></div>
      <div class="sr-stat"><i class="fas fa-percent" style="color:#7c3aed"></i><strong><?= $pass_ratio ?>%</strong><span>Pass Ratio</span></div>
    </div>

    <div class="card glass sr-filter-card">
      <div class="card-body">
        <form method="GET" class="sr-filter-grid">
          <?php if ($all_trainings): ?><input type="hidden" name="all" value="1"><?php endif; ?>
          <div class="form-group sr-wide-field">
            <label class="form-label">Search All Fields</label>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($global_search) ?>" placeholder="Application date, training date, vendor, status, Aadhaar, token">
          </div>
          <div class="form-group">
            <label class="form-label">From Date</label>
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">To Date</label>
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Training Date</label>
            <input type="date" name="training_date" class="form-control" value="<?= htmlspecialchars($training_date) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Application Date</label>
            <input type="date" name="application_date" class="form-control" value="<?= htmlspecialchars($application_date) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Batch No</label>
            <input type="text" name="batch_no" class="form-control" value="<?= htmlspecialchars($batch_no) ?>" placeholder="B20260609001">
          </div>
          <div class="form-group">
            <label class="form-label">Token No</label>
            <input type="text" name="token_no" class="form-control" value="<?= htmlspecialchars($token_no) ?>" placeholder="000001">
          </div>
          <div class="form-group">
            <label class="form-label">Aadhaar</label>
            <input type="text" name="aadhaar" class="form-control" value="<?= htmlspecialchars($aadhaar) ?>" placeholder="Aadhaar no">
          </div>
          <div class="form-group">
            <label class="form-label">Vendor</label>
            <input type="text" name="vendor" class="form-control" value="<?= htmlspecialchars($vendor) ?>" placeholder="Vendor / contractor">
          </div>
          <div class="form-group">
            <label class="form-label">Contractor</label>
            <select name="contractor_id" class="form-control">
              <option value="">All Contractors</option>
              <?php foreach($contractors as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $contractor_id === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] ?? 'N/A') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="">All</option>
              <?php foreach (['pending_safety', 'pending', 'contractor_confirmed', 'scheduled', 'passed', 'failed', 'absent'] as $statusOption): ?>
                <option value="<?= htmlspecialchars($statusOption) ?>" <?= $status_filter === $statusOption ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $statusOption))) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sr-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Generate</button>
            <a href="reports.php" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Training Report Details</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table" id="trainingReportTable">
          <thead>
            <tr>
              <th>Date</th>
              <th>Batch No</th>
              <th>Token No</th>
              <th>Worker</th>
              <th>Contractor</th>
              <th>Type</th>
              <th>Attendance</th>
              <th>Result</th>
              <th>Source</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reportRows as $row):
              $status = safetyReportsNormalStatus($row['status'] ?? '');
              $attendance = $row['attendance_status'] ?: ($status === 'scheduled' ? 'scheduled' : ($status === 'pending' ? 'pending' : 'present'));
            ?>
            <tr>
              <td><?= !empty($row['report_date']) ? date('d M Y', strtotime($row['report_date'])) : '-' ?></td>
              <td><?= htmlspecialchars($row['batch_number'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['token_number'] ?? '') ?></td>
              <td>
                <div style="font-weight:700"><?= htmlspecialchars($row['worker_name'] ?? 'Worker') ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($row['worker_code'] ?? '') ?><?= !empty($row['aadhaar']) ? ' | ' . htmlspecialchars($row['aadhaar']) : '' ?></div>
              </td>
              <td><?= htmlspecialchars($row['contractor_name'] ?? 'N/A') ?></td>
              <td><span class="badge badge-outline"><?= htmlspecialchars(ucfirst((string)($row['training_type'] ?: 'Induction'))) ?></span></td>
              <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $attendance))) ?></td>
              <td><span class="badge <?= safetyReportsStatusBadge($status) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?></span></td>
              <td><span class="badge badge-gray"><?= htmlspecialchars($row['source'] ?? 'Report') ?></span></td>
              <td><button type="button" class="btn btn-sm btn-outline" onclick='openTrainingView(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>View</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($reportRows)): ?>
            <tr><td colspan="10" class="text-center" style="padding:40px;">No data found for the selected period.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="training-view-modal" id="trainingViewModal" style="display:none">
      <div class="training-view-dialog">
        <div class="training-view-head">
          <h3>Training Details</h3>
          <button type="button" class="btn btn-sm btn-outline" onclick="closeTrainingView()">Close</button>
        </div>
        <div class="training-view-grid" id="trainingViewContent"></div>
      </div>
    </div>

    <style>
      .safety-report-header{display:flex;align-items:flex-end;justify-content:space-between;gap:14px;margin-bottom:18px}
      .safety-report-header .page-title{display:flex;align-items:center;gap:10px}
      .sr-actions{display:flex;gap:8px;flex-wrap:wrap}
      .sr-stats{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:12px;margin-bottom:16px}
      .sr-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;align-items:center;gap:10px;box-shadow:0 1px 5px rgba(15,23,42,.05)}
      .sr-stat i{width:32px;height:32px;border-radius:8px;background:#f8fafc;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
      .sr-stat strong{font-size:22px;color:#111827;line-height:1}
      .sr-stat span{font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase}
      .sr-filter-card{margin-bottom:18px}
      .sr-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr)) auto;gap:12px;align-items:end}
      .sr-wide-field{grid-column:span 2}
      .sr-filter-actions{display:flex;gap:8px}
      .training-view-modal{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:9999;align-items:center;justify-content:center;padding:20px}
      .training-view-dialog{background:#fff;border-radius:8px;max-width:760px;width:100%;box-shadow:0 20px 60px rgba(15,23,42,.25);overflow:hidden}
      .training-view-head{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #e5e7eb}
      .training-view-head h3{margin:0;font-size:17px}
      .training-view-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:16px}
      .training-view-item{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#f8fafc}
      .training-view-item span{display:block;font-size:11px;color:#64748b;text-transform:uppercase;font-weight:800;margin-bottom:4px}
      .training-view-item strong{font-size:13px;color:#111827}
      @media(max-width:1000px){.sr-stats{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}.sr-filter-grid{grid-template-columns:1fr 1fr}}
      @media(max-width:640px){.safety-report-header{flex-direction:column;align-items:stretch}.sr-actions,.sr-filter-actions{width:100%}.sr-actions .btn,.sr-filter-actions .btn{flex:1}.sr-filter-grid{grid-template-columns:1fr}}
      @media(max-width:640px){.training-view-grid{grid-template-columns:1fr}}
      @media print{.sidebar,.topbar,.sr-actions,.sr-filter-card{display:none!important}.main-content{margin:0!important}.card{box-shadow:none!important}}
    </style>
    <script>
      function exportTrainingReportCsv() {
        const table = document.getElementById('trainingReportTable');
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tr')).map(row =>
          Array.from(row.children).map(cell => `"${cell.innerText.replace(/"/g, '""').trim()}"`).join(',')
        );
        const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'all-trainings-report.csv';
        a.click();
        URL.revokeObjectURL(url);
      }
      function openTrainingView(row) {
        const modal = document.getElementById('trainingViewModal');
        const content = document.getElementById('trainingViewContent');
        const fields = [
          ['Worker', row.worker_name || '-'],
          ['Aadhaar', row.aadhaar || '-'],
          ['Enrollment No', row.worker_code || '-'],
          ['Vendor / Contractor', row.contractor_name || '-'],
          ['Training Date', row.report_date ? new Date(row.report_date).toLocaleDateString() : '-'],
          ['Batch No', row.batch_number || '-'],
          ['Token No', row.token_number || '-'],
          ['Training Type', row.training_type || '-'],
          ['Attendance', row.attendance_status || '-'],
          ['Status', row.status || '-'],
          ['Source', row.source || '-']
        ];
        content.innerHTML = fields.map(([label, value]) => `<div class="training-view-item"><span>${label}</span><strong>${String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]))}</strong></div>`).join('');
        modal.style.display = 'flex';
      }
      function closeTrainingView() {
        document.getElementById('trainingViewModal').style.display = 'none';
      }
    </script>
    <?php
}

renderLayout("Training Reports", 'renderContent', $role, $name);
