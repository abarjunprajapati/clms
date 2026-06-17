<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

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

function renderContent() {
    global $conn;

    $hasSchedule = safetyDashTableExists($conn, 'training_schedule');
    $hasSessionWorkers = safetyDashTableExists($conn, 'training_session_workers');

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
    ") : [];
?>
<div class="content-header safety-header">
  <div>
    <h2 class="page-title"><i class="fas fa-clock"></i> Upcoming / Open Sessions</h2>
    <p class="page-subtitle">View and manage upcoming or open safety training induction sessions.</p>
  </div>
  <div class="safety-actions">
    <a href="training_schedule.php" class="btn btn-primary"><i class="fas fa-calendar-alt"></i> Plan Session</a>
  </div>
</div>

<div class="card glass" style="margin-top:20px;">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-list"></i> Sessions List</div>
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
<?php
}

renderLayout("Upcoming Sessions", 'renderContent', $role, $name);
?>
