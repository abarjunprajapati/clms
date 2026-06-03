<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
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

function safetyDashCount($conn, $sql, $types = '', $params = []) {
    return db_count($conn, $sql, $types, $params);
}

function renderContent() {
    global $conn;

    $hasRequests = safetyDashTableExists($conn, 'training_requests');
    $hasSchedule = safetyDashTableExists($conn, 'training_schedule');
    $hasSessionWorkers = safetyDashTableExists($conn, 'training_session_workers');
    $hasWorkmen = safetyDashTableExists($conn, 'workmen');

    $requestPending = $hasRequests
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM training_requests WHERE LOWER(COALESCE(status, 'pending')) IN ('pending', 'failed')")
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
    $pendingResults = $hasSessionWorkers
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM training_session_workers WHERE LOWER(COALESCE(attendance_status, 'pending')) = 'present' AND LOWER(COALESCE(result, 'pending')) NOT IN ('pass', 'fail', 'passed', 'failed')")
        : 0;
    $expiringSoon = $hasWorkmen && safetyDashColumnExists($conn, 'workmen', 'training_valid_till')
        ? safetyDashCount($conn, "SELECT COUNT(*) c FROM workmen WHERE training_valid_till BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")
        : 0;

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
                    session_id,
                    COUNT(*) AS assigned_count,
                    SUM(CASE WHEN LOWER(COALESCE(attendance_status, '')) = 'present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN LOWER(COALESCE(result, 'pending')) IN ('pass','fail','passed','failed') THEN 1 ELSE 0 END) AS result_done_count
                FROM training_session_workers
                GROUP BY session_id
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
            WHERE LOWER(COALESCE(tr.status, 'pending')) IN ('pending', 'failed')
            ORDER BY $createdExpr DESC
            LIMIT 6
        ");
    }
    ?>
    <div class="content-header safety-header">
      <div>
        <h2 class="page-title"><i class="fas fa-helmet-safety"></i> Safety Training Dashboard</h2>
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
              <?php foreach ($recentRequests as $request): ?>
              <tr>
                <td><strong><?= htmlspecialchars($request['worker_name'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($request['contractor_name'] ?? 'N/A') ?></td>
                <td><span class="badge <?= strtolower($request['status']) === 'failed' ? 'badge-danger' : 'badge-warning' ?>"><?= strtoupper(htmlspecialchars($request['status'])) ?></span></td>
                <td><a href="training_requests.php" class="btn btn-sm btn-outline">Schedule</a></td>
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
      <a href="training_requests.php" class="quick-link"><i class="fas fa-list-check"></i><strong>Schedule Batch</strong><span>Assign pending workers to training batches.</span></a>
      <a href="conduct_results.php" class="quick-link"><i class="fas fa-clipboard-user"></i><strong>Attendance & Marks</strong><span>Save attendance, theory/practical marks and final results.</span></a>
      <a href="training_status.php" class="quick-link"><i class="fas fa-id-card-clip"></i><strong>Certificate Status</strong><span>Track eligibility and training validity.</span></a>
      <a href="reports.php" class="quick-link"><i class="fas fa-chart-column"></i><strong>Reports</strong><span>Review pass/fail, contractor-wise and audit summaries.</span></a>
    </div>

    <style>
      .safety-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px}
      .safety-header .page-title{display:flex;align-items:center;gap:10px}
      .safety-actions{display:flex;gap:8px;flex-wrap:wrap}
      .quick-grid{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin-top:20px}
      .quick-link{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:7px;min-height:108px}
      .quick-link i{font-size:19px;color:var(--primary)}
      .quick-link strong{font-size:13px;color:#111827}
      .quick-link span{font-size:12px;color:#64748b;line-height:1.35}
      .quick-link:hover{border-color:#c7d2fe;background:#f8fafc}
      @media(max-width:1000px){.quick-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}}
      @media(max-width:640px){.safety-header{flex-direction:column;align-items:stretch}.safety-actions .btn,.quick-grid{grid-template-columns:1fr}}
    </style>
    <?php
}

renderLayout("Safety Dashboard", 'renderContent', $role, $name);
?>
