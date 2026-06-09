<?php
session_start();
require_once '../include/config.php';

$_role = $_SESSION['role'] ?? '';
if (!in_array($_role, ['authority', 'execution_officer', 'admin'])) {
    header('Location: ../index.php');
    exit('Unauthorized - ACC access required');
}

// Dynamic stats
$acc_pending    = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'approval_pending'");
$acc_approved   = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'approved' AND MONTH(updated_at)=MONTH(CURDATE())");
$total_passes   = db_count($conn, "SELECT COUNT(*) c FROM permanent_gate_passes WHERE status='active'");
$total_rejected = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='rejected'");
$user_name      = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'ACC Officer';
$user_initials  = strtoupper(substr($user_name, 0, 2));
?>
<!DOCTYPE html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ACC – Final Gate Pass Approval</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-user-tie"></i></div>
    <div>
      <div class="topbar-title">ACC – Final Gate Pass Approval</div>
      <div class="topbar-subtitle">Authority Panel · Area Control Centre</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-notif"><i class="fas fa-bell" style="font-size:18px"></i><div class="notif-badge"><?= $acc_pending ?></div></div>
    <div class="topbar-user">
      <div class="user-avatar"><?= htmlspecialchars($user_initials) ?></div>
      <div><div style="font-size:13px;font-weight:600"><?= htmlspecialchars($user_name) ?></div><div style="font-size:11px;opacity:0.7">Area Control Centre</div></div>
    </div>
  </div>
</div>

<div class="page-container">
  <!-- Workflow Progress -->
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;padding:14px 20px;background:white;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm);flex-wrap:wrap">
    <span class="badge badge-success" style="font-size:11px"><i class="fas fa-check"></i> 6/A Submitted</span>
    <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
    <span class="badge badge-success" style="font-size:11px"><i class="fas fa-check"></i> PO Verified</span>
    <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
    <span class="badge badge-success" style="font-size:11px"><i class="fas fa-check"></i> WO Approved</span>
    <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
    <span class="badge badge-primary" style="font-size:11px"><i class="fas fa-clock"></i> ACC ◄ HERE</span>
    <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
    <span class="badge badge-gray" style="font-size:11px"><i class="fas fa-id-badge"></i> Permanent Pass</span>
  </div>

  <div class="page-header">
    <div class="page-title">Area Control Centre – Final Gate Pass Approval</div>
    <div class="page-subtitle">Final authority decision for issuance of permanent gate passes to contractor workmen.</div>
  </div>

  <!-- Dynamic Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-inbox"></i></div>
      <div class="stat-value"><?= $acc_pending ?></div><div class="stat-label">Awaiting ACC Approval</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-check-circle"></i></div>
      <div class="stat-value"><?= $acc_approved ?></div><div class="stat-label">Approved This Month</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:var(--primary)"><i class="fas fa-id-badge"></i></div>
      <div class="stat-value"><?= $total_passes ?></div><div class="stat-label">Total Passes Issued</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times-circle"></i></div>
      <div class="stat-value"><?= $total_rejected ?></div><div class="stat-label">Rejected</div>
    </div>
  </div>

  <!-- Pending Table -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list"></i> Pending ACC Approval</div>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table">
        <thead>
          <tr><th>Ref</th><th>Contractor</th><th>Workmen</th><th>WO Approved On</th><th>WO</th><th>PO</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php
          $acc_queue = db_fetch_all($conn, "
            SELECT a.*, 
                   (SELECT COUNT(*) FROM workmen w WHERE w.application_id = a.application_id AND w.status='active') AS enrolled_count
            FROM annexure2a a
            WHERE a.workflow_status = 'approval_pending'
            ORDER BY a.updated_at DESC
          ");
          if (count($acc_queue) > 0):
            foreach($acc_queue as $row):
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['application_id'] ?? $row['ref_id']) ?></strong></td>
            <td><?= htmlspecialchars($row['contractor_name']) ?></td>
            <td>
              <?= (int)($row['total_workmen'] ?? 0) ?> planned
              <span class="badge badge-info" style="margin-left:4px"><?= (int)($row['enrolled_count'] ?? 0) ?> enrolled</span>
            </td>
            <td><?= $row['updated_at'] ? date('d M Y', strtotime($row['updated_at'])) : '-' ?></td>
            <td><span class="badge badge-success">Approved</span></td>
            <td><span class="badge badge-success">Verified</span></td>
            <td><span class="badge badge-warning">Pending ACC</span></td>
            <td><button class="btn btn-primary btn-sm" onclick="showACCPanel('<?= htmlspecialchars($row['application_id'] ?? $row['ref_id']) ?>')"><i class="fas fa-gavel"></i> Final Decision</button></td>
          </tr>
          <?php 
            endforeach; 
          else: 
          ?>
          <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500)">No applications pending ACC approval.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ACC Decision Panel -->
  <div id="accPanel" style="display:none">
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-gavel"></i> ACC Final Decision</div>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('accPanel').style.display='none'"><i class="fas fa-times"></i></button>
      </div>
      <div class="card-body" id="acc-panel-content">
        <div style="text-align:center;padding:60px;"><i class="fas fa-spinner fa-spin" style="font-size:48px;color:var(--primary);"></i><div style="margin-top:20px;">Loading...</div></div>
      </div>
    </div>
  </div>
</div>

