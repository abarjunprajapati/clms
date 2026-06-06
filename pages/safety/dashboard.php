<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/training_flow.php';
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
      <a href="training_requests.php" class="quick-link"><i class="fas fa-list-check"></i><strong>Schedule Classes</strong><span>Assign date, time, venue, batch and instructor for each workman.</span></a>
      <a href="training_requests.php#attachment-requests" class="quick-link"><i class="fas fa-file-alt"></i><strong>Document Attached</strong><span>Schedule workers submitted with approval attachment.</span></a>
      <a href="training_requests.php#eo-approved-requests" class="quick-link"><i class="fas fa-user-check"></i><strong>EO Online Approved</strong><span>Schedule workers approved online without attachment.</span></a>
      <a href="training_schedule.php" class="quick-link"><i class="fas fa-calendar-alt"></i><strong>Manage Schedule</strong><span>Control postponement, advancement, cancellation and attendee updates.</span></a>
      <a href="conduct_results.php" class="quick-link"><i class="fas fa-clipboard-user"></i><strong>Attendance & Marks</strong><span>Save attendance, marks and final pass/fail results.</span></a>
      <a href="retraining.php" class="quick-link"><i class="fas fa-rotate-left"></i><strong>Re-Training</strong><span>Review failed workmen and route repeat induction requests.</span></a>
      <a href="training_status.php" class="quick-link"><i class="fas fa-id-card"></i><strong>Eligibility Status</strong><span>Track passed workers, validity and gate pass readiness.</span></a>
      <a href="reports.php" class="quick-link"><i class="fas fa-chart-column"></i><strong>Reports</strong><span>Review pass/fail, contractor-wise and audit summaries.</span></a>
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
