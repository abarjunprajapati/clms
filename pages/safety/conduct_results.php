<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function conductResultsTableExists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $safeTable = clms_db_real_escape_string($conn, $table);
    $result = clms_db_query($conn, "SHOW TABLES LIKE '$safeTable'");
    $cache[$table] = $result && clms_db_num_rows($result) > 0;
    return $cache[$table];
}

function conductResultsColumnExists($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (!conductResultsTableExists($conn, $table)) {
        $cache[$key] = false;
        return false;
    }

    $safeColumn = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && clms_db_num_rows($result) > 0;
    return $cache[$key];
}

function conductResultsCol($conn, $table, $alias, $column, $fallback = 'NULL') {
    return conductResultsColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function renderContent() {
    global $conn;

    if (!conductResultsTableExists($conn, 'training_schedule')) {
        echo '<div class="alert alert-warning">Training schedule table not found. Please create a training session first.</div>';
        return;
    }

    $dateExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'session_date', 'CURDATE()');
    $timeExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'session_time', "'00:00:00'");
    $locationExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'location', "'Training Venue'");
    $typeExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'training_type', "'induction'");
    $trainerExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'trainer_name', "''");
    $statusExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'session_status', "'open'");
    $capacityExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'capacity', '0');
    $enrolledExpr = conductResultsCol($conn, 'training_schedule', 'ts', 'enrolled_count', '0');

    $workerStatsJoin = '';
    $workerStatsSelect = '0 AS assigned_count, 0 AS present_count, 0 AS result_done_count';
    if (conductResultsTableExists($conn, 'training_session_workers') && conductResultsColumnExists($conn, 'training_session_workers', 'session_id')) {
        $attendanceExpr = conductResultsColumnExists($conn, 'training_session_workers', 'attendance_status') ? 'tsw.attendance_status' : "''";
        $resultExpr = conductResultsColumnExists($conn, 'training_session_workers', 'result') ? 'tsw.result' : "'pending'";
        $workerStatsJoin = "
            LEFT JOIN (
                SELECT
                    tsw.session_id,
                    COUNT(*) AS assigned_count,
                    SUM(CASE WHEN LOWER(COALESCE($attendanceExpr, '')) = 'present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN LOWER(COALESCE($resultExpr, 'pending')) IN ('pass','fail','passed','failed') THEN 1 ELSE 0 END) AS result_done_count
                FROM training_session_workers tsw
                JOIN training_requests tr ON tr.id = tsw.training_request_id
                WHERE tr.status = 'contractor_confirmed'
                GROUP BY tsw.session_id
            ) sws ON sws.session_id = ts.id
        ";
        $workerStatsSelect = "COALESCE(sws.assigned_count, 0) AS assigned_count, COALESCE(sws.present_count, 0) AS present_count, COALESCE(sws.result_done_count, 0) AS result_done_count";
    }

    $sessions = db_fetch_all($conn, "
        SELECT
            ts.id,
            $dateExpr AS session_date,
            $timeExpr AS session_time,
            $locationExpr AS location,
            $typeExpr AS training_type,
            $trainerExpr AS trainer_name,
            $statusExpr AS session_status,
            $capacityExpr AS capacity,
            $enrolledExpr AS enrolled_count,
            $workerStatsSelect
        FROM training_schedule ts
        $workerStatsJoin
        WHERE LOWER(COALESCE($statusExpr, 'open')) <> 'cancelled'
        ORDER BY $dateExpr DESC, $timeExpr DESC
    ");

    $openCount = 0;
    $completedCount = 0;
    $pendingResults = 0;
    foreach ($sessions as $session) {
        $status = strtolower((string)($session['session_status'] ?? 'open'));
        if ($status === 'completed') {
            $completedCount++;
        } else {
            $openCount++;
        }
        $pendingResults += max(0, (int)$session['assigned_count'] - (int)$session['result_done_count']);
    }
    ?>
    <div class="content-header conduct-header">
      <div>
        <h2 class="page-title"><i class="fas fa-users-cog"></i> Conduct & Results</h2>
      </div>
      <div class="conduct-actions">
        <a href="training_schedule.php" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Create Session</a>
        <a href="training_requests.php" class="btn btn-primary"><i class="fas fa-envelope-open-text"></i> Training Requests</a>
      </div>
    </div>

    <div class="conduct-stats">
      <div class="conduct-stat"><i class="fas fa-calendar-check"></i><strong><?= count($sessions) ?></strong><span>Total Sessions</span></div>
      <div class="conduct-stat info"><i class="fas fa-door-open"></i><strong><?= $openCount ?></strong><span>Open</span></div>
      <div class="conduct-stat success"><i class="fas fa-lock"></i><strong><?= $completedCount ?></strong><span>Completed</span></div>
      <div class="conduct-stat warning"><i class="fas fa-poll-h"></i><strong><?= $pendingResults ?></strong><span>Pending Results</span></div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Training Sessions</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Session</th>
              <th>Workers</th>
              <th>Attendance</th>
              <th>Results</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sessions as $session):
              $assigned = (int)($session['assigned_count'] ?? 0);
              $present = (int)($session['present_count'] ?? 0);
              $resultDone = (int)($session['result_done_count'] ?? 0);
              $status = strtolower((string)($session['session_status'] ?? 'open'));
              $statusClass = $status === 'completed' ? 'badge-success' : ($status === 'locked' ? 'badge-warning' : 'badge-info');
            ?>
            <tr>
              <td>
                <strong><?= !empty($session['session_date']) ? date('d M Y', strtotime($session['session_date'])) : '-' ?></strong>
                <div style="font-size:11px;color:var(--text-muted)"><?= !empty($session['session_time']) ? date('H:i', strtotime($session['session_time'])) : '-' ?></div>
              </td>
              <td>
                <div style="font-weight:700"><?= htmlspecialchars($session['location'] ?? 'Training Venue') ?></div>
                <div style="font-size:11px;color:var(--text-muted)">
                  <?= htmlspecialchars(ucfirst((string)($session['training_type'] ?? 'induction'))) ?>
                  <?= !empty($session['trainer_name']) ? ' | Trainer: ' . htmlspecialchars($session['trainer_name']) : '' ?>
                </div>
              </td>
              <td><?= $assigned ?> / <?= (int)($session['capacity'] ?: $session['enrolled_count'] ?: $assigned) ?></td>
              <td><?= $present ?> Present</td>
              <td><?= $resultDone ?> / <?= $assigned ?></td>
              <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
              <td>
                <a href="manage_session.php?id=<?= (int)$session['id'] ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-clipboard-check"></i> Conduct
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sessions)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;">No sessions found. Create a session to conduct training.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <style>
      .conduct-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}
      .conduct-header .page-title{display:flex;align-items:center;gap:10px}
      .conduct-actions{display:flex;gap:8px;flex-wrap:wrap}
      .conduct-stats{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:12px;margin-bottom:16px}
      .conduct-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;align-items:center;gap:10px}
      .conduct-stat i{width:34px;height:34px;border-radius:8px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center}
      .conduct-stat.info i{background:#dbeafe;color:#2563eb}
      .conduct-stat.success i{background:#dcfce7;color:#16a34a}
      .conduct-stat.warning i{background:#fef3c7;color:#d97706}
      .conduct-stat strong{font-size:24px;color:#111827}
      .conduct-stat span{font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase}
      @media(max-width:900px){.conduct-stats{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}}
      @media(max-width:640px){.conduct-header{flex-direction:column;align-items:stretch}.conduct-actions .btn{flex:1}}
    </style>
    <?php
}

renderLayout('Conduct & Results', 'renderContent', $role, $name);
?>
