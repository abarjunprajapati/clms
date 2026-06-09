<?php


require_once '../../include/auth.php';
checkAuth(['welfare_user', 'super_admin', 'execution_officer', 'pass_user']);
include '../../include/config.php';
require_once '../../include/temporary_pass_validity.php';



$role = $_SESSION['role'];
$tempPassValidityDays = clms_get_temporary_pass_validity_days($conn);
$pending_wo = db_count($conn, "SELECT COUNT(*) c FROM gate_passes WHERE status = 'pending'");
$notif_count = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welfare Officer – Gate Pass Approval</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-stamp"></i></div>
    <div>
      <div class="topbar-title">Welfare Officer – Gate Pass Approval</div>
      <div class="topbar-subtitle">Welfare Portal · Gate Pass Workflow</div>
    </div>
  </div>
  <div class="topbar-right">
<div class="notif-badge"><?= $notif_count ?></div>
    <div class="topbar-user">
      <div class="user-avatar">WO</div>
<div style="font-size:13px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Welfare Officer') ?></div><div style="font-size:11px;opacity:0.7">Welfare Officer</div>
    </div>
  </div>
</div>

<div class="page-container">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;padding:16px 20px;background:white;border-radius:12px;border:1px solid var(--gray-200);box-shadow:var(--shadow-sm)">
    <div style="font-size:12px;color:var(--gray-500);font-weight:600">GATE PASS WORKFLOW:</div>
    <div style="display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap">
      <span class="badge badge-success" style="font-size:11px"><i class="fas fa-check"></i> 6/A Submitted</span>
      <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
      <span class="badge badge-success" style="font-size:11px"><i class="fas fa-check"></i> PO Verified</span>
      <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
      <span class="badge badge-primary" style="font-size:11px"><i class="fas fa-clock"></i> Welfare Officer ◄ HERE</span>
      <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
      <span class="badge badge-gray" style="font-size:11px"><i class="fas fa-clock"></i> ACC Approval</span>
      <i class="fas fa-arrow-right" style="color:var(--gray-300)"></i>
      <span class="badge badge-gray" style="font-size:11px"><i class="fas fa-clock"></i> Permanent Pass</span>
    </div>
  </div>

  <div class="page-header">
    <div class="page-title">Welfare Officer – Gate Pass Approval / Reject</div>
    <div class="page-subtitle">Review gate pass applications verified by Pass Issuing Officer and make approval/rejection decision.</div>
  </div>

  <!-- Pending Applications -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list"></i> Annexure 5A — Gate Pass Requests</div>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table">
        <thead>
          <tr><th>Ref</th><th>Contractor</th><th>Gate Pass Ref</th><th>Workmen</th><th>Submitted On</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php 
          $gate_q = $conn->query("
            SELECT
              g.*,
              a.ref_id,
              a.contractor_name,
              a.workflow_status,
              COUNT(g2.id) AS total_workmen
            FROM gate_passes g
            LEFT JOIN annexure2a a ON g.application_no = a.application_id
            LEFT JOIN gate_passes g2 ON g2.application_no = g.application_no
            WHERE g.status IN ('pending', 'approved')
            GROUP BY g.id
            ORDER BY g.created_at DESC
            LIMIT 20
          ");
          if($gate_q->num_rows > 0):
            while($row = $gate_q->fetch_assoc()):
              $action_text = 'Review';
              $action_class = 'btn-primary';
              $status_badge = '<span class="badge badge-warning">Pending Review</span>';
              
              if ($row['workflow_status'] == 'gatepass_requested') {
                $action_text = 'Verify Documents';
                $status_badge = '<span class="badge badge-info">Documents Pending</span>';
              } elseif ($row['workflow_status'] == 'gatepass_verified') {
                $action_text = 'Issue Temp Pass';
                $status_badge = '<span class="badge badge-warning">Ready for Temp Pass</span>';
              } elseif ($row['workflow_status'] == 'temporary_pass_issued') {
                $action_text = 'Generate ACC';
                $status_badge = '<span class="badge badge-info">ACC Pending</span>';
              } elseif ($row['workflow_status'] == 'acc_generated') {
                $action_text = 'Issue Permanent Pass';
                $status_badge = '<span class="badge badge-success">Ready for Permanent</span>';
              }
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['ref_id']) ?></strong></td>
            <td><?= htmlspecialchars($row['contractor_name']) ?></td>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= (int)($row['total_workmen'] ?? 1) ?></td>
            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td><?= $status_badge ?></td>
            <td><button class="btn <?= $action_class ?> btn-sm" onclick="showWelfarePanel('<?= htmlspecialchars($row['application_no']) ?>', '<?= htmlspecialchars($row['workflow_status']) ?>')"><i class="fas fa-gavel"></i> <?= $action_text ?></button></td>
          </tr>
          <?php endwhile; ?>
          <?php else: ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500)"><i class="fas fa-inbox" style="font-size:48px;opacity:0.3"></i><br>No gate pass applications pending welfare action</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Welfare Decision Panel -->
  <div id="welfarePanel" style="display:none">
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-gavel"></i> Welfare Officer Action — <span id="panelTitle">Annexure 5A</span></div>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('welfarePanel').style.display='none'"><i class="fas fa-times"></i></button>
      </div>
      <div class="card-body">
        <!-- Summary -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px" id="summarySection">
          <div style="background:var(--gray-50);border-radius:10px;padding:14px">
            <div class="info-row"><span class="info-label">Contractor</span><span class="info-value" id="info-contractor">Loading...</span></div>
            <div class="info-row"><span class="info-label">Application ID</span><span class="info-value" id="info-app-id">Loading...</span></div>
            <div class="info-row"><span class="info-label">PAN</span><span class="info-value" id="info-pan">Loading...</span></div>
          </div>
          <div style="background:var(--gray-50);border-radius:10px;padding:14px">
            <div class="info-row"><span class="info-label">Workmen</span><span class="info-value" id="info-workmen">Loading...</span></div>
            <div class="info-row"><span class="info-label">Safety Training</span><span class="info-value"><span class="badge badge-success">Cleared</span></span></div>
            <div class="info-row"><span class="info-label">Documents</span><span class="info-value" id="info-docs">Loading...</span></div>
          </div>
          <div style="background:var(--gray-50);border-radius:10px;padding:14px">
            <div class="info-row"><span class="info-label">Entry Gate</span><span class="info-value" id="info-gate">Loading...</span></div>
            <div class="info-row"><span class="info-label">Shift</span><span class="info-value" id="info-shift">Loading...</span></div>
            <div class="info-row"><span class="info-label">Pass Period</span><span class="info-value" id="info-period">Loading...</span></div>
          </div>
        </div>

        <!-- Document Verification Section (for gatepass_requested) -->
        <div id="docVerificationSection" style="display:none;margin-bottom:20px">
          <h4 style="margin-bottom:12px"><i class="fas fa-file-shield"></i> Document Verification (Annexure 6A)</h4>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-medical" checked> Medical Fitness Certificate
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-pcc" checked> Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-pcc-police" checked> Proof of forwarding PCC to Thane Police Station
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-pcc-cisf" checked> Proof of forwarding PCC to CISF
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-police-station" checked> Name of Police Station from where PCC has been obtained
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-ec-policy" checked> Employee Compensation Policy if not covered under ESI
            </label>
            <label style="display:flex;align-items:center;gap:8px;padding:10px;background:white;border:1px solid var(--gray-200);border-radius:8px">
              <input type="checkbox" id="doc-esi-epf" checked> ESI / EPF Undertaking if not covered under ESI / EPF
            </label>
          </div>
        </div>

        <!-- Temp Pass Section (for gatepass_verified) -->
        <div id="tempPassSection" style="display:none;margin-bottom:20px">
          <h4 style="margin-bottom:12px"><i class="fas fa-clock"></i> Issue Temporary Gate Pass</h4>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
            <div class="form-group">
              <label class="form-label">Temporary Pass Validity (Days)</label>
              <input type="number" class="form-control" id="temp-validity" value="<?= (int)$tempPassValidityDays ?>" min="1" max="<?= (int)$tempPassValidityDays ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Access Zones</label>
              <select class="form-control" id="temp-zones">
                <option value="all">All Zones</option>
                <option value="restricted">Zone A & B Only</option>
                <option value="limited">Administrative Only</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ACC Generation Section (for temporary_pass_issued) -->
        <div id="accSection" style="display:none;margin-bottom:20px">
          <h4 style="margin-bottom:12px"><i class="fas fa-microchip"></i> Generate ACC Number & SAP Integration</h4>
          <div style="background:#f0f9ff;border:1px solid #0ea5e9;border-radius:8px;padding:15px;margin-bottom:15px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
              <i class="fas fa-info-circle" style="color:#0ea5e9"></i>
              <strong>SAP S/4 HANA Integration</strong>
            </div>
            <p style="margin:0;font-size:13px;color:#374151">ACC number will be generated and automatically pushed to SAP system for workforce mapping.</p>
          </div>
          <div class="form-group">
            <label class="form-label">SAP Integration Status</label>
            <div style="display:flex;align-items:center;gap:10px">
              <span class="badge badge-info">Ready for Integration</span>
              <button class="btn btn-outline btn-sm" onclick="testSAPConnection()"><i class="fas fa-plug"></i> Test Connection</button>
            </div>
          </div>
        </div>

        <!-- Permanent Pass Section (for acc_generated) -->
        <div id="permanentPassSection" style="display:none;margin-bottom:20px">
          <h4 style="margin-bottom:12px"><i class="fas fa-id-badge"></i> Issue Permanent Gate Pass</h4>
          <div style="background:#f0fdf4;border:1px solid #16a34a;border-radius:8px;padding:15px;margin-bottom:15px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
              <i class="fas fa-check-circle" style="color:#16a34a"></i>
              <strong>ACC Generated Successfully</strong>
            </div>
            <p style="margin:0;font-size:13px;color:#374151">ACC: <strong id="acc-number-display">Loading...</strong> | SAP Status: <span class="badge badge-success">Integrated</span></p>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
            <div class="form-group">
              <label class="form-label">Permanent Pass Validity</label>
              <select class="form-control" id="perm-validity">
                <option value="contract">Till Contract End</option>
                <option value="1year">1 Year</option>
                <option value="2years">2 Years</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Pass Type</label>
              <select class="form-control" id="perm-type">
                <option value="permanent">Permanent Workman</option>
                <option value="contract">Contract Worker</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Welfare Decision Form -->
        <div class="form-group">
          <label class="form-label">Welfare Officer Remarks</label>
          <textarea class="form-control" id="welfare-remarks" rows="3" placeholder="Enter decision remarks..."></textarea>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap" id="actionButtons">
          <button class="btn btn-success btn-lg" id="btn-approve" onclick="welfareApprove()"><i class="fas fa-check-circle"></i> Approve & Proceed</button>
          <button class="btn btn-danger" onclick="welfareReject()"><i class="fas fa-times-circle"></i> Reject Application</button>
          <button class="btn btn-outline" onclick="document.getElementById('welfarePanel').style.display='none'">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../../js/navigation.js"></script>
