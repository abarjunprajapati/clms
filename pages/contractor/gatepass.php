<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gate Pass Request – Annexure 6/A</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-door-open"></i></div>
    <div>
      <div class="topbar-title">Annexure 6/A – Gate Pass Request</div>
      <div class="topbar-subtitle">Contractor Portal · Gate Pass Module</div>
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
    <div class="page-title">Annexure 6/A – Gate Pass Application</div>
    <div class="page-subtitle">Apply for gate pass for qualified workmen. Only workmen who have passed safety training are eligible.</div>
  </div>

  <div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div><strong>41 Workmen Qualified</strong> — Safety training completed. 41 workmen are eligible for gate pass. 7 failed workmen are excluded.</div>
  </div>

  <!-- Part A: Contractor Details -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-building"></i> Part A — Contractor Reference</div>
    </div>
    <div class="card-body">
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Contractor Name <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" id="info-contractor-name" value="Loading..." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Contract Number <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" id="info-contract-no" value="Loading..." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Welfare Approval Ref.</label>
          <input class="form-control sap-field" id="info-welfare-ref" value="Loading..." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Safety Training Ref.</label>
          <input class="form-control sap-field" id="info-safety-ref" value="Loading..." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Work Location <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" id="info-location" value="Loading..." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Gate Pass Validity Requested</label>
          <select class="form-control" id="validity_period">
            <option>Till Contract End</option>
            <option>6 Months</option>
            <option>3 Months</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Part B: Gate Details -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-map-marker-alt"></i> Part B — Entry Gate & Access Details</div>
    </div>
    <div class="card-body">
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Entry Gate Requested <span class="required">*</span></label>
          <select class="form-control" id="entry_gate">
            <option>Main Gate – NH48, Km 42</option>
            <option>East Entry Gate – Site B</option>
            <option>West Entry Gate – Site C</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Work Shift <span class="required">*</span></label>
          <select class="form-control" id="work_shift">
            <option>Day Shift (06:00 – 18:00)</option>
            <option>Night Shift (18:00 – 06:00)</option>
            <option>Both Shifts</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Type of Access <span class="required">*</span></label>
          <select class="form-control" id="access_type">
            <option>Construction Zone</option>
            <option>Heavy Machinery Area</option>
            <option>All Zones</option>
            <option>Administrative Area Only</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Pass From Date <span class="required">*</span></label>
          <input class="form-control" type="date" id="from_date" value="<?= date('Y-m-d') ?>" />
        </div>
        <div class="form-group">
          <label class="form-label">Pass To Date <span class="required">*</span></label>
          <input class="form-control" type="date" id="to_date" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" />
        </div>
        <div class="form-group">
          <label class="form-label">Vehicle Entry Required?</label>
          <select class="form-control">
            <option>No</option>
            <option>Yes – Light Vehicle</option>
            <option>Yes – Heavy Vehicle</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Part C: Qualified Workmen Selection -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-users"></i> Section B – Personnel Requesting Gate Pass</div>

      <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
        <input type="checkbox" id="selectAllGP" onchange="selectAllGP()" checked /> Select All (41)
      </label>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table" id="gpTable">
        <thead>
        <tr>
          <th><input type="checkbox" onchange="selectAllGP()" /></th>
          <th>Name</th>
          <th>Temp ID</th>
          <th>Role</th>
          <th>Training</th>
          <th>Access Zone</th>
          <th>Access Hours</th>
        </tr>

        </thead>
        <tbody id="gpTableBody">
          <tr><td colspan="7" style="text-align:center;padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--primary);"></i><br>
            <strong>Loading qualified personnel...</strong>
          </td></tr>
        </tbody>

      </table>
      <div style="padding:12px 16px;background:var(--gray-50);border-top:1px solid var(--gray-100);font-size:12px;color:var(--gray-500)">
        <span id="gpTableFooter">Loading...</span>

      </div>
    </div>
  </div>

  <!-- Part D: Representative Declaration -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-pen-fancy"></i> Part D — Authorised Signatory Declaration</div>
    </div>
    <div class="card-body">
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">Authorised Signatory Name <span class="required">*</span></label>
          <input class="form-control" placeholder="Full name" />
        </div>
        <div class="form-group">
          <label class="form-label">Designation <span class="required">*</span></label>
          <input class="form-control" placeholder="e.g., Director / Project Manager" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Declaration</label>
        <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:13px;color:var(--gray-700)">
          <input type="checkbox" id="gp-agree" style="margin-top:3px;width:16px;height:16px" />
          <span>I hereby declare that all workmen listed above have completed mandatory safety training and are authorised to enter the work site. I accept responsibility for their conduct and compliance with site rules.</span>
        </label>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:40px">
    <button class="btn btn-outline" onclick="saveDraftGP()"><i class="fas fa-save"></i> Save Draft</button>
    <button class="btn btn-primary btn-lg" onclick="submitGP()"><i class="fas fa-paper-plane"></i> Submit Gate Pass Application</button>
  </div>
</div>

