<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $totalComp = db_count($conn, "SELECT COUNT(*) c FROM compliance");
    $verified = db_count($conn, "SELECT COUNT(*) c FROM compliance WHERE status='verified'");
    $pending = db_count($conn, "SELECT COUNT(*) c FROM compliance WHERE status='pending' OR status IS NULL");
    $rejected = db_count($conn, "SELECT COUNT(*) c FROM compliance WHERE status='rejected'");
    
    $esiRate = ($totalComp > 0) ? round(($verified/$totalComp)*100) : 0;
    
    $flagged = db_fetch_all($conn, "SELECT c.*, con.contractor_name FROM compliance c LEFT JOIN contractors con ON c.contractor_id = con.id WHERE c.validation_status != 'passed' AND c.validation_status IS NOT NULL AND c.validation_status != '' ORDER BY c.uploaded_at DESC LIMIT 20");
    
    $alerts = db_fetch_all($conn, "SELECT ca.*, c.contractor_name FROM compliance_alerts ca LEFT JOIN contractors c ON ca.contractor_id = c.id WHERE ca.status='active' ORDER BY ca.created_at DESC LIMIT 10");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-shield-alt" style="color:#10b981;margin-right:10px;"></i> Compliance Governance Dashboard</h2>
        <!-- <p class="page-subtitle">Oversight of ESI, PF, KLWF monthly validations per contractor.</p> -->
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $totalComp ?></div><div style="font-size:12px;opacity:0.6;">Total Submissions</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#10b981;"><?= $verified ?></div><div style="font-size:12px;opacity:0.6;">Verified</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $pending ?></div><div style="font-size:12px;opacity:0.6;">Pending</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $rejected ?></div><div style="font-size:12px;opacity:0.6;">Rejected</div></div>
    </div>

    <?php if(!empty($flagged)): ?>
    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-header"><div class="card-title"><i class="fas fa-flag"></i> Flagged Compliance Submissions</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Contractor</th><th>Type</th><th>Month</th><th>Amount</th><th>Validation</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach($flagged as $f): ?>
          <tr>
            <td><strong><?= htmlspecialchars($f['contractor_name'] ?? '-') ?></strong></td>
            <td><span class="badge badge-outline"><?= strtoupper($f['type'] ?? '-') ?></span></td>
            <td><?= $f['month_year'] ?? '-' ?></td>
            <td>₹<?= number_format($f['amount'] ?? 0, 2) ?></td>
            <td><small style="color:#ef4444;"><?= htmlspecialchars($f['validation_errors'] ?? $f['validation_status'] ?? '-') ?></small></td>
            <td><span class="badge badge-<?= ($f['status']=='verified')?'success':(($f['status']=='rejected')?'danger':'warning') ?>"><?= strtoupper($f['status'] ?? 'PENDING') ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if(!empty($alerts)): ?>
    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i> Active Compliance Alerts</div></div>
      <div class="card-body">
        <?php foreach($alerts as $a): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px;border-bottom:1px solid rgba(0,0,0,0.05);">
          <i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i>
          <div>
            <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($a['contractor_name'] ?? 'Unknown') ?> — <?= htmlspecialchars($a['compliance_type'] ?? '') ?></div>
            <div style="font-size:11px;opacity:0.6;">Expiry: <?= $a['expiry_date'] ?? 'N/A' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php
}

renderLayout("Compliance Dashboard", 'renderContent', $_SESSION['role'], $_SESSION['name']);
