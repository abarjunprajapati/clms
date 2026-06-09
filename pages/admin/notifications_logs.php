<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $logCheck = clms_db_query($conn, "SHOW TABLES LIKE 'notification_logs'");
    $hasTable = ($logCheck && clms_db_num_rows($logCheck) > 0);
    
    $logs = [];
    $stats = ['sms'=>0,'email'=>0,'system'=>0,'push'=>0,'sent'=>0,'failed'=>0];
    if ($hasTable) {
        $logs = db_fetch_all($conn, "SELECT * FROM notification_logs ORDER BY created_at DESC LIMIT 50");
        $stats['sms'] = db_count($conn, "SELECT COUNT(*) c FROM notification_logs WHERE channel='sms'");
        $stats['email'] = db_count($conn, "SELECT COUNT(*) c FROM notification_logs WHERE channel='email'");
        $stats['system'] = db_count($conn, "SELECT COUNT(*) c FROM notification_logs WHERE channel='system'");
        $stats['sent'] = db_count($conn, "SELECT COUNT(*) c FROM notification_logs WHERE status IN ('sent','delivered')");
        $stats['failed'] = db_count($conn, "SELECT COUNT(*) c FROM notification_logs WHERE status='failed'");
    }
    
    // Also check notifications table
    $sysNotifs = db_fetch_all($conn, "SELECT n.*, u.name as user_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 30");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-bell" style="color:#f59e0b;margin-right:10px;"></i> Notification & Communication Logs</h2>
        <p class="page-subtitle">Track SMS, email, and system notification delivery status.</p>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:16px;text-align:center;"><div style="font-size:24px;font-weight:800;color:#6366f1;"><?= $stats['sms'] ?></div><div style="font-size:11px;opacity:0.6;">SMS Sent</div></div>
      <div class="card glass" style="padding:16px;text-align:center;"><div style="font-size:24px;font-weight:800;color:#8b5cf6;"><?= $stats['email'] ?></div><div style="font-size:11px;opacity:0.6;">Emails</div></div>
      <div class="card glass" style="padding:16px;text-align:center;"><div style="font-size:24px;font-weight:800;color:#0284c7;"><?= $stats['system'] ?></div><div style="font-size:11px;opacity:0.6;">System</div></div>
      <div class="card glass" style="padding:16px;text-align:center;"><div style="font-size:24px;font-weight:800;color:#10b981;"><?= $stats['sent'] ?></div><div style="font-size:11px;opacity:0.6;">Delivered</div></div>
      <div class="card glass" style="padding:16px;text-align:center;"><div style="font-size:24px;font-weight:800;color:#ef4444;"><?= $stats['failed'] ?></div><div style="font-size:11px;opacity:0.6;">Failed</div></div>
    </div>

    <!-- System Notifications -->
    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i> System Notifications (<?= count($sysNotifs) ?>)</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>User</th><th>Title</th><th>Type</th><th>Read</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach($sysNotifs as $n): ?>
          <tr>
            <td><?= htmlspecialchars($n['user_name'] ?? 'System') ?></td>
            <td><strong><?= htmlspecialchars($n['title'] ?? '-') ?></strong></td>
            <td><span class="badge badge-info"><?= strtoupper($n['type'] ?? 'INFO') ?></span></td>
            <td><?= $n['is_read'] ? '<span class="badge badge-success">Read</span>' : '<span class="badge badge-warning">Unread</span>' ?></td>
            <td><small><?= date('d M, H:i', strtotime($n['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($sysNotifs)): ?>
          <tr><td colspan="5" style="text-align:center;padding:30px;opacity:0.5;">No notifications yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if(!empty($logs)): ?>
    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Communication Delivery Logs</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Recipient</th><th>Channel</th><th>Type</th><th>Status</th><th>Error</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach($logs as $l): ?>
          <tr>
            <td><?= htmlspecialchars($l['recipient'] ?? '-') ?></td>
            <td><span class="badge badge-<?= $l['channel']=='sms'?'info':($l['channel']=='email'?'purple':'gray') ?>"><?= strtoupper($l['channel']) ?></span></td>
            <td><?= htmlspecialchars($l['type'] ?? '-') ?></td>
            <td><span class="badge badge-<?= in_array($l['status'],['sent','delivered'])?'success':'danger' ?>"><?= strtoupper($l['status']) ?></span></td>
            <td><small style="color:#ef4444;"><?= htmlspecialchars($l['error_message'] ?? '-') ?></small></td>
            <td><small><?= date('d M, H:i', strtotime($l['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
    <?php
}

renderLayout("Notifications Log", 'renderContent', $_SESSION['role'], $_SESSION['name']);
