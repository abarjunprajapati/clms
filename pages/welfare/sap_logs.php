<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

function renderContent() {
    global $conn;

    $logs = db_fetch_all($conn, "SELECT * FROM sap_logs ORDER BY created_at DESC LIMIT 100");
    $stats = db_single($conn, "SELECT COUNT(*) as total, 
                               SUM(CASE WHEN status='SUCCESS' THEN 1 ELSE 0 END) as success,
                               SUM(CASE WHEN status!='SUCCESS' THEN 1 ELSE 0 END) as failed
                               FROM sap_logs");

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-sync-alt" style="color:#3b82f6;margin-right:10px"></i> SAP Integration Logs</h2>
            <!-- <p class="page-subtitle">Real-time visibility into SAP S/4 HANA simulation workflow activities.</p> -->
        </div>
        <div style="display:flex; gap:10px;">
            <div class="badge badge-success" style="padding:10px 15px;">Success: <?= $stats['success'] ?? 0 ?></div>
            <div class="badge badge-danger" style="padding:10px 15px;">Failed: <?= $stats['failed'] ?? 0 ?></div>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6"><i class="fas fa-database"></i></div>
            <div class="stat-value"><?= db_count($conn, "SELECT COUNT(*) FROM sap_vendors") ?></div>
            <div class="stat-label">SAP Vendors</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= db_count($conn, "SELECT COUNT(*) FROM sap_workers") ?></div>
            <div class="stat-label">SAP Workers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-value"><?= db_count($conn, "SELECT COUNT(*) FROM sap_attendance") ?></div>
            <div class="stat-label">SAP Attendance</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(99,102,241,0.1);color:#6366f1"><i class="fas fa-history"></i></div>
            <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
            <div class="stat-label">Total Sync Logs</div>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-list"></i> Activity Log</div>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Activity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="3" style="text-align:center;padding:40px;color:#9ca3af">No SAP activities logged yet.</td></tr>
                    <?php else: foreach($logs as $log): ?>
                    <tr>
                        <td style="white-space:nowrap"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                        <td><?= htmlspecialchars($log['activity']) ?></td>
                        <td>
                            <span class="badge badge-<?= $log['status'] === 'SUCCESS' ? 'success' : 'danger' ?>">
                                <?= $log['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card glass" style="margin-top:24px">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-network-wired"></i> SAP Connection Status</div>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width:12px; height:12px; border-radius:50%; background:#10b981; box-shadow: 0 0 10px #10b981"></div>
                <div style="font-weight:600; color:#10b981">SAP S/4 HANA MIDDLEWARE CONNECTED</div>
                <div style="margin-left:auto; font-size:12px; color:var(--text-muted)">Last Ping: Just Now</div>
            </div>
            <p style="margin-top:15px; font-size:14px; line-height:1.6">
                The CLMS system is successfully interfaced with the SAP Demo Integration Layer. 
                All worker lifecycle events including vendor validation, enrollment synchronization, 
                and attendance reflection are being simulated in real-time.
            </p>
        </div>
    </div>
    <?php
}

renderLayout("SAP Logs", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