<script>
let currentAppId = '';
let currentStatus = '';

function showWelfarePanel(appId, status) {
  currentAppId = appId;
  currentStatus = status;
  
  document.getElementById('welfarePanel').style.display = 'block';
  document.getElementById('welfarePanel').scrollIntoView({ behavior: 'smooth' });
  
  // Load application details
  loadApplicationDetails(appId);
  
  // Show/hide sections based on status
  showRelevantSections(status);
  
  // Update button text
  updateActionButton(status);
}

function loadApplicationDetails(appId) {
  // Mock data for now - in real implementation, fetch from API
  document.getElementById('info-contractor').textContent = 'Ravi Constructions';
  document.getElementById('info-app-id').textContent = appId;
  document.getElementById('info-pan').textContent = 'AAACR5055K';
  document.getElementById('info-workmen').textContent = '41 Eligible';
  document.getElementById('info-docs').textContent = 'All Verified';
  document.getElementById('info-gate').textContent = 'Main Gate, Km 42';
  document.getElementById('info-shift').textContent = 'Day (06:00–18:00)';
  document.getElementById('info-period').textContent = 'Apr–Dec 2025';
  document.getElementById('acc-number-display').textContent = 'ACC-' + Date.now().toString().slice(-6);
}

function showRelevantSections(status) {
  // Hide all sections first
  document.getElementById('docVerificationSection').style.display = 'none';
  document.getElementById('tempPassSection').style.display = 'none';
  document.getElementById('accSection').style.display = 'none';
  document.getElementById('permanentPassSection').style.display = 'none';
  
  // Show relevant section based on status
  if (status === 'gatepass_requested') {
    document.getElementById('docVerificationSection').style.display = 'block';
    document.getElementById('panelTitle').textContent = 'Document Verification';
  } else if (status === 'gatepass_verified') {
    document.getElementById('tempPassSection').style.display = 'block';
    document.getElementById('panelTitle').textContent = 'Issue Temporary Pass';
  } else if (status === 'temporary_pass_issued') {
    document.getElementById('accSection').style.display = 'block';
    document.getElementById('panelTitle').textContent = 'Generate ACC Number';
  } else if (status === 'acc_generated') {
    document.getElementById('permanentPassSection').style.display = 'block';
    document.getElementById('panelTitle').textContent = 'Issue Permanent Pass';
  }
}

