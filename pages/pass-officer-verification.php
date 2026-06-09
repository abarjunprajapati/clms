<?php
session_start();
require_once '../include/config.php';

$_role = $_SESSION['role'] ?? '';
if (!in_array($_role, ['pass_officer', 'admin', 'welfare_user', 'authority'])) {
    header('Location: ../index.php');
    exit('Unauthorized - Pass Officer access required');
}

// Dynamic stats using workflow_status
$pending_count  = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'submitted'");
$verified_count = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'verified' AND DATE(updated_at) = CURDATE()");
$rejected_count = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'rejected' AND DATE(updated_at) = CURDATE()");
$total_verified = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status IN ('verified', 'welfare_approved', 'acc_approved', 'pass_generated')");
$notif_count    = db_count($conn, "SELECT COUNT(*) c FROM notifications WHERE role_target = 'pass_officer' AND is_read = 0");

// PIO verification queue
$pio_queue = db_fetch_all($conn, "
    SELECT a.application_id, a.contractor_name, a.total_workmen, a.category_work, 
           a.workflow_status, a.updated_at, a.created_at, a.project_name,
           (SELECT COUNT(*) FROM workmen w WHERE w.application_id = a.application_id AND w.status = 'active') AS workmen_enrolled
    FROM annexure2a a
    WHERE a.workflow_status = 'submitted'
    ORDER BY a.updated_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pass Issuing Officer – Gate Pass Verification</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-id-badge"></i></div>
    <div>
      <div class="topbar-title">Pass Issuing Officer – Gate Pass Verification</div>
      <div class="topbar-subtitle">Safety Portal · Pass Issuing Officer Role</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-notif"><i class="fas fa-bell" style="font-size:18px"></i><div class="notif-badge"><?= $notif_count ?></div></div>
    <div class="topbar-user">
      <div class="user-avatar">PO</div>
      <div>
        <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Pass Officer') ?></div>
        <div style="font-size:11px;opacity:0.7">Pass Issuing Officer</div>
      </div>
    </div>
  </div>
</div>

<div class="layout-wrapper">
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Gate Pass</div>
      <a href="pass-officer-verification.php" class="sidebar-item active"><i class="fas fa-id-badge"></i> Verification Queue</a>
      <a href="welfare-pass-approval.php" class="sidebar-item"><i class="fas fa-stamp"></i> Welfare Approval</a>
      <a href="acc-approval.php" class="sidebar-item"><i class="fas fa-user-tie"></i> ACC Approval</a>
      <a href="permanent-gatepass.php" class="sidebar-item"><i class="fas fa-door-open"></i> Permanent Pass</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Reports</div>
      <a href="notifications.php" class="sidebar-item"><i class="fas fa-bell"></i> Notifications</a>
      <a href="/clms/api/logout.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="main-content">
    <div class="page-header">
      <div class="page-title">Gate Pass Verification &amp; Validation</div>
      <div class="page-subtitle">Review welfare-approved applications and verify before forwarding to ACC for final approval.</div>
    </div>

    <!-- Dynamic Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-inbox"></i></div>
        <div class="stat-value"><?= $pending_count ?></div><div class="stat-label">Pending Verification</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-check"></i></div>
        <div class="stat-value"><?= $verified_count ?></div><div class="stat-label">Verified Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times"></i></div>
        <div class="stat-value"><?= $rejected_count ?></div><div class="stat-label">Rejected Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-user-check"></i></div>
        <div class="stat-value"><?= $total_verified ?></div><div class="stat-label">Total Passes Verified</div>
      </div>
    </div>

    <!-- Applications Table -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-inbox"></i> Gate Pass Verification Queue</div>
        <div style="display:flex;gap:8px">
          <input class="form-control" style="width:200px" id="searchInput" placeholder="Search contractor..." oninput="filterTable()" />
        </div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table" id="pioTable">
          <thead>
            <tr>
              <th>Ref No.</th>
              <th>Contractor</th>
              <th>Category</th>
              <th>Workmen (DB)</th>
              <th>Submitted On</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pio_queue)): ?>
              <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500)">
                <i class="fas fa-inbox" style="font-size:48px;opacity:0.3;display:block;margin-bottom:12px"></i>
                No gate pass requests pending PIO verification.<br>
                <small>Requests appear here after safety training is completed and Annexure 6/A is submitted.</small>
              </td></tr>
            <?php else: ?>
              <?php foreach ($pio_queue as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars($row['application_id']) ?></strong></td>
                <td><?= htmlspecialchars($row['contractor_name']) ?></td>
                <td><?= htmlspecialchars($row['category_work'] ?? $row['project_name'] ?? 'N/A') ?></td>
                <td><span class="badge badge-info"><?= (int)$row['workmen_enrolled'] ?> enrolled</span></td>
                <td><?= $row['created_at'] ? date('d M Y', strtotime($row['created_at'])) : '-' ?></td>
                <td><span class="badge badge-warning">Pending Verify</span></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="openVerifyPanel('<?= htmlspecialchars($row['application_id']) ?>')">
                    <i class="fas fa-search"></i> Verify
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Verification Panel (shown when row clicked) -->
    <div class="card" id="verifyPanel" style="display:none;margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-search"></i> PIO Verification Dashboard</div>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('verifyPanel').style.display='none'"><i class="fas fa-times"></i></button>
      </div>
      <div class="card-body" id="verifyPanelContent">
        <div style="text-align:center;padding:60px">
          <i class="fas fa-spinner fa-spin" style="font-size:48px;color:var(--primary)"></i>
          <div style="margin-top:20px;font-size:18px;font-weight:500">Loading application data...</div>
        </div>
      </div>
    </div>

  </div><!-- /main-content -->
</div><!-- /layout-wrapper -->

<script src="../js/utils.js"></script>
<script src="../js/navigation.js"></script>
<script>
  let currentAppId = null;

  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#pioTable tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }

  async function openVerifyPanel(appId) {
    if (!appId) return;
    currentAppId = appId;
    const panel   = document.getElementById('verifyPanel');
    const content = document.getElementById('verifyPanelContent');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth' });

    content.innerHTML = '<div style="text-align:center;padding:60px"><i class="fas fa-spinner fa-spin" style="font-size:48px;color:var(--primary)"></i><div style="margin-top:20px">Loading data for ' + appId + '...</div></div>';

    try {
      const res = await window.apiFetch(`get_application_details.php?application_id=${encodeURIComponent(appId)}`);
      
      if (!res.success || !res.data) throw new Error(res.error || 'Failed to load');

      const app      = res.data.application || {};
      const workers  = res.data.workers     || [];
      const workmen  = res.data.workmen     || [];

      content.innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
          <div>
            <h4 style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Application Summary</h4>
            <div class="info-row"><span class="info-label">App ID</span><span class="info-value">${app.application_id || appId}</span></div>
            <div class="info-row"><span class="info-label">Contractor</span><span class="info-value">${app.contractor_name || '-'}</span></div>
            <div class="info-row"><span class="info-label">Category</span><span class="info-value">${app.category_work || app.project_name || '-'}</span></div>
            <div class="info-row"><span class="info-label">Total Workmen</span><span class="info-value">${app.total_workmen || 0}</span></div>
            <div class="info-row"><span class="info-label">Enrolled in DB</span><span class="info-value">${workmen.length} workmen</span></div>
            <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge badge-warning">${(app.workflow_status||'N/A').replace(/_/g,' ')}</span></span></div>
          </div>
          <div>
            <h4 style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Compliance</h4>
            <div class="info-row"><span class="info-label">Labour Licence</span><span class="info-value">${app.labour_license || '-'}</span></div>
            <div class="info-row"><span class="info-label">Validity</span><span class="info-value">${app.labour_validity || '-'}</span></div>
            <div class="info-row"><span class="info-label">EPF Code</span><span class="info-value">${app.epf_code || '-'}</span></div>
            <div class="info-row"><span class="info-label">ESIC Code</span><span class="info-value">${app.esic_code || '-'}</span></div>
            <div class="info-row"><span class="info-label">Supervisors (3A)</span><span class="info-value">${workers.length} records</span></div>
          </div>
        </div>

        <h4 style="font-size:13px;font-weight:600;margin-bottom:10px">Enrolled Workmen (${workmen.length})</h4>
        ${workmen.length > 0 ? `
        <table class="data-table" style="margin-bottom:20px">
          <thead><tr><th>#</th><th>Name</th><th>Role/Trade</th><th>Aadhaar (last 4)</th><th>Temp ID</th></tr></thead>
          <tbody>
            ${workmen.map((w, i) => `<tr>
              <td>${i+1}</td>
              <td>${w.name || '-'}</td>
              <td>${w.trade || w.role || '-'}</td>
              <td>xxxx xxxx ${(w.aadhar||'').slice(-4)}</td>
              <td>${w.temp_id || w.temp_id_issued || '<span class="badge badge-warning">Not issued</span>'}</td>
            </tr>`).join('')}
          </tbody>
        </table>` : '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> No workmen enrolled yet for this application.</div>'}

        <div class="form-group">
          <label class="form-label">PIO Verification Remarks</label>
          <textarea id="pioRemarks" class="form-control" rows="3" placeholder="Enter verification observations..."></textarea>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
          <button class="btn btn-success btn-lg" onclick="forwardToWO()">
            <i class="fas fa-check-circle"></i> Approve &amp; Forward to Welfare
          </button>
          <button class="btn btn-danger" onclick="rejectGP()">
            <i class="fas fa-times-circle"></i> Reject
          </button>
          <button class="btn btn-outline" onclick="document.getElementById('verifyPanel').style.display='none'">
            Cancel
          </button>
        </div>
      `;
    } catch (e) {
      content.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: ' + e.message + '</div>';
    }
  }

  async function forwardToWO() {
    if (!currentAppId) return;
    const remarks = document.getElementById('pioRemarks')?.value?.trim() || 'PIO Verified';
    if (!confirm('Approve and forward application ' + currentAppId + ' to Welfare?')) return;

    try {
      const res = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'verify', remarks })
      });
      if (res.success) {
        alert('✅ Application verified by PIO and forwarded to Welfare.');
        window.location.reload();
      } else {
        alert('Error: ' + (res.error || 'Unknown error'));
      }
    } catch (e) {
      alert('Network error: ' + e.message);
    }
  }

  async function rejectGP() {
    if (!currentAppId) return;
    const reason = prompt('Enter rejection reason:');
    if (!reason) return;

    try {
      const res = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'reject', remarks: reason })
      });
      if (res.success) {
        alert('Application rejected. Contractor has been notified.');
        window.location.reload();
      } else {
        alert('Error: ' + (res.error || 'Unknown error'));
      }
    } catch (e) {
      alert('Network error: ' + e.message);
    }
  }
</script>
</body>
</html>

