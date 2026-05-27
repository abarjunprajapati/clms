<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get Officer ID
$officerRes = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = (SELECT contractor_id FROM users WHERE id = ?)", 'i', [$userId]);
$officerId = $officerRes['id'] ?? 0;

function renderContent() {
    global $conn, $officerId;

    // Productivity Stats by Contractor (Real-time calculation)
    $stats = db_fetch_all($conn, "SELECT c.id, c.contractor_name, 
                                 (SELECT COUNT(*) FROM execution_worker_deployments WHERE contractor_id = c.id AND execution_officer_id = ? AND status = 'active') as deployed,
                                 (SELECT COUNT(DISTINCT workman_id) FROM attendance WHERE workman_id IN (SELECT workman_id FROM execution_worker_deployments WHERE contractor_id = c.id AND execution_officer_id = ? AND status = 'active') AND DATE(check_in) = CURDATE()) as present
                                 FROM contractors c 
                                 JOIN execution_officer_contractors eoc ON c.id = eoc.contractor_id 
                                 WHERE eoc.execution_officer_id = ?", 'iii', [$officerId, $officerId, $officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-chart-line" style="color:#10b981;margin-right:8px"></i>Productivity Intelligence</h2>
            <!-- <p class="page-subtitle">Analyze manpower utilization, contractor efficiency, and deployment performance.</p> -->
        </div>
        <div class="action-buttons">
            <button class="btn btn-outline" onclick="location.href='reports.php'"><i class="fas fa-file-invoice"></i> Efficiency Report</button>
        </div>
    </div>

    <!-- Efficiency Scorecards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-bottom:24px;">
        <div class="card glass">
            <div class="card-header"><div class="card-title">Manpower Utilization Trend</div></div>
            <div class="card-body">
                <canvas id="utilizationTrendChart" height="200"></canvas>
            </div>
        </div>
        <div class="card glass">
            <div class="card-header"><div class="card-title">Contractor Efficiency Ranking</div></div>
            <div class="card-body">
                <canvas id="efficiencyRankChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Vendor Performance Matrix (Live)</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contractor</th>
                        <th>Planned (Deployed)</th>
                        <th>Actual (Present)</th>
                        <th>Idle/Variance</th>
                        <th>Efficiency %</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($stats)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#64748b">No contractors assigned yet.</td></tr>
                    <?php else: foreach($stats as $s): 
                        $variance = $s['deployed'] - $s['present'];
                        $efficiency = ($s['deployed'] > 0) ? round(($s['present'] / $s['deployed']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['contractor_name']) ?></strong></td>
                            <td><?= $s['deployed'] ?></td>
                            <td><?= $s['present'] ?></td>
                            <td><span class="text-<?= $variance > 0 ? 'danger' : 'success' ?>"><?= $variance ?></span></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px">
                                    <div style="flex-grow:1; background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden">
                                        <div style="width:<?= $efficiency ?>%; background:<?= $efficiency < 70 ? '#ef4444' : ($efficiency < 90 ? '#f59e0b' : '#10b981') ?>; height:100%"></div>
                                    </div>
                                    <span style="font-size:12px; font-weight:700; width:40px"><?= $efficiency ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $efficiency < 70 ? 'danger' : ($efficiency < 90 ? 'warning' : 'success') ?>">
                                    <?= $efficiency < 70 ? 'CRITICAL' : ($efficiency < 90 ? 'SATISFACTORY' : 'EXCELLENT') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Mock data for trend (as we don't have historical logs yet)
        const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        
        new Chart(document.getElementById('utilizationTrendChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Efficiency %',
                    data: [85, 88, 76, 92, 89, 95, 91],
                    borderColor: '#6366f1',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)'
                }]
            },
            options: { scales: { y: { beginAtZero: true, max: 100 } } }
        });

        new Chart(document.getElementById('efficiencyRankChart'), {
            type: 'radar',
            data: {
                labels: <?= json_encode(array_map(function($s) { return $s['contractor_name']; }, $stats)) ?>,
                datasets: [{
                    label: 'Performance Index',
                    data: <?= json_encode(array_map(function($s) { return ($s['deployed'] > 0 ? ($s['present']/$s['deployed'])*100 : 0); }, $stats)) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: '#10b981',
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: { scales: { r: { beginAtZero: true, max: 100 } } }
        });
    });
    </script>
    <?php
}

renderLayout("Productivity Intelligence", 'renderContent', $role, $name);
?>
