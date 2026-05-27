<?php
session_start();
include '../include/config.php';

if (!isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit('Unauthorized');
}

$role = $_SESSION['role'];
$passes_data = db_fetch_all($conn, "
  SELECT 
    pgp.id, pgp.pass_no, pgp.valid_from, pgp.valid_till, pgp.created_at,
    w.name as worker_name, w.trade, w.temp_id as worker_temp_id,
    a.contractor_name, a.application_id as contract_id, a.workflow_status
  FROM permanent_gate_passes pgp
  JOIN workmen w ON pgp.worker_id = w.id
  INNER JOIN annexure2a a ON pgp.application_id = a.application_id AND a.workflow_status IN ('completed', 'pass_generated', 'permanent_pass_issued', 'acc_approved')
  WHERE pgp.status = 'active'
  ORDER BY pgp.created_at DESC
");

$passes = $passes_data ?: [];
$approved_passes_count = count($passes);
$pending_generation = db_fetch_all($conn, "SELECT application_id, contractor_name, workflow_status, updated_at FROM annexure2a WHERE workflow_status IN ('final_approval_pending', 'acc_approved') ORDER BY updated_at DESC");
$notif_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Permanent Gate Pass Issuance</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white; }
    }
    .pass-grid { display: flex; flex-wrap: wrap; gap: 20px; padding: 8px; }
    .gate-pass {
      width: 360px;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      background: var(--white);
      border: 2px solid var(--primary);
    }
    .gate-pass-header {
      background: linear-gradient(135deg, #1a3c6e 0%, #0ea5e9 100%);
      color: white;
      padding: 14px 18px;
      text-align: center;
    }
    .gate-pass-body { padding: 14px 18px; }
    .pass-number {
      font-size: 26px;
      font-weight: 900;
      color: var(--primary);
      font-family: 'Courier New', monospace;
      letter-spacing: 3px;
      text-align: center;
    }
    .qr-placeholder {
      width: 80px; height: 80px;
      background: var(--gray-100);
      border: 1px solid var(--gray-300);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
    }
    .hologram {
      width: 30px; height: 30px;
      background: linear-gradient(135deg, #f59e0b, #10b981, #3b82f6, #8b5cf6);
      border-radius: 50%;
      opacity: 0.7;
    }
  </style>
</head>
<body>
<div class="topbar no-print">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-id-badge"></i></div>
    <div>
      <div class="topbar-title">Permanent Gate Pass Issuance</div>
      <div class="topbar-subtitle">Contractor Portal · Gate Pass Module</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="contractor-dashboard.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-home"></i> Dashboard</a>
<div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'RC', 0,2)) ?></div>
  </div>
</div>