<!-- Success Modal -->
<div id="gpModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-body" style="text-align:center;padding:40px 30px">
      <div style="width:70px;height:70px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:var(--success)"><i class="fas fa-check-circle"></i></div>
      <h2 style="font-size:20px;font-weight:700;margin-bottom:8px">Annexure 6/A Submitted!</h2>
      <p style="font-size:13px;color:var(--gray-500);margin-bottom:6px">Reference: <strong>GP-2025-0842-6A</strong></p>
      <p style="font-size:12px;color:var(--gray-400);margin-bottom:20px">Gate pass application forwarded to Pass Issuing Officer for verification.</p>
      <div class="alert alert-info" style="text-align:left">
        <i class="fas fa-info-circle"></i>
        <strong>Next Steps:</strong>
        <ol style="margin-top:6px;padding-left:16px;font-size:12px;color:var(--gray-600)">
          <li>Pass Issuing Officer – Verification</li>
          <li>Welfare Officer – Approval/Reject</li>
          <li>ACC – Final Approval</li>
          <li>Permanent Gate Pass Issuance</li>
        </ol>
      </div>
      <div style="display:flex;gap:10px;justify-content:center">
        <a href="pass-officer-verification.php" class="btn btn-primary"><i class="fas fa-eye"></i> Track Status</a>
        <button class="btn btn-outline" onclick="hideModal('gpModal')">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../../js/utils.js"></script>
<script src="../../js/navigation.js"></script>
<script>
// =============================================
// GATE PASS 6/A - DYNAMIC DATA
// =============================================

async function loadData() {
    const appId = window.getAppId();
    if (!appId) return;

    // Load App details for progress bar and info fields
    const appRes = await window.apiFetch(`get_application_details.php?id=${encodeURIComponent(appId)}`);
    if (appRes.success && appRes.data.application) {
        const app = appRes.data.application;
        document.getElementById('workflowProgressContainer').innerHTML = window.renderWorkflowProgress(app.workflow_status);
        
        // Populate Info Fields
        document.getElementById('info-contractor-name').value = app.contractor_name || 'N/A';
        document.getElementById('info-contract-no').value = app.contract_no || 'N/A';
        document.getElementById('info-welfare-ref').value = app.application_id || 'N/A';
        document.getElementById('info-safety-ref').value = 'STR-' + (app.application_id || '').split('-').pop(); // Mock STR ref
        document.getElementById('info-location').value = app.work_location || 'N/A';
        
        if (app.contract_period) {
            document.getElementById('validity_period').options[0].textContent = `Till Contract End (${app.contract_period.split(' to ').pop()})`;
        }
    }

    // Load Qualified Personnel
    const tbody = document.getElementById('gpTableBody');
    const footer = document.getElementById('gpTableFooter');
    
    const res = await window.apiFetch(`get_gate_passes.php?application_id=${encodeURIComponent(appId)}`);
    const workers = normalizeArray(res);

    if (workers.length > 0) {
        tbody.innerHTML = workers.map(p => `
          <tr>
            <td><input type="checkbox" checked class="worker-checkbox" value="${p.id}"></td>
            <td><strong>${p.name}</strong></td>
            <td><span style="font-family:monospace;font-weight:700;color:var(--primary);">${p.temp_id || p.workman_id || '-'}</span></td>
            <td>${p.role || p.trade || '-'}</td>
            <td><span class="badge badge-success">${p.training || 'Qualified'}</span></td>
            <td>
              <select class="form-control" style="font-size:12px;padding:4px 8px;" data-zone="${p.id}">
                <option selected>Zone A & B</option>
                <option>Zone A only</option>
                <option>Zone B only</option>
                <option>All Zones</option>
              </select>
            </td>
            <td>
              <select class="form-control" style="font-size:12px;padding:4px 8px;" data-hours="${p.id}">
                <option selected>06:00-22:00</option>
                <option>24x7</option>
                <option>Day Only (06:00-18:00)</option>
                <option>Night Only (18:00-06:00)</option>
              </select>
            </td>
          </tr>
        `).join('');
        footer.textContent = `Showing ${workers.length} qualified personnel for ${appId}.`;
        document.getElementById('selectAllGP').nextSibling.textContent = ` Select All (${workers.length})`;
    } else {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:60px;color:var(--gray-400);"><i class="fas fa-users-slash" style="font-size:48px;"></i><br><strong>No qualified personnel found</strong><br><small>Complete safety training first.</small></td></tr>';
        footer.textContent = 'No eligible personnel found.';
    }
}

function selectAllGP() {
    const checked = document.getElementById('selectAllGP').checked;
    document.querySelectorAll('#gpTable input[type="checkbox"]').forEach(cb => cb.checked = checked);
}

async function submitGP() {
    if (!document.getElementById('gp-agree').checked) {
        showToast('⚠️', 'Please accept the declaration.');
        return;
    }

    const appId = window.getAppId();
    const workerIds = Array.from(document.querySelectorAll('.worker-checkbox:checked')).map(cb => cb.value);
    
    if (workerIds.length === 0) {
        showToast('⚠️', 'Please select at least one workman.');
        return;
    }

    const res = await window.apiFetch('submit_gate_pass.php', {
        method: 'POST',
        body: JSON.stringify({
            application_id: appId,
            worker_ids: workerIds,
            entry_gate: document.getElementById('entry_gate').value,
            work_shift: document.getElementById('work_shift').value,
            access_type: document.getElementById('access_type').value,
            from_date: document.getElementById('from_date').value,
            to_date: document.getElementById('to_date').value
        })
    });

    if (res.success) {
        document.querySelector('#gpModal strong').textContent = res.request_id || 'GP-' + Date.now().toString().slice(-4);
        showModal('gpModal');
        loadData();
    } else {
        showToast('❌', res.error || 'Submission failed');
    }
}

document.addEventListener('DOMContentLoaded', loadData);
</script>

</body>
</html>

