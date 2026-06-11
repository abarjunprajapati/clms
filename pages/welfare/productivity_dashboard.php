<?php
require_once '../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function productivityTableExists($conn, $table) {
    static $cache = [];
    $allowedTables = ['productivity_reports', 'contractors', 'workmen', 'attendance', 'master_departments'];
    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    $cache[$table] = $result && mysqli_num_rows($result) > 0;
    return $cache[$table];
}

function productivityColumnExists($conn, $table, $column) {
    static $cache = [];
    $allowedTables = ['workmen', 'attendance'];
    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && mysqli_num_rows($result) > 0;
    return $cache[$key];
}

function renderContent() {
    global $conn;

    $hasReports = productivityTableExists($conn, 'productivity_reports');
    $hasWorkmen = productivityTableExists($conn, 'workmen');
    $hasAttendance = productivityTableExists($conn, 'attendance');

    $reports = $hasReports ? db_fetch_all($conn, "
        SELECT pr.*, d.dept_name, c.contractor_name
        FROM productivity_reports pr
        LEFT JOIN master_departments d ON pr.dept_id = d.id
        LEFT JOIN contractors c ON pr.contractor_id = c.id
        ORDER BY pr.report_date DESC, pr.id DESC
    ") : [];

    $reportStats = $hasReports ? db_single($conn, "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(output_qty), 0) AS total_qty,
            COALESCE(SUM(manpower_deployed), 0) AS total_manpower
        FROM productivity_reports
    ") : ['total' => 0, 'total_qty' => 0, 'total_manpower' => 0];

    $passConditions = ["w.status IN ('temporary_issued','acc_generated','permanent_active')"];
    foreach (['acc_number', 'temp_pass_no', 'acc_card_number'] as $column) {
        if (productivityColumnExists($conn, 'workmen', $column)) {
            $passConditions[] = "COALESCE(w.`$column`, '') <> ''";
        }
    }
    $passConditionSql = implode(' OR ', $passConditions);
    $attendanceJoin = $hasAttendance ? "LEFT JOIN attendance a ON a.workman_id = w.id AND DATE(a.check_in) = CURDATE()" : "";
    $presentTodayExpr = $hasAttendance ? "COUNT(DISTINCT CASE WHEN DATE(a.check_in) = CURDATE() THEN a.workman_id END)" : "0";

    $liveRows = $hasWorkmen ? db_fetch_all($conn, "
        SELECT
            c.id,
            c.contractor_name,
            c.vendor_code,
            COUNT(DISTINCT w.id) AS total_workers,
            SUM(CASE WHEN w.status NOT IN ('blocked','inactive','removed','rejected') THEN 1 ELSE 0 END) AS active_workers,
            SUM(CASE WHEN $passConditionSql THEN 1 ELSE 0 END) AS pass_workers,
            $presentTodayExpr AS present_today
        FROM contractors c
        LEFT JOIN workmen w ON w.contractor_id = c.id
        $attendanceJoin
        GROUP BY c.id, c.contractor_name, c.vendor_code
        HAVING total_workers > 0 OR pass_workers > 0 OR present_today > 0
        ORDER BY present_today DESC, active_workers DESC, c.contractor_name
    ") : [];

    $totalContractors = count($liveRows);
    $activeWorkers = 0;
    $passWorkers = 0;
    $presentToday = 0;
    foreach ($liveRows as $row) {
        $activeWorkers += (int)($row['active_workers'] ?? 0);
        $passWorkers += (int)($row['pass_workers'] ?? 0);
        $presentToday += (int)($row['present_today'] ?? 0);
    }
    $utilization = $activeWorkers > 0 ? round(($presentToday / $activeWorkers) * 100, 1) : 0;
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-chart-line" style="color:#10b981;margin-right:10px;"></i> Productivity Monitoring Dashboard</h2>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:20px;">
    <i class="fas fa-info-circle"></i>
    <div>Purpose: contractor-wise manpower utilization, active pass workforce, attendance presence, and submitted productivity output tracking.</div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4, 1fr); margin-bottom:24px;">
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div class="stat-value"><?= number_format($totalContractors) ?></div>
        <div class="stat-label">Contractors With Workforce</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= number_format($activeWorkers) ?></div>
        <div class="stat-label">Active Workforce</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-id-card"></i></div>
        <div class="stat-value"><?= number_format($passWorkers) ?></div>
        <div class="stat-label">Pass / ACC Workforce</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-gauge-high"></i></div>
        <div class="stat-value"><?= number_format($utilization, 1) ?>%</div>
        <div class="stat-label">Today Utilization</div>
    </div>
</div>

<div class="card glass" style="margin-bottom:24px;">
    <div class="card-header"><div class="card-title">Contractor-Wise Live Productivity View</div></div>
    <div class="card-body" style="padding:0">
        <table class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Vendor Code</th>
                    <th>Total Workers</th>
                    <th>Active Workforce</th>
                    <th>Pass / ACC</th>
                    <th>Present Today</th>
                    <th>Utilization</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($liveRows as $row):
                    $rowActive = (int)($row['active_workers'] ?? 0);
                    $rowPresent = (int)($row['present_today'] ?? 0);
                    $rowUtil = $rowActive > 0 ? round(($rowPresent / $rowActive) * 100, 1) : 0;
                    $badge = $rowUtil >= 85 ? 'success' : ($rowUtil >= 60 ? 'warning' : 'danger');
                    $label = $rowUtil >= 85 ? 'Good' : ($rowUtil >= 60 ? 'Watch' : 'Low');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['contractor_name'] ?? 'N/A') ?></strong></td>
                    <td><code><?= htmlspecialchars($row['vendor_code'] ?? '-') ?></code></td>
                    <td><?= (int)($row['total_workers'] ?? 0) ?></td>
                    <td><?= $rowActive ?></td>
                    <td><?= (int)($row['pass_workers'] ?? 0) ?></td>
                    <td><?= $rowPresent ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:90px;background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden;">
                                <div style="width:<?= min($rowUtil, 100) ?>%;background:<?= $rowUtil >= 85 ? '#10b981' : ($rowUtil >= 60 ? '#f59e0b' : '#ef4444') ?>;height:100%;"></div>
                            </div>
                            <strong style="font-size:12px;"><?= number_format($rowUtil, 1) ?>%</strong>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= $badge ?>"><?= $label ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($liveRows)): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;">No contractor workforce or attendance data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3, 1fr); margin-bottom:24px;">
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-value"><?= number_format((int)($reportStats['total'] ?? 0)) ?></div>
        <div class="stat-label">Submitted Output Reports</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-users-gear"></i></div>
        <div class="stat-value"><?= number_format((float)($reportStats['total_manpower'] ?? 0)) ?></div>
        <div class="stat-label">Reported Manpower</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-tachometer-alt"></i></div>
        <div class="stat-value">
            <?php
                $avgEff = ((float)($reportStats['total_manpower'] ?? 0) > 0) ? ((float)$reportStats['total_qty'] / (float)$reportStats['total_manpower']) : 0;
                echo number_format($avgEff, 2);
            ?>
        </div>
        <div class="stat-label">Reported Unit / Man-day</div>
    </div>
</div>

<div class="card glass">
    <div class="card-header"><div class="card-title">Submitted Productivity Logs</div></div>
    <div class="card-body" style="padding:0">
        <table class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Contractor</th>
                    <th>Department</th>
                    <th>Work</th>
                    <th>Output</th>
                    <th>Manpower</th>
                    <th>Efficiency</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= !empty($r['report_date']) ? date('d-M-Y', strtotime($r['report_date'])) : '-' ?></td>
                    <td><strong><?= htmlspecialchars($r['contractor_name'] ?? 'N/A') ?></strong></td>
                    <td><?= htmlspecialchars($r['dept_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['work_description'] ?? '-') ?></td>
                    <td><?= htmlspecialchars((string)($r['output_qty'] ?? 0)) ?> <?= htmlspecialchars($r['output_unit'] ?? '') ?></td>
                    <td><?= (int)($r['manpower_deployed'] ?? 0) ?></td>
                    <td><strong style="color:#10b981;"><?= number_format(((float)($r['manpower_deployed'] ?? 0) > 0 ? (float)$r['output_qty'] / (float)$r['manpower_deployed'] : 0), 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reports)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;">No submitted productivity logs yet. Live contractor utilization is shown above.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
}
renderLayout('Productivity Dashboard', 'renderContent', $role, $name);
?>
