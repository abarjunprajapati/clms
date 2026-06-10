<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';
$trainingFeePerWorker = clms_training_fee_per_worker($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Safety Training Request – Contractor Portal</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-hard-hat"></i></div>
    <div>
      <div class="topbar-title">Safety Training Request</div>
      <div class="topbar-subtitle">Contractor Portal · Safety Module</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="contractor-dashboard.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-home"></i> Dashboard</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<!-- DYNAMIC PROGRESS TRACKER -->
<div class="page-container" style="padding-bottom:0">
  <div id="workflowProgressContainer"></div>
</div>

<div class="page-container">
  <div class="page-header">
    <div class="page-title">Safety Training Request</div>
    <div class="page-subtitle">Request safety training slots for enrolled workmen. Training is mandatory before gate pass issuance.</div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <!-- Request Form -->
    <div>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-calendar-plus"></i> Request Training Slot</div>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Contractor Name <span class="sap-badge">SAP</span></label>
            <input class="form-control sap-field" value="Ravi Constructions Pvt. Ltd." disabled />
          </div>
          <div class="form-group">
            <label class="form-label">Contract Number <span class="sap-badge">SAP</span></label>
            <input class="form-control sap-field" value="PWD/2024/CNT/0187" disabled />
          </div>
          <div class="form-group">
            <label class="form-label">Training Type <span class="required">*</span></label>
            <select class="form-control" id="trainingType">
              <option value="">Select Training Type</option>
              <option>Basic Safety Induction (New Workmen)</option>
              <option>Height Safety Training</option>
              <option>Electrical Safety</option>
              <option>Fire Safety & Emergency Evacuation</option>
              <option>HAZMAT Handling</option>
              <option>First Aid & CPR</option>
              <option>PPE Usage & Care</option>
            </select>
          </div>
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">Preferred Date <span class="required">*</span></label>
              <input class="form-control" type="date" id="prefDate" />
            </div>
            <div class="form-group">
              <label class="form-label">Preferred Time Slot <span class="required">*</span></label>
              <select class="form-control">
                <option>09:00 AM – 01:00 PM</option>
                <option>02:00 PM – 06:00 PM</option>
                <option>Full Day (09:00 AM – 05:00 PM)</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Training Location <span class="required">*</span></label>
            <select class="form-control">
              <option>Safety Training Centre – Zone A, Pune</option>
              <option>Mobile Training Unit – Site Visit</option>
              <option>Online (Virtual Mode)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Number of Participants <span class="required">*</span></label>
            <input class="form-control" type="number" id="participantCount" value="48" />
            <div class="form-hint">All enrolled workmen: 48. Max per batch: 30.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Select Workmen for Training <span class="required">*</span></label>
            <div id="workmenSelectionList" style="border:1.5px solid var(--gray-300);border-radius:8px;max-height:180px;overflow-y:auto;padding:8px">
              <label style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;border-bottom:1px solid var(--gray-100);cursor:pointer">
                <input type="checkbox" id="selectAllWorkmen" onchange="toggleAllWorkmen()" checked /> <strong>Select All</strong>
              </label>
              <div id="workmenListContent">
                <div style="text-align:center;padding:10px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Special Requirements</label>
            <textarea class="form-control" rows="2" placeholder="Any special requirements or notes..."></textarea>
          </div>

          <div style="display:flex;gap:10px">
            <button class="btn btn-outline" onclick="saveDraftST()"><i class="fas fa-save"></i> Save Draft</button>
            <button class="btn btn-primary" style="flex:1" onclick="submitTrainingRequest()"><i class="fas fa-paper-plane"></i> Submit Training Request</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Info & Status -->
    <div>
      <!-- Training Info -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-info-circle"></i> Training Info & Fee</div>
        </div>
        <div class="card-body">
          <div class="alert alert-warning">
            <i class="fas fa-rupee-sign"></i>
            <div id="paymentFeeInfo"><strong>Application Fee:</strong> ₹ 500 per workman. Fee payment required before training confirmation.</div>
          </div>
          <div class="info-row"><span class="info-label">Training Duration</span><span class="info-value">4 Hours (Basic)</span></div>
          <div class="info-row"><span class="info-label">Min. Pass Score</span><span class="info-value">70%</span></div>
          <div class="info-row"><span class="info-label">Certificate Validity</span><span class="info-value">1 Year</span></div>
          <div class="info-row"><span class="info-label">Training Authority</span><span class="info-value">Executing Officer</span></div>
          <div class="info-row"><span class="info-label">Medium</span><span class="info-value">Hindi / Marathi / English</span></div>
          <div style="margin-top:14px">
            <a href="payment.php" class="btn btn-warning btn-full"><i class="fas fa-credit-card"></i> Pay Application Fee (₹24,000)</a>
          </div>
        </div>
      </div>

      <!-- Training History -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-history"></i> Training Request History</div>
        </div>
        <div class="card-body" style="padding:0">
          <table class="data-table">
            <thead><tr><th>Ref No.</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
            <tbody id="trainingHistory">
              <tr>
                <td>STR-2025-001</td>
                <td>Basic Safety</td>
                <td>15 Mar 2025</td>
                <td><span class="badge badge-success">Completed</span></td>
              </tr>
              <tr>
                <td>STR-2025-002</td>
                <td>Fire Safety</td>
                <td>20 Mar 2025</td>
                <td><span class="badge badge-success">Completed</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Submission Modal -->
<div id="stModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-body" style="text-align:center;padding:36px 28px">
      <div style="width:70px;height:70px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:var(--success)"><i class="fas fa-check-circle"></i></div>
      <h2 style="font-size:20px;font-weight:700;margin-bottom:8px">Training Request Submitted!</h2>
      <p style="font-size:13px;color:var(--gray-500);margin-bottom:6px">Reference: <strong>STR-2025-0843</strong></p>
      <p style="font-size:12px;color:var(--gray-400);margin-bottom:20px">Executing Officer notified. Awaiting approval. Safety team will contact you for slot confirmation. Please complete payment to proceed.</p>
      <div class="alert alert-warning" style="text-align:left;margin-bottom:20px">
        <i class="fas fa-exclamation-triangle"></i>
        <div><strong>Next Step:</strong> Pay the application fee of ₹24,000 to confirm your training slot. Email & SMS notifications sent.</div>
      </div>
      <div style="display:flex;gap:10px;justify-content:center">
        <a href="payment.php" class="btn btn-warning"><i class="fas fa-credit-card"></i> Pay Fee Now</a>
        <a href="safety-training-approval.php" class="btn btn-primary"><i class="fas fa-eye"></i> Track Approval</a>
        <button class="btn btn-outline" onclick="hideModal('stModal')">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../../js/utils.js"></script>
<script src="../../js/navigation.js"></script>
<script>
// =============================================
// SAFETY TRAINING - DYNAMIC DATA
// =============================================

async function loadData() {
    const appId = window.getAppId();
    if (!appId) return;

    // Load App details for progress bar
    const appRes = await window.apiFetch(`get_application_details.php?id=${encodeURIComponent(appId)}`);
    if (appRes.success && appRes.data.application) {
        document.getElementById('workflowProgressContainer').innerHTML = window.renderWorkflowProgress(appRes.data.application.workflow_status);
    }

    // Load Workmen for selection
    const list = document.getElementById('workmenListContent');
    const res = await window.apiFetch(`get_workmen.php?application_id=${encodeURIComponent(appId)}&type=workman`);
    
    if (res.success && res.data.length > 0) {
        list.innerHTML = res.data.map(w => `
          <label style="display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0;cursor:pointer">
            <input type="checkbox" class="workman-checkbox" checked value="${w.id}" onchange="updateParticipantCount()" /> 
            ${w.name} – ${w.temp_id || 'Pending'}
          </label>
        `).join('');
        updateParticipantCount();
    } else {
        list.innerHTML = '<div style="color:var(--gray-500);font-size:12px">No enrolled workmen found.</div>';
    }
}

function toggleAllWorkmen() {
    const checked = document.getElementById('selectAllWorkmen').checked;
    document.querySelectorAll('.workman-checkbox').forEach(cb => cb.checked = checked);
    updateParticipantCount();
}

function updateParticipantCount() {
    const selected = document.querySelectorAll('.workman-checkbox:checked').length;
    document.getElementById('participantCount').value = selected;
    const feePerWorker = <?= json_encode((float)$trainingFeePerWorker) ?>;
    const fee = selected * feePerWorker;
    document.getElementById('paymentFeeInfo').innerHTML = 
      `<strong>Application Fee:</strong> ₹ 500 per workman. For ${selected} workmen = <strong>₹ ${fee.toLocaleString()}</strong>. Fee payment required before training confirmation.`;
}

async function submitTrainingRequest() {
    const appId = window.getAppId();
    const type = document.getElementById('trainingType').value;
    const date = document.getElementById('prefDate').value;
    const selected = Array.from(document.querySelectorAll('.workman-checkbox:checked')).map(cb => cb.value);

    if (!type || !date || selected.length === 0) {
        showToast('⚠️', 'Please complete all required fields.');
        return;
    }

    const res = await window.apiFetch('submit_training_request.php', {
        method: 'POST',
        body: JSON.stringify({
            application_id: appId,
            training_type: type,
            preferred_date: date,
            workman_ids: selected,
            remarks: document.querySelector('textarea').value
        })
    });

    if (res.success) {
        document.querySelector('#stModal strong').textContent = res.request_id || 'STR-' + Date.now().toString().slice(-4);
        showModal('stModal');
        loadData();
    } else {
        showToast('❌', res.error || 'Submission failed');
    }
}

document.addEventListener('DOMContentLoaded', loadData);
</script>
</body>
</html>