<div class="page-container">
  <!-- Success Banner -->
  <?php if ($approved_passes_count > 0): 
    $first_pass = $passes[0];
  ?>
  <div style="background:linear-gradient(135deg,#065f46,#10b981);border-radius:16px;padding:24px;color:white;margin-bottom:24px;display:flex;align-items:center;gap:20px" class="no-print">
    <div style="width:70px;height:70px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0">
      <i class="fas fa-check-circle"></i>
    </div>
    <div style="flex:1">
      <div style="font-size:22px;font-weight:800;margin-bottom:6px">Permanent Gate Passes Issued! 🎉</div>
      <div style="opacity:0.85;font-size:14px"><?= $approved_passes_count ?> permanent gate passes have been generated for <?= htmlspecialchars($first_pass['contractor_name'] ?? 'Contractor') ?>. All notifications sent to contractor, welfare team, and safety team.</div>
      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
        <span style="background:rgba(255,255,255,0.15);padding:4px 12px;border-radius:20px;font-size:12px">Reference: <?= htmlspecialchars($first_pass['contract_id'] ?? 'N/A') ?></span>
        <span style="background:rgba(255,255,255,0.15);padding:4px 12px;border-radius:20px;font-size:12px">ACC Approved: System Admin</span>
        <span style="background:rgba(255,255,255,0.15);padding:4px 12px;border-radius:20px;font-size:12px">Valid: <?= date('d M Y', strtotime($first_pass['valid_from'])) ?> – <?= date('d M Y', strtotime($first_pass['valid_till'])) ?></span>
      </div>
    </div>
    <div class="no-print">
      <button class="btn btn-lg" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.4)" onclick="window.print()"><i class="fas fa-print"></i> Print All</button>
    </div>
  </div>
  <?php endif; ?>

  <!-- Workflow Complete -->
  <div class="card no-print" style="margin-bottom:24px">
    <div class="card-header"><div class="card-title"><i class="fas fa-sitemap"></i> Complete Workflow Journey</div></div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-sign-in-alt"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--gray-700)">Contractor Login</div>
          <div style="font-size:10px;color:var(--success)">✓ Done</div>
        </div>
        <div style="width:30px;height:2px;background:var(--success);flex-shrink:0"></div>
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-file-alt"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--gray-700)">Annexure 2/A &amp; 3/A</div>
          <div style="font-size:10px;color:var(--success)">✓ Done</div>
        </div>
        <div style="width:30px;height:2px;background:var(--success);flex-shrink:0"></div>
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-check-double"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--gray-700)">Welfare Verify</div>
          <div style="font-size:10px;color:var(--success)">✓ Done</div>
        </div>
        <div style="width:30px;height:2px;background:var(--success);flex-shrink:0"></div>
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-stamp"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--gray-700)">Welfare Approve</div>
          <div style="font-size:10px;color:var(--success)">✓ Done</div>
        </div>
        <div style="width:30px;height:2px;background:var(--success);flex-shrink:0"></div>
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-users"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--gray-700)">Enrolment 4/A</div>
          <div style="font-size:10px;color:var(--success)">✓ Done</div>
        </div>
        <div style="width:30px;height:2px;background:var(--success);flex-shrink:0"></div>
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-hard-hat"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--gray-700)">Safety Training</div>
          <div style="font-size:10px;color:var(--success)">✓ Done</div>
        </div>
        <div style="width:30px;height:2px;background:var(--success);flex-shrink:0"></div>
        <div style="text-align:center;min-width:100px">
          <div style="width:40px;height:40px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;color:var(--success)"><i class="fas fa-id-badge"></i></div>
          <div style="font-size:11px;font-weight:600;color:var(--primary)">Permanent Pass</div>
          <div style="font-size:10px;color:var(--success)">✓ Issued!</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Actions Bar -->
  <div class="card no-print" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print All Passes</button>
      <button class="btn btn-outline" onclick="downloadAll()"><i class="fas fa-download"></i> Download ZIP</button>
      <button class="btn btn-outline" onclick="sendNotifications()"><i class="fas fa-bell"></i> Send Notifications</button>
      <button class="btn btn-outline" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Export to Excel</button>
      <div style="margin-left:auto;font-size:13px;color:var(--gray-600)">