<script src="../js/utils.js"></script>
<script src="../js/navigation.js"></script>
<script>
  let currentAppId = null;

  async function showACCPanel(appId) {
    if (!appId) return;
    currentAppId = appId;
    document.getElementById('accPanel').style.display = 'block';
    document.getElementById('accPanel').scrollIntoView({ behavior: 'smooth' });

    const content = document.getElementById('acc-panel-content');
    content.innerHTML = '<div style="text-align:center;padding:60px"><i class="fas fa-spinner fa-spin" style="font-size:48px;color:var(--primary)"></i><div style="margin-top:20px">Loading data for ' + appId + '...</div></div>';

    try {
      const res = await window.apiFetch(`get_application_details.php?application_id=${encodeURIComponent(appId)}`);
      
      if (res.success && res.data) {
        const app     = res.data.application || {};
        const workers = res.data.workers     || [];
        const workmen = res.data.workmen     || [];

        content.innerHTML = `
          <div class="alert alert-info" style="margin-bottom:20px">
            <i class="fas fa-check-double"></i>
            <div>
              <strong>Full Workflow Clearance:</strong> All checkpoints cleared.<br />
              ✓ Welfare Authority Approved &nbsp;·&nbsp; ✓ Pass Officer Verified
            </div>
          </div>

          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
            <div style="background:#f0fdf4;border-radius:10px;padding:14px;border:1px solid #86efac">
              <div style="font-size:11px;color:var(--gray-500);font-weight:700;margin-bottom:8px">CONTRACTOR</div>
              <div style="font-weight:700">${app.contractor_name || '-'}</div>
              <div style="font-size:12px;color:var(--gray-500)">${app.epf_code || 'EPF: N/A'}</div>
            </div>
            <div style="background:#f0fdf4;border-radius:10px;padding:14px;border:1px solid #86efac">
              <div style="font-size:11px;color:var(--gray-500);font-weight:700;margin-bottom:8px">WORKMEN</div>
              <div style="font-weight:700">${app.total_workmen || 0} planned / ${workmen.length} enrolled</div>
              <div style="font-size:12px;color:var(--gray-500)">${workers.length} supervisors declared</div>
            </div>
            <div style="background:#f0fdf4;border-radius:10px;padding:14px;border:1px solid #86efac">
              <div style="font-size:11px;color:var(--gray-500);font-weight:700;margin-bottom:8px">VALIDITY</div>
              <div style="font-weight:700">${app.labour_validity ? new Date(app.labour_validity).toLocaleDateString('en-IN') : '-'}</div>
              <div style="font-size:12px;color:var(--gray-500)">Labour licence expiry</div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">ACC Final Remarks <span style="color:var(--danger)">*</span></label>
            <textarea id="acc-remarks" class="form-control" rows="3" placeholder="Enter final approval decision remarks..."></textarea>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn btn-success btn-lg" onclick="accApprove()">
              <i class="fas fa-check-circle"></i> Grant Final Approval &amp; Issue Permanent Gate Passes
            </button>
            <button class="btn btn-danger" onclick="accReject()">
              <i class="fas fa-times-circle"></i> Reject
            </button>
            <button class="btn btn-outline" onclick="document.getElementById('accPanel').style.display='none'">Cancel</button>
          </div>
        `;
      } else {
        content.innerHTML = '<div class="alert alert-danger">Error: ' + (res.error || 'Failed to load application data') + '</div>';
      }
    } catch (e) {
      content.innerHTML = '<div class="alert alert-danger">Network error: ' + e.message + '</div>';
    }
  }

  async function accApprove() {
    if (!currentAppId) return;
    const remarks = document.getElementById('acc-remarks')?.value?.trim() || '';
    if (!remarks) { alert('Please enter approval remarks before granting final approval.'); return; }
    if (!confirm('Grant FINAL approval for ' + currentAppId + '? This will generate permanent gate passes for all enrolled workmen.')) return;

    const content = document.getElementById('acc-panel-content');
    content.innerHTML += '<div id="acc-loading" style="margin-top:10px;color:var(--primary)"><i class="fas fa-spinner fa-spin"></i> Processing...</div>';

    try {
      // Step 1: Update workflow status to acc_approved
      const statusRes = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'acc_approve', remarks })
      });

      if (!statusRes.success) {
        alert('Error updating status: ' + (statusRes.error || 'Unknown error'));
        document.getElementById('acc-loading')?.remove();
        return;
      }

      // Step 2: Generate permanent gate passes for all workmen
      const passRes = await window.apiFetch('generate_permanent_pass.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId })
      });

      if (passRes.success) {
        alert('✅ Final approval granted!\n' + (passRes.data.generated_count || 0) + ' permanent gate passes generated successfully.\n\nRedirecting to pass management page...');
        window.location.href = 'permanent-gatepass.php?id=' + encodeURIComponent(currentAppId);
      } else {
        alert('⚠️ Application approved but pass generation had issues: ' + (passRes.error || 'Unknown error') + '\n\nCheck permanent-gatepass.php for details.');
        window.location.href = 'permanent-gatepass.php?id=' + encodeURIComponent(currentAppId);
      }
    } catch (e) {
      alert('Network error: ' + e.message);
      document.getElementById('acc-loading')?.remove();
    }
  }

  async function accReject() {
    if (!currentAppId) return;
    const reason = prompt('Enter rejection reason:');
    if (!reason || !reason.trim()) return;

    try {
      const res = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'reject', remarks: reason.trim() })
      });
      if (res.success) {
        alert('Application rejected. Contractor and welfare officer will be notified.');
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

