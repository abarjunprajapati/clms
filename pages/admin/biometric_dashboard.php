<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $totalACC = db_count($conn, "SELECT COUNT(*) c FROM acc_attendance_map");
    $enrolled = db_count($conn, "SELECT COUNT(*) c FROM acc_attendance_map WHERE biometric_status='ENROLLED'");
    $pending = db_count($conn, "SELECT COUNT(*) c FROM acc_attendance_map WHERE biometric_status='PENDING'");
    $failed = db_count($conn, "SELECT COUNT(*) c FROM acc_attendance_map WHERE biometric_status='FAILED'");
    
    $records = db_fetch_all($conn, "SELECT am.*, w.name as workman_name, w.contractor_id, c.contractor_name FROM acc_attendance_map am LEFT JOIN workmen w ON am.worker_id = w.id LEFT JOIN contractors c ON w.contractor_id = c.id ORDER BY am.updated_at DESC LIMIT 50");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-fingerprint" style="color:#8b5cf6;margin-right:10px;"></i> Biometric Governance</h2>
        <!-- <p class="page-subtitle">ACC-to-Biometric enrollment status, device mapping, and failed enrollments.</p> -->
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $totalACC ?></div><div style="font-size:12px;opacity:0.6;">Total ACC Cards</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#10b981;"><?= $enrolled ?></div><div style="font-size:12px;opacity:0.6;">Enrolled</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $pending ?></div><div style="font-size:12px;opacity:0.6;">Pending</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $failed ?></div><div style="font-size:12px;opacity:0.6;">Failed</div></div>
    </div>

    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> ACC-Biometric Mapping</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>ACC Number</th><th>Worker</th><th>Contractor</th><th>Device ID</th><th>Status</th><th>Last Updated</th></tr></thead>
          <tbody>
          <?php if(empty($records)): ?>
          <tr><td colspan="6" style="text-align:center;padding:30px;opacity:0.5;">No biometric records found.</td></tr>
          <?php else: foreach($records as $r): ?>
          <tr>
            <td><code style="font-weight:700;color:#6366f1;"><?= htmlspecialchars($r['acc_number']) ?></code></td>
            <td><strong><?= htmlspecialchars($r['workman_name'] ?? '-') ?></strong></td>
            <td><?= htmlspecialchars($r['contractor_name'] ?? '-') ?></td>
            <td><code><?= htmlspecialchars($r['attendance_device_id'] ?? 'Unassigned') ?></code></td>
            <td>
              <span class="badge badge-<?= $r['biometric_status']=='ENROLLED'?'success':($r['biometric_status']=='FAILED'?'danger':'warning') ?>">
                <?= $r['biometric_status'] ?>
              </span>
            </td>
            <td><small><?= date('d M Y, H:i', strtotime($r['updated_at'])) ?></small></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Biometric Governance", 'renderContent', $_SESSION['role'], $_SESSION['name']);
