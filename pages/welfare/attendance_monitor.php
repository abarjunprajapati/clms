<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $date = $_GET['date'] ?? date('Y-m-d');
    $active_tab = $_GET['tab'] ?? 'live';
    
    // Fetch all attendance for the selected date
    $rows = db_fetch_all($conn, "SELECT a.*, w.id as worker_id, w.status as worker_status, c.contractor_name as clms_contractor
        FROM sap_attendance a 
        LEFT JOIN workmen w ON a.acc_no = w.acc_number
        LEFT JOIN contractors c ON w.contractor_id = c.id
        WHERE a.attendance_date = ? 
        ORDER BY a.in_time DESC", 's', [$date]);

    $total_punches = count($rows);
    $present = count(array_filter($rows, function($r) { return !empty($r['in_time']); }));
    $missing_out = count(array_filter($rows, function($r) use ($date) { return !empty($r['in_time']) && empty($r['out_time']) && $date < date('Y-m-d'); }));

    ?>
    <style>
        .nav-tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .tab-btn { padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: 0.2s; border: 1px solid transparent; background: transparent; color: var(--text-muted); }
        .tab-btn.active { background: rgba(16,185,129,0.1); color: #10b981; border-color: rgba(16,185,129,0.2); }
        .tab-btn:hover:not(.active) { background: rgba(255,255,255,0.05); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card { padding: 20px; border-radius: 12px; position: relative; overflow: hidden; }
        .stat-icon { position: absolute; right: 20px; top: 20px; font-size: 24px; opacity: 0.2; }
        .stat-value { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
        .stat-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
    </style>

    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-desktop" style="color:#10b981;margin-right:8px"></i> Attendance Command Center</h2>
            <!-- <p class="page-subtitle">Real-time gate entry & biometric monitoring for all contractors.</p> -->
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="../demo-punch-machine.php" target="_blank" class="btn btn-outline" style="padding:8px 15px; border-radius:8px; font-weight:600; text-decoration:none; display:inline-block; font-size:13px; border:1px solid #10b981; color:#10b981;">
                <i class="fas fa-fingerprint" style="margin-right:5px"></i> Open Punch Terminal
            </a>
            <input type="date" id="datePicker" class="form-control" value="<?= $date ?>" onchange="filterDate()" style="background:var(--input-bg); border:1.5px solid var(--border-color); border-radius:8px; padding:8px 12px; color:var(--text-primary)">
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass" style="border-left: 4px solid #10b981">
            <div class="stat-icon"><i class="fas fa-fingerprint"></i></div>
            <div class="stat-value"><?= $present ?></div><div class="stat-label">Total Present</div>
        </div>
        <div class="stat-card glass" style="border-left: 4px solid #3b82f6">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <div class="stat-value"><?= count(array_unique(array_column($rows, 'contractor_name'))) ?></div><div class="stat-label">Active Contractors</div>
        </div>
        <div class="stat-card glass" style="border-left: 4px solid #ef4444">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value"><?= $missing_out ?></div><div class="stat-label">Missing Punches</div>
        </div>
        <div class="stat-card glass" style="border-left: 4px solid #f59e0b">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= count(array_filter($rows, function($r) { return $r['sap_sync_status'] == 'PENDING'; })) ?></div><div class="stat-label">Sync Pending</div>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="tab-btn <?= $active_tab == 'live' ? 'active' : '' ?>" onclick="switchTab('live')">Live Monitoring</button>
        <button class="tab-btn <?= $active_tab == 'contractor' ? 'active' : '' ?>" onclick="switchTab('contractor')">Contractor Wise</button>
        <button class="tab-btn <?= $active_tab == 'audit' ? 'active' : '' ?>" onclick="switchTab('audit')">Audit & Exceptions</button>
    </div>

    <?php if ($active_tab == 'live'): ?>
        <div class="card glass">
            <div class="card-header"><div class="card-title">Recent Punches - <?= date('d M Y', strtotime($date)) ?></div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead><tr><th>ACC No</th><th>Worker</th><th>Contractor</th><th>In Time</th><th>Out Time</th><th>Status</th><th>Sync</th></tr></thead>
                    <tbody>
                        <?php if(empty($rows)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px; opacity:0.5">No punches recorded for this date</td></tr>
                        <?php else: foreach($rows as $a): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($a['acc_no']) ?></code></td>
                            <td style="font-weight:600"><?= htmlspecialchars($a['worker_name']) ?></td>
                            <td style="font-size:12px"><?= htmlspecialchars($a['clms_contractor'] ?: $a['contractor_name']) ?></td>
                            <td style="color:#10b981; font-weight:600"><?= $a['in_time'] ? date('H:i', strtotime($a['in_time'])) : '-' ?></td>
                            <td style="color:#ef4444; font-weight:600"><?= $a['out_time'] ? date('H:i', strtotime($a['out_time'])) : '-' ?></td>
                            <td><span class="badge <?= $a['punch_status'] == 'OUT' ? 'badge-success' : 'badge-warning' ?>"><?= $a['punch_status'] ?: 'IN' ?></span></td>
                            <td><span class="badge <?= $a['sap_sync_status'] == 'SYNCED' ? 'badge-success' : 'badge-danger' ?>"><?= $a['sap_sync_status'] ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_tab == 'contractor'): ?>
        <div class="card glass">
            <div class="card-header"><div class="card-title">Attendance Summary by Contractor</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead><tr><th>Contractor Name</th><th>Present Today</th><th>Missing Out</th><th>Avg. Work Hours</th></tr></thead>
                    <tbody>
                        <?php
                        $c_summary = [];
                        foreach($rows as $a) {
                            $cn = $a['clms_contractor'] ?: $a['contractor_name'];
                            if (!isset($c_summary[$cn])) $c_summary[$cn] = ['present' => 0, 'missing' => 0, 'hours' => 0, 'count' => 0];
                            $c_summary[$cn]['present']++;
                            if (empty($a['out_time']) && $date < date('Y-m-d')) $c_summary[$cn]['missing']++;
                        }
                        if(empty($c_summary)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:40px; opacity:0.5">No data available</td></tr>
                        <?php else: foreach($c_summary as $name => $s): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($name) ?></td>
                            <td><span class="badge badge-success"><?= $s['present'] ?></span></td>
                            <td><span class="badge <?= $s['missing'] > 0 ? 'badge-danger' : 'badge-info' ?>"><?= $s['missing'] ?></span></td>
                            <td>-</td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($active_tab == 'audit'): ?>
        <div class="card glass">
            <div class="card-header"><div class="card-title">Suspicious Activity & Audit Exceptions</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead><tr><th>Type</th><th>Worker / Details</th><th>Date/Time</th><th>Severity</th></tr></thead>
                    <tbody>
                        <?php
                        $exceptions = array_filter($rows, function($r) use ($date) { return empty($r['out_time']) && $date < date('Y-m-d'); });
                        if(empty($exceptions)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; opacity:0.5">No missing punches detected</td></tr>
                        <?php else: foreach($exceptions as $e): ?>
                        <tr>
                            <td><span class="badge badge-danger">MISSING PUNCH</span></td>
                            <td><strong><?= htmlspecialchars($e['worker_name']) ?></strong> (<?= htmlspecialchars($e['acc_no']) ?>)</td>
                            <td><?= date('d M', strtotime($e['attendance_date'])) ?> - <?= $e['in_time'] ?></td>
                            <td><span class="badge badge-warning">MEDIUM</span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        
                        <!-- Blocked Worker Attempt Simulation -->
                        <?php
                        $blocked_attempts = db_fetch_all($conn, "SELECT w.name, w.acc_number, w.status FROM workmen w WHERE w.status = 'blocked' AND w.acc_number IS NOT NULL LIMIT 2");
                        foreach($blocked_attempts as $ba): ?>
                        <tr style="opacity:0.6">
                            <td><span class="badge badge-danger">BLOCKED ATTEMPT</span></td>
                            <td><?= htmlspecialchars($ba['name']) ?> (<?= htmlspecialchars($ba['acc_number']) ?>)</td>
                            <td><?= date('d M H:i') ?></td>
                            <td><span class="badge badge-danger">HIGH</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <script>
    function switchTab(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.location.href = url.toString();
    }
    function filterDate() {
        const d = document.getElementById('datePicker').value;
        const url = new URL(window.location.href);
        url.searchParams.set('date', d);
        window.location.href = url.toString();
    }
    </script>
    <?php
}

renderLayout("Attendance Monitor", 'renderContent', $_SESSION['role'], $_SESSION['name']);