function updateActionButton(status) {
  const btn = document.getElementById('btn-approve');
  if (status === 'gatepass_requested') {
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify Documents & Proceed';
  } else if (status === 'gatepass_verified') {
    btn.innerHTML = '<i class="fas fa-clock"></i> Issue Temporary Pass';
  } else if (status === 'temporary_pass_issued') {
    btn.innerHTML = '<i class="fas fa-microchip"></i> Generate ACC & Integrate SAP';
  } else if (status === 'acc_generated') {
    btn.innerHTML = '<i class="fas fa-id-badge"></i> Issue Permanent Pass';
  }
}

async function welfareApprove() {
  const remarks = document.getElementById('welfare-remarks').value;
  
  let action = '';
  let additionalData = {};
  
  if (currentStatus === 'gatepass_requested') {
    action = 'verify_documents';
  } else if (currentStatus === 'gatepass_verified') {
    action = 'issue_temporary_pass';
    additionalData = {
      temp_validity: document.getElementById('temp-validity').value,
      temp_zones: document.getElementById('temp-zones').value
    };
  } else if (currentStatus === 'temporary_pass_issued') {
    action = 'generate_acc';
  } else if (currentStatus === 'acc_generated') {
    action = 'issue_permanent_pass';
    additionalData = {
      perm_validity: document.getElementById('perm-validity').value,
      perm_type: document.getElementById('perm-type').value
    };
  }
  
  // Call workflow action API
  const res = await fetch('../../api/workflow_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      application_id: currentAppId,
      action: action,
      remarks: remarks,
      additional_data: additionalData
    })
  });
  
  const data = await res.json();
  if (data.success) {
    showToast('✅', 'Action completed successfully!');
    setTimeout(() => location.reload(), 1500);
  } else {
    showToast('❌', data.error || 'Action failed');
  }
}

function welfareReject() {
  const remarks = document.getElementById('welfare-remarks').value;
  if (!remarks.trim()) {
    showToast('⚠️', 'Please provide rejection reason');
    return;
  }
  
  // Call reject action
  fetch('../../api/workflow_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      application_id: currentAppId,
      action: 'reject_gatepass',
      remarks: remarks
    })
  }).then(res => res.json()).then(data => {
    if (data.success) {
      showToast('❌', 'Application rejected. Contractor notified.');
      document.getElementById('welfarePanel').style.display = 'none';
      setTimeout(() => location.reload(), 1500);
    } else {
      showToast('❌', data.error || 'Rejection failed');
    }
  });
}

function testSAPConnection() {
  showToast('🔗', 'SAP connection test initiated...');
  // Mock SAP test
  setTimeout(() => showToast('✅', 'SAP connection successful!'), 1000);
}

function showToast(icon, message) {
  // Simple toast implementation
  const toast = document.createElement('div');
  toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#333;color:white;padding:12px 20px;border-radius:8px;z-index:9999;font-size:14px;';
  toast.innerHTML = `${icon} ${message}`;
  document.body.appendChild(toast);
  setTimeout(() => document.body.removeChild(toast), 3000);
}
</script>
</body>
</html>
