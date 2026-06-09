<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Core Stats
    $totalUsers = db_count($conn, "SELECT COUNT(*) c FROM users");
    $totalContractors = db_count($conn, "SELECT COUNT(*) c FROM contractors");
    $totalWorkers = db_count($conn, "SELECT COUNT(*) c FROM workmen");
    $activeApps = db_count($conn, "SELECT COUNT(*) c FROM applications WHERE current_status NOT IN ('approved','rejected','completed')");
    $blockedContractors = db_count($conn, "SELECT COUNT(*) c FROM contractors WHERE is_blocked=1 OR status='blocked'");
    $blockedWorkers = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status='blocked'");
    
    // Compliance
    $compTotal = db_count($conn, "SELECT COUNT(*) c FROM compliance");
    $compVerified = db_count($conn, "SELECT COUNT(*) c FROM compliance WHERE status='verified'");
    $compRate = ($compTotal > 0) ? round(($compVerified / $compTotal) * 100) : 0;
    
    // Attendance Trend (Last 7 Days)
    $attendanceTrend = db_fetch_all($conn, "
        SELECT DATE(check_in) as date, COUNT(*) as count 
        FROM attendance 
        WHERE check_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(check_in) 
        ORDER BY date ASC
    ");
    
    // Workforce Distribution
    $workerStatus = db_fetch_all($conn, "SELECT status, COUNT(*) as count FROM workmen GROUP BY status");
    
    // Integration & Health
    $sapFailed = db_count($conn, "SELECT COUNT(*) c FROM sap_sync_queue WHERE sync_status='failed'");
    $expiredPasses = db_count($conn, "SELECT COUNT(*) c FROM gate_passes WHERE valid_to < CURDATE() AND status='active'");
    
    // Recent audit
    $recentAudit = db_fetch_all($conn, "SELECT al.*, u.name as user_name FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 6");
    
    // Lockdown check
    $lockdown = '0';
    $lCheck = clms_db_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if($lCheck && clms_db_num_rows($lCheck) > 0) {
        $ls = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key='system_lockdown'");
        $lockdown = $ls['setting_value'] ?? '0';
    }

    // Schema Check
    $requiredTables = ['system_settings', 'role_permissions', 'roles', 'audit_logs', 'master_trades'];
    $missingTables = [];
    foreach($requiredTables as $tbl) {
        $check = clms_db_query($conn, "SHOW TABLES LIKE '$tbl'");
        if(!$check || clms_db_num_rows($check) == 0) $missingTables[] = $tbl;
    }
    ?>

    <style>
        .stat-card-premium {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .stat-card-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        .stat-card-premium::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--card-color, #6366f1);
        }
        .stat-icon-bg {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 16px;
            background: var(--bg-color, rgba(99,102,241,0.1));
            color: var(--card-color, #6366f1);
        }
        .chart-container { height: 280px; position: relative; }
        .action-link {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px; border-radius: 12px;
            background: #f8fafc; border: 1px solid #e2e8f0;
            color: #1e293b; text-decoration: none; font-weight: 600; font-size: 13px;
            transition: all 0.2s;
        }
        .action-link:hover { background: #fff; border-color: #6366f1; color: #6366f1; transform: translateX(4px); }
        .action-link i { font-size: 16px; width: 24px; text-align: center; }
        
        .compliance-ring {
            position: relative; width: 140px; height: 140px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto;
        }
        .compliance-ring svg { transform: rotate(-90deg); width: 100%; height: 100%; }
        .compliance-ring circle {
            fill: none; stroke-width: 10; stroke-linecap: round;
            transition: stroke-dashoffset 1s ease-out;
        }
        .compliance-ring .bg { stroke: #f1f5f9; }
        .compliance-ring .progress { stroke: var(--comp-color, #10b981); stroke-dasharray: 408; stroke-dashoffset: <?= 408 - (408 * $compRate / 100) ?>; }
        
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .lockdown-alert { animation: pulse-red 2s infinite; }
    </style>

    <?php if(!empty($missingTables)): ?>
    <div style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:18px 28px;border-radius:16px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:15px;box-shadow:0 10px 25px rgba(217,119,6,0.2);">
      <div style="display:flex;align-items:center;gap:15px;">
        <i class="fas fa-database" style="font-size:28px;"></i>
        <div><strong style="font-size:17px;">SCHEMA INCOMPLETE</strong><br><span style="opacity:0.9;font-size:13px;">Missing tables: <?= implode(', ', $missingTables) ?>. Critical features may be unavailable.</span></div>
      </div>
      <a href="<?= BASE_URL ?>api/admin/init_admin_schema.php" target="_blank" class="btn" style="background:#fff;color:#d97706;font-weight:800;padding:10px 20px;"><i class="fas fa-magic"></i> Initialize Now</a>
    </div>
    <?php endif; ?>

    <?php if($lockdown == '1'): ?>
    <div class="lockdown-alert" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:18px 28px;border-radius:16px;margin-bottom:24px;display:flex;align-items:center;gap:15px;">
      <i class="fas fa-shield-virus" style="font-size:28px;"></i>
      <div><strong style="font-size:17px;">SYSTEM LOCKDOWN ACTIVE</strong><br><span style="opacity:0.9;font-size:13px;">All external operations and registrations are currently paused by the administrator.</span></div>
      <a href="<?= BASE_URL ?>pages/admin/settings.php" class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff;margin-left:auto;border:1px solid #fff;">Update Settings</a>
    </div>
    <?php endif; ?>

    <div class="content-header" style="margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end;">
      <div>
        <h2 class="page-title" style="font-size:28px;"><i class="fas fa-crown" style="color:#f59e0b;margin-right:12px;"></i> Command Center</h2>
        <!-- <p class="page-subtitle" style="font-size:15px;">System-wide governance, workforce oversight, and real-time integration monitoring.</p> -->
      </div>
      <div style="display:flex; gap:12px;">
        <div style="text-align:right; font-size:12px; color:#64748b; margin-right:10px;">
            <div style="font-weight:700; color:#1e293b;"><i class="fas fa-circle" style="color:#10b981; font-size:8px; margin-right:5px;"></i> SYSTEM ONLINE</div>
            <div>Last Sync: <?= date('H:i:s') ?></div>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:32px;">
      <div class="stat-card-premium" style="--card-color:#6366f1; --bg-color:rgba(99,102,241,0.1);">
        <div class="stat-icon-bg"><i class="fas fa-users-cog"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1e293b;"><?= number_format($totalUsers) ?></div>
        <div style="font-size:13px; color:#64748b; font-weight:600; margin-top:4px;">System Operators</div>
      </div>
      <div class="stat-card-premium" style="--card-color:#10b981; --bg-color:rgba(16,185,129,0.1);">
        <div class="stat-icon-bg"><i class="fas fa-building"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1e293b;"><?= number_format($totalContractors) ?></div>
        <div style="font-size:13px; color:#64748b; font-weight:600; margin-top:4px;">Active Contractors</div>
      </div>
      <div class="stat-card-premium" style="--card-color:#0284c7; --bg-color:rgba(2,132,199,0.1);">
        <div class="stat-icon-bg"><i class="fas fa-user-hard-hat"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1e293b;"><?= number_format($totalWorkers) ?></div>
        <div style="font-size:13px; color:#64748b; font-weight:600; margin-top:4px;">Enrolled Workforce</div>
      </div>
      <div class="stat-card-premium" style="--card-color:#f59e0b; --bg-color:rgba(245,158,11,0.1);">
        <div class="stat-icon-bg"><i class="fas fa-file-signature"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1e293b;"><?= number_format($activeApps) ?></div>
        <div style="font-size:13px; color:#64748b; font-weight:600; margin-top:4px;">Pending Approvals</div>
      </div>
    </div>

    <!-- Main Analytics Row -->
    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px; margin-bottom:32px;">
      
      <!-- Attendance Trend Chart -->
      <div class="card glass" style="padding:24px; border-radius:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div style="font-weight:700; font-size:16px;"><i class="fas fa-chart-line" style="color:#6366f1; margin-right:8px;"></i> Attendance Velocity (7 Days)</div>
            <a href="<?= BASE_URL ?>pages/admin/attendance_dashboard.php" style="font-size:12px; color:#6366f1; font-weight:600;">Details <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="chart-container">
            <canvas id="attendanceChart"></canvas>
        </div>
      </div>

      <!-- Compliance & Status -->
      <div style="display:flex; flex-direction:column; gap:24px;">
        <div class="card glass" style="padding:24px; border-radius:20px; text-align:center;">
            <div style="font-weight:700; font-size:15px; margin-bottom:20px; text-align:left;"><i class="fas fa-shield-check" style="color:#10b981; margin-right:8px;"></i> Compliance Health</div>
            <div class="compliance-ring">
                <svg viewBox="0 0 140 140">
                    <circle class="bg" cx="70" cy="70" r="65"></circle>
                    <circle class="progress" cx="70" cy="70" r="65" style="--comp-color: <?= $compRate > 80 ? '#10b981' : ($compRate > 50 ? '#f59e0b' : '#ef4444') ?>"></circle>
                </svg>
                <div style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <div style="font-size:28px; font-weight:800; color:#1e293b;"><?= $compRate ?>%</div>
                    <div style="font-size:11px; font-weight:600; color:#64748b;">VERIFIED</div>
                </div>
            </div>
            <div style="margin-top:15px; font-size:12px; color:#64748b;">
                <strong><?= $compVerified ?></strong> of <strong><?= $compTotal ?></strong> entities verified.
            </div>
        </div>

        <div class="card glass" style="padding:20px; border-radius:20px;">
            <div style="font-weight:700; font-size:14px; margin-bottom:15px;"><i class="fas fa-heartbeat" style="color:#ef4444; margin-right:8px;"></i> Integration Vitals</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <div style="background:#fef2f2; padding:12px; border-radius:12px; text-align:center; border:1px solid #fee2e2;">
                    <div style="font-size:18px; font-weight:800; color:#ef4444;"><?= $sapFailed ?></div>
                    <div style="font-size:10px; font-weight:700; color:#991b1b;">SAP FAILS</div>
                </div>
                <div style="background:#fffbeb; padding:12px; border-radius:12px; text-align:center; border:1px solid #fef3c7;">
                    <div style="font-size:18px; font-weight:800; color:#d97706;"><?= $expiredPasses ?></div>
                    <div style="font-size:10px; font-weight:700; color:#92400e;">EXPIRED PASS</div>
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Audit & Quick Actions -->
    <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:24px;">
        
        <!-- Audit Trail -->
        <div class="card glass" style="border-radius:20px; overflow:hidden;">
            <div class="card-header" style="padding:20px 24px;">
                <div class="card-title" style="font-size:15px;"><i class="fas fa-history" style="color:#6366f1;"></i> Governance Audit Trail</div>
                <a href="<?= BASE_URL ?>pages/admin/audit_logs.php" class="btn btn-sm btn-outline">View Full Log</a>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead><tr><th>User</th><th>Action</th><th>Target</th><th>Module</th><th>Time</th></tr></thead>
                    <tbody>
                    <?php foreach($recentAudit as $a): ?>
                    <tr>
                        <td><div style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($a['user_name'] ?? 'System') ?></div></td>
                        <td><span style="font-size:12px;"><?= htmlspecialchars($a['action'] ?? '-') ?></span></td>
                        <td><code style="font-size:11px; background:#f1f5f9; padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($a['target_id'] ?? 'N/A') ?></code></td>
                        <td><span class="badge badge-outline" style="font-size:10px;"><?= htmlspecialchars($a['module'] ?? '-') ?></span></td>
                        <td><small style="color:#64748b; font-weight:600;"><?= date('H:i, d M', strtotime($a['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Controls -->
        <div class="card glass" style="padding:24px; border-radius:20px;">
            <div style="font-weight:700; font-size:15px; margin-bottom:20px;"><i class="fas fa-bolt" style="color:#f59e0b; margin-right:8px;"></i> Administrator Controls</div>
            <div style="display:grid; grid-template-columns: 1fr; gap:12px;">
                <a href="<?= BASE_URL ?>pages/admin/create_user.php" class="action-link"><i class="fas fa-user-plus" style="color:#6366f1;"></i> Provision New User</a>
                <a href="<?= BASE_URL ?>pages/admin/workflow_control.php" class="action-link"><i class="fas fa-gamepad" style="color:#f59e0b;"></i> Workflow Intervention</a>
                <a href="<?= BASE_URL ?>pages/admin/contractor_control.php" class="action-link"><i class="fas fa-building-circle-exclamation" style="color:#ef4444;"></i> Entity Blocking Control</a>
                <a href="<?= BASE_URL ?>pages/admin/master_data.php" class="action-link"><i class="fas fa-database" style="color:#0284c7;"></i> Master Data Sync</a>
                <a href="<?= BASE_URL ?>pages/admin/settings.php" class="action-link"><i class="fas fa-cog" style="color:#64748b;"></i> System Configurations</a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        // Data Preparation
        const rawData = <?= json_encode($attendanceTrend) ?>;
        const labels = rawData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
        });
        const values = rawData.map(d => d.count);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Entry Count',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0,0,0,0.03)' },
                        ticks: { font: { size: 11 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    });
    </script>

    <?php
}

renderLayout("Admin Dashboard", 'renderContent', $_SESSION['role'], $_SESSION['name']);
