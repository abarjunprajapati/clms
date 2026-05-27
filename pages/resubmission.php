<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resubmission – Contractor Portal</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-redo"></i></div>
    <div>
      <div class="topbar-title">Resubmission Portal</div>
      <div class="topbar-subtitle">Contractor Portal · Correct & Resubmit</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="contractor-dashboard.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-home"></i> Dashboard</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<div class="page-container">
  <div class="page-header">
    <div class="page-title">Application Resubmission</div>
    <div class="page-subtitle">Your application was returned for resubmission. Review the rejection remarks and correct the issues before resubmitting.</div>
  </div>

  <!-- Rejection Notice -->
  <div class="alert alert-danger" style="margin-bottom:20px">
    <i class="fas fa-times-circle" style="font-size:18px"></i>
    <div>
      <strong>Application Returned for Resubmission</strong><br />
      <span style="font-size:12px">Reference: <strong>ANN-2025-0842</strong> · Returned by: Welfare Authority on <strong>04 Apr 2025</strong></span>
    </div>
  </div>

  <!-- Rejection Remarks -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-comment-alt" style="color:var(--danger)"></i> Rejection Remarks from Welfare Authority</div>
      <span class="badge badge-danger">Action Required</span>
    </div>
    <div class="card-body">
      <div style="background:#fef2f2;border-radius:8px;padding:16px;border-left:4px solid var(--danger);margin-bottom:14px">
        <div style="font-size:12px;color:var(--gray-500);margin-bottom:4px">Remarks by: Rajesh Kumar (Welfare Authority) · 04 Apr 2025</div>
        <div style="font-size:13px;color:var(--gray-800)">1. Labour Licence copy is illegible – please upload a clear scan.<br />2. EPF Code provided does not match SAP records – verify and correct.<br />3. Number of female workmen is missing in the declaration.</div>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <span class="badge badge-danger"><i class="fas fa-times"></i> Labour Licence – Illegible</span>
        <span class="badge badge-danger"><i class="fas fa-times"></i> EPF Code Mismatch</span>
        <span class="badge badge-danger"><i class="fas fa-times"></i> Female Workmen Count Missing</span>
      </div>
    </div>
  </div>

  <!-- Points to Correct -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list-check"></i> Correction Checklist</div>
    </div>
    <div class="card-body">
      <div id="correctionList">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--gray-100)">
          <div style="display:flex;align-items:center;gap:10px">
            <input type="checkbox" id="c1" style="width:16px;height:16px" onchange="updateChecklist()" />
            <label for="c1" style="font-size:13px;cursor:pointer"><strong>Labour Licence Copy</strong> — Upload a clear, legible scan</label>
          </div>
          <span class="badge badge-danger">Pending</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--gray-100)">
          <div style="display:flex;align-items:center;gap:10px">
            <input type="checkbox" id="c2" style="width:16px;height:16px" onchange="updateChecklist()" />
            <label for="c2" style="font-size:13px;cursor:pointer"><strong>EPF Code</strong> — Correct EPF code as per SAP records</label>
          </div>
          <span class="badge badge-danger">Pending</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0">
          <div style="display:flex;align-items:center;gap:10px">
            <input type="checkbox" id="c3" style="width:16px;height:16px" onchange="updateChecklist()" />
            <label for="c3" style="font-size:13px;cursor:pointer"><strong>Female Workmen Count</strong> — Fill in actual number in Part C</label>
          </div>
          <span class="badge badge-danger">Pending</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Resubmission Form -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-edit"></i> Corrected Submission</div>
    </div>
    <div class="card-body">
      <div class="form-row-3" style="margin-bottom:16px">
        <div class="form-group">
          <label class="form-label">Contractor Name <span class="sap-badge">SAP</span></label>
          <input class="form-control sap-field" value="Ravi Constructions Pvt. Ltd." disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Application Reference <span class="sap-badge">Auto</span></label>
          <input class="form-control sap-field" value="ANN-2025-0842" disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Resubmission No.</label>
          <input class="form-control sap-field" value="RSB-0001" disabled />
        </div>
      </div>

      <div class="section-divider"><span>Corrections</span></div>

      <!-- EPF Code Correction -->
      <div class="form-group">
        <label class="form-label">Corrected EPF Code <span class="required">*</span></label>
        <input class="form-control" placeholder="e.g., MH/PUN/12345/001" />
        <div class="form-hint" style="color:var(--danger)">Previous value was incorrect. Please verify with EPF certificate.</div>
      </div>

      <!-- Female Workmen -->
      <div class="form-group">
        <label class="form-label">Number of Female Workmen <span class="required">*</span></label>
        <input class="form-control" type="number" placeholder="e.g., 5" />
      </div>

      <!-- Re-upload Labour Licence -->
      <div class="form-group">
        <label class="form-label">Re-upload Labour Licence (Clear Scan) <span class="required">*</span></label>
        <div class="upload-area" onclick="document.getElementById('reupload').click()">
          <i class="fas fa-cloud-upload-alt" style="font-size:22px;margin-bottom:8px"></i>
          <div style="font-size:13px;font-weight:600">Upload Clear Scan</div>
          <div style="font-size:11px;color:var(--gray-400)">PDF · Min 300 DPI · Max 5MB</div>
        </div>
        <input type="file" id="reupload" style="display:none" />
      </div>

      <!-- Additional Comments -->
      <div class="form-group">
        <label class="form-label">Comments / Explanation</label>
        <textarea class="form-control" rows="3" placeholder="Optionally explain any corrections made..."></textarea>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:40px">
    <a href="contractor-dashboard.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
    <button class="btn btn-primary" onclick="resubmitApplication()">
      <i class="fas fa-paper-plane"></i> Resubmit Application
    </button>
  </div>
</div>

<!-- Success Modal -->
<div id="resubModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-body" style="text-align:center;padding:40px 30px">
      <div style="width:70px;height:70px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:var(--success)">
        <i class="fas fa-check-circle"></i>
      </div>
      <h2 style="font-size:20px;font-weight:700;margin-bottom:8px">Resubmitted Successfully!</h2>
      <p style="font-size:13px;color:var(--gray-500);margin-bottom:24px">Your corrected application has been submitted. Reference: <strong>ANN-2025-0842-RSB-001</strong><br />Welfare team has been notified via SMS & Email.</p>
      <div style="display:flex;gap:10px;justify-content:center">
        <a href="contractor-dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <button class="btn btn-outline" onclick="hideModal('resubModal')">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../js/navigation.js"></script>
<script>
  function updateChecklist() {
    const items = document.querySelectorAll('#correctionList input[type="checkbox"]');
    items.forEach(cb => {
      const badge = cb.closest('div[style]').nextElementSibling;
      if (badge) badge.className = cb.checked ? 'badge badge-success' : 'badge badge-danger';
      if (badge) badge.textContent = cb.checked ? 'Done' : 'Pending';
    });
  }
  function resubmitApplication() { showModal('resubModal'); }
</script>
</body>
</html>

