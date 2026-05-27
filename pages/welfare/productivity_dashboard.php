<?php
require_once '../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;

    // Fetch productivity reports with contractor names
    $reports = db_fetch_all($conn, "
        SELECT pr.*, d.dept_name, c.contractor_name 
        FROM productivity_reports pr
        LEFT JOIN master_departments d ON pr.dept_id = d.id
        LEFT JOIN contractors c ON pr.contractor_id = c.id
        ORDER BY pr.report_date DESC
    ");

    // Aggregated stats
    $stats = db_single($conn, "SELECT COUNT(*) as total, SUM(output_qty) as total_qty, SUM(manpower_deployed) as total_manpower FROM productivity_reports");
    
    // Ensure $stats is an array even if table is empty or query fails
    if (!$stats) {
        $stats = ['total' => 0, 'total_qty' => 0, 'total_manpower' => 0];
    } else {
        // Handle NULL results from SUM() on empty table
        $stats['total'] = $stats['total'] ?? 0;
        $stats['total_qty'] = $stats['total_qty'] ?? 0;
        $stats['total_manpower'] = $stats['total_manpower'] ?? 0;
    }
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-chart-line" style="color:#10b981;margin-right:10px;"></i> Productivity Monitoring Dashboard</h2>
        <!-- <p class="page-subtitle">Central oversight of work output across all contractors and departments.</p> -->
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3, 1fr); margin-bottom:24px;">
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Reports Logged</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= number_format((float)($stats['total_manpower'] ?? 0)) ?></div>
        <div class="stat-label">Total Manpower Deployed</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon"><i class="fas fa-tachometer-alt"></i></div>
        <div class="stat-value">
            <?php 
                $avg_eff = ($stats['total_manpower'] > 0) ? ($stats['total_qty'] / $stats['total_manpower']) : 0;
                echo number_format($avg_eff, 2);
            ?>
        </div>
        <div class="stat-label">Avg. Efficiency (Unit/Man-day)</div>
    </div>
</div>

<div class="card glass">
    <div class="card-header"><div class="card-title">Detailed Productivity Logs</div></div>
    <div class="card-body" style="padding:0">
        <table class="data-table" style="width:100%">
            <thead>
                <tr>
                    <th>S.No</th>
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
                <?php foreach ($reports as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d-M-Y', strtotime($r['report_date'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['contractor_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['dept_name']) ?></td>
                    <td><?= htmlspecialchars($r['work_description']) ?></td>
                    <td><?= $r['output_qty'] ?> <?= htmlspecialchars($r['output_unit']) ?></td>
                    <td><?= $r['manpower_deployed'] ?></td>
                    <td>
                        <div style="font-weight:600; color:#10b981;">
                            <?= number_format(($r['manpower_deployed'] > 0 ? $r['output_qty']/$r['manpower_deployed'] : 0), 2) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
}
renderLayout('Productivity Dashboard', 'renderContent', $role, $name);
?>