<strong><?= $approved_passes_count ?></strong> passes generated
      </div>
    </div>
  </div>

  <!-- Gate Pass Cards -->
  <!-- Final Approved Queue for Generation -->
  <?php
  if (!empty($pending_generation)):
  ?>
  <div class="card no-print" style="margin-bottom:24px; border-left:4px solid var(--primary);">
    <div class="card-header"><div class="card-title">Pending Gate Pass Generation</div></div>
    <div class="card-body">
      <table class="data-table">
        <thead><tr><th>Application ID</th><th>Contractor</th><th>Approved On</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($pending_generation as $app): ?>
          <tr>
            <td><?= htmlspecialchars($app['application_id'] ?? $app['ref_id']) ?></td>
            <td><?= htmlspecialchars($app['contractor_name']) ?></td>
            <td><?= $app['updated_at'] ?></td>
            <td><button class="btn btn-primary btn-sm" onclick="generatePass('<?= $app['application_id'] ?? $app['ref_id'] ?>')"><i class="fas fa-cogs"></i> Generate Passes</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Gate Pass Cards -->
  <div class="pass-grid">
    <?php if (empty($passes)): ?>
      <div style="padding:40px;text-align:center;width:100%;color:var(--gray-500);">No passes generated yet.</div>
    <?php else: foreach ($passes as $pass): ?>
    <div class="gate-pass">
      <div class="gate-pass-header">
        <div style="font-size:10px;letter-spacing:2px;opacity:0.8;font-weight:600">GOVERNMENT WORKS DEPARTMENT</div>
        <div style="font-size:16px;font-weight:800;margin:4px 0">PERMANENT GATE PASS</div>
        <div style="font-size:10px;opacity:0.75">Valid Site Entry Authorization</div>
      </div>
      <div class="gate-pass-body">
        <div class="pass-number"><?= $pass['pass_no'] ?></div>
        <hr class="divider" style="margin:10px 0" />
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="width:60px;height:70px;border-radius:8px;background:var(--gray-100);border:1px solid var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0"><i class="fas fa-user"></i></div>
          <div style="flex:1">
            <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Name</span><span class="info-value" style="font-size:12px"><?= htmlspecialchars($pass['worker_name']) ?></span></div>
            <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Trade</span><span class="info-value" style="font-size:12px"><?= htmlspecialchars($pass['trade']) ?></span></div>
            <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Temp ID</span><span class="info-value" style="font-size:12px"><?= htmlspecialchars($pass['worker_temp_id']) ?></span></div>
          </div>
        </div>
        <hr class="divider" style="margin:10px 0" />
        <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Contractor</span><span class="info-value" style="font-size:11px"><?= htmlspecialchars($pass['contractor_name']) ?></span></div>
        <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Contract</span><span class="info-value" style="font-size:11px"><?= htmlspecialchars($pass['contract_id']) ?></span></div>
        <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Valid From</span><span class="info-value" style="font-size:11px;color:var(--success)"><?= date('d M Y', strtotime($pass['valid_from'])) ?></span></div>
        <div class="info-row" style="padding:4px 0"><span class="info-label" style="font-size:11px">Valid Till</span><span class="info-value" style="font-size:11px;color:var(--danger);font-weight:700"><?= date('d M Y', strtotime($pass['valid_till'])) ?></span></div>
        <hr class="divider" style="margin:10px 0" />
        <div style="display:flex;justify-content:space-between;align-items:flex-end">
          <div>
            <div class="hologram"></div>
            <div style="font-size:9px;color:var(--gray-400);margin-top:4px">Hologram Protected</div>
          </div>
          <div style="text-align:center">
            <div class="qr-placeholder"><i class="fas fa-qrcode"></i></div>
            <div style="font-size:9px;color:var(--gray-400);margin-top:2px">Scan to verify</div>
          </div>
          <div style="text-align:right">
            <span class="badge badge-success" style="font-size:9px">ACTIVE</span><br />
            <div style="font-size:9px;color:var(--gray-400);margin-top:4px">Issued by ACC</div>
            <div style="font-size:9px;color:var(--gray-400)">Approved</div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- All Passes Summary Table -->
  <div class="card no-print" style="margin-top:24px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-table"></i> Pass Issuance Register</div>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table">
        <thead><tr><th>Pass No.</th><th>Temp ID</th><th>Name</th><th>Trade</th><th>Issue Date</th><th>Valid Till</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php if (empty($passes)): ?>
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500)">No approved gate passes yet</td></tr>
            <?php else: foreach ($passes as $pass): ?>
            <tr>
              <td><?= htmlspecialchars($pass['pass_no'] ?? $pass['id']) ?></td>
              <td><?= htmlspecialchars($pass['worker_temp_id'] ?? 'TMP-'.$pass['id']) ?></td>
              <td><?= htmlspecialchars($pass['worker_name'] ?? 'Worker') ?></td>
              <td><?= htmlspecialchars($pass['trade'] ?? 'Labour') ?></td>
              <td><?= date('d M Y', strtotime($pass['created_at'])) ?></td>
              <td><?= date('d M Y', strtotime($pass['valid_till'])) ?></td>
              <td><span class="badge badge-success">Active</span></td>
              <td><button class="btn btn-sm btn-outline" onclick="printPass('<?= $pass['id'] ?>')"><i class="fas fa-print"></i></button></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
      </table>
    </div>
  </div>
</div>

<script src="../js/utils.js"></script>
<script src="../js/navigation.js"></script>
<script>
  function downloadAll() { alert('Downloading all passes as ZIP...'); }
  function sendNotifications() { alert('Notifications sent.'); }
  function exportExcel() { alert('Exporting register to Excel...'); }
  function loadMorePasses() { alert('Loading more passes...'); }

  async function generatePass(appId) {
    if (!appId) return;
    if (!confirm('Generate Gate Passes for application ' + appId + '?')) return;
    
    try {
      const res = await window.apiFetch('generate_permanent_pass.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: appId })
      });
      if (res.success) {
        alert('Gate Passes generated successfully!');
        window.location.reload();
      } else {
        alert('Error: ' + res.error);
      }
    } catch(e) {
      alert('Network error: ' + e.message);
    }
  }
</script>
</body>
</html>

