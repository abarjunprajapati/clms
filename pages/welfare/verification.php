<?php


require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'execution_officer', 'pass_user']);
include '../../include/config.php';

// Accept both 'welfare_user' and 'welfare_user' (normalized by session.php)
$_welfareRole = $_SESSION['role'] ?? '';
if (!in_array($_welfareRole, ['welfare_user', 'welfare_user', 'welfare_admin', 'admin'])) {
    header('Location: ../index.php');
    exit('Unauthorized - Welfare access required');
}


// Fetch stats using workflow_status column
// Fetch stats initially (will be updated by JS)
$pending         = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='verified'");
$verified_today  = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='welfare_approved' AND DATE(updated_at)=CURDATE()");
$query_count     = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='verified' AND remarks IS NOT NULL AND remarks <> ''");
$forwarded       = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status NOT IN ('submitted', 'verified', 'rejected')");


// Handle selected record
$selected_record = null;
$representatives = [];
if (isset($_GET['ref'])) {
    $ref_stmt = $conn->prepare("SELECT * FROM annexure2a WHERE application_id = ?");
    $ref_stmt->bind_param('s', $_GET['ref']);
    $ref_stmt->execute();
    $result = $ref_stmt->get_result();
    $selected_record = $result->fetch_assoc();
    
    if ($selected_record) {
        // Fetch representatives
        $reps_stmt = $conn->prepare("SELECT * FROM annexure3a WHERE application_id = ?");
        $reps_stmt->bind_param('s', $selected_record['application_id']);
        $reps_stmt->execute();
        $reps_result = $reps_stmt->get_result();
        while ($rep = $reps_result->fetch_assoc()) {
            $representatives[] = $rep;
        }
    }
}

// Notification count fallback
$role = $_SESSION['role'];
$notif_count = db_count($conn, "SELECT COUNT(*) c FROM notifications WHERE role_target=? AND is_read=0", 's', [$role]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welfare User – Verification Screen</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-user-shield"></i></div>
    <div>
      <div class="topbar-title">Welfare User – Application Verification</div>
      <div class="topbar-subtitle">Welfare Team Portal · Role: Welfare User</div>
    </div>
  </div>
    <div class="topbar-right">
    <div class="topbar-notif"><i class="fas fa-bell" style="font-size:18px"></i><div class="notif-badge"><?= $notif_count ?></div></div>
    <div class="topbar-user">
      <div class="user-avatar">WU</div>
      <div><div style="font-size:13px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Welfare User') ?></div><div style="font-size:11px;opacity:0.7">Welfare User · Zone A</div></div>
    </div>
  </div>
</div>


<div class="layout-wrapper">
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Welfare</div>
      <a href="welfare-verification.php" class="sidebar-item active"><i class="fas fa-check-double"></i> Verification Queue</a>
      <a href="welfare-approval.php" class="sidebar-item"><i class="fas fa-stamp"></i> Approval Panel</a>
      <a href="resubmission.php" class="sidebar-item"><i class="fas fa-redo"></i> Resubmissions</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Notifications</div>
<span class="badge badge-danger" style="margin-left:auto"><?= $notif_count ?></span>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Access</div>
      <a href="../index.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="main-content">
    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-inbox"></i></div>
        <div class="stat-value" data-stat="welfare_pending"><?= $pending ?></div><div class="stat-label">Pending Verification</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-check"></i></div>
        <div class="stat-value" data-stat="verified_today"><?= $verified_today ?></div><div class="stat-label">Verified Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times"></i></div>
        <div class="stat-value" data-stat="query_count"><?= $query_count ?></div><div class="stat-label">Queries Raised</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-forward"></i></div>
        <div class="stat-value" data-stat="approved"><?= $forwarded ?></div><div class="stat-label">Forwarded to Authority</div>
      </div>
    </div>


    <!-- Applications Queue -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-inbox"></i> Verification Queue</div>
        <div style="display:flex;gap:8px">
          <input class="form-control" style="width:200px" placeholder="Search contractor..." />
          <select class="form-control" style="width:160px">
            <option>All Status</option>
            <option>Pending</option>
            <option>In Progress</option>
            <option>Verified</option>
          </select>
        </div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ref No.</th>
              <th>Contractor Name</th>
              <th>SAP Code</th>
              <th>Annexures</th>
              <th>Submitted On</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="applicationQueueBody">
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500)"><i class="fas fa-spinner fa-spin"></i> Loading applications...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

<?php if ($selected_record): ?>
    <!-- Detailed Verification Panel -->
    <div class="card" id="verifyPanel" style="display:block;margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-search"></i> Detailed Verification — <?= htmlspecialchars($selected_record['contractor_name']) ?></div>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('verifyPanel').style.display='none'"><i class="fas fa-times"></i> Close</button>
      </div>
<?php else: ?>
    <!-- Empty state for no selected record -->
<div class="empty-state" id="welfareVerifyContainer">
      <i class="fas fa-search" style="font-size:64px;color:var(--gray-300)"></i>
      <h3 id="welfareVerifyTitle">Select application to verify</h3>
      <p>Click "Review" in queue → loads dynamic verification dashboard</p>
      <div class="mt-3">
        <button class="btn btn-primary" onclick="loadSampleVerification()">
          <i class="fas fa-play"></i> Load Sample (CMS-2024-001)
        </button>
      </div>
    </div>
<?php endif; ?>

      <div class="card-body">
        <!-- Progress Tracker -->
        <div id="workflowProgressContainer"></div>

        <div class="tabs">
          <button class="tab-btn active" data-tab="vt-details" onclick="switchTab('vt-details')">Contractor Details</button>
          <button class="tab-btn" data-tab="vt-docs" onclick="switchTab('vt-docs')">Documents</button>
          <button class="tab-btn" data-tab="vt-manpower" onclick="switchTab('vt-manpower')">Manpower</button>
          <button class="tab-btn" data-tab="vt-rep" onclick="switchTab('vt-rep')">Representatives</button>
          <button class="tab-btn" data-tab="vt-checklist" onclick="switchTab('vt-checklist')">Checklist</button>
        </div>

        <!-- Details Tab -->
        <div class="tab-panel active" id="vt-details">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div id="detailsLeft">
              <h4 style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:10px">SAP-Verified Fields</h4>
              <div class="info-row"><span class="info-label">Ref ID</span><span class="info-value" id="val-ref_id">-</span></div>
              <div class="info-row"><span class="info-label">Contractor Name</span><span class="info-value" id="val-contractor_name">- <span class="sap-badge">SAP ✓</span></span></div>
              <div class="info-row"><span class="info-label">Contractor ID</span><span class="info-value" id="val-contractor_id">- <span class="sap-badge">SAP ✓</span></span></div>
              <div class="info-row"><span class="info-label">Labour Licence</span><span class="info-value" id="val-labour_license">-</span></div>
              <div class="info-row"><span class="info-label">Licence Validity</span><span class="info-value" id="val-labour_validity">-</span></div>
            </div>
            <div id="detailsRight">
              <h4 style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Compliance Details</h4>
              <div class="info-row"><span class="info-label">EPF Code</span><span class="info-value" id="val-epf_code">-</span></div>
              <div class="info-row"><span class="info-label">ESIC Code</span><span class="info-value" id="val-esic_code">-</span></div>
              <div class="info-row"><span class="info-label">Office Address</span><span class="info-value" id="val-office_address">-</span></div>
              <div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge" id="val-status">Pending</span></span></div>
              <div class="info-row"><span class="info-label">Submitted</span><span class="info-value" id="val-created_at">-</span></div>
            </div>
          </div>
        </div>


        <!-- Documents Tab -->
        <div class="tab-panel" id="vt-docs">
          <table class="data-table">
            <thead><tr><th>Document</th><th>File</th><th>Upload Date</th><th>Verification</th><th>Action</th></tr></thead>
            <tbody id="docsTableBody">
              <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--gray-500)">No documents uploaded for this application</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Manpower Tab -->
        <div class="tab-panel" id="vt-manpower">
          <div class="form-row-3" id="manpowerStats">
            <div class="stat-card"><div class="stat-value" id="val-total_workmen">0</div><div class="stat-label">Total Proposed</div></div>
            <div class="stat-card"><div class="stat-value" id="val-skilled">0</div><div class="stat-label">Skilled</div></div>
            <div class="stat-card"><div class="stat-value" id="val-unskilled">0</div><div class="stat-label">Unskilled</div></div>
            <div class="stat-card"><div class="stat-value" id="val-supervisors">0</div><div class="stat-label">Supervisors</div></div>
            <div class="stat-card"><div class="stat-value" id="val-representatives">0</div><div class="stat-label">Representatives</div></div>
            <div class="stat-card"><div class="stat-value" id="val-deployment_date">N/A</div><div class="stat-label">Deployment Date</div></div>
          </div>
        </div>


        <!-- Representatives Tab -->
        <div class="tab-panel" id="vt-rep">
          <table class="data-table">
            <thead><tr><th>#</th><th>Name</th><th>Designation</th><th>Mobile</th><th>Aadhaar</th><th>Status</th></tr></thead>
            <tbody id="repsTableBody">
                <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--gray-500)">No representatives declared for this contractor</td></tr>
            </tbody>
          </table>
        </div>


        <!-- Checklist Tab -->
        <div class="tab-panel" id="vt-checklist">
          <div style="max-width:500px">
            <h4 style="font-size:14px;font-weight:600;margin-bottom:14px">Verification Checklist</h4>
            <div id="checklistItems">
              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
                <span style="font-size:13px">Labour Licence valid and not expired</span>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-success btn-sm check-yes" onclick="checkItem(this,'yes')"><i class="fas fa-check"></i> Yes</button>
                  <button class="btn btn-outline btn-sm check-no" onclick="checkItem(this,'no')"><i class="fas fa-times"></i> No</button>
                </div>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
                <span style="font-size:13px">EPF/ESIC registration active</span>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-success btn-sm check-yes" onclick="checkItem(this,'yes')"><i class="fas fa-check"></i> Yes</button>
                  <button class="btn btn-outline btn-sm check-no" onclick="checkItem(this,'no')"><i class="fas fa-times"></i> No</button>
                </div>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
                <span style="font-size:13px">Documents are legible and genuine</span>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-success btn-sm check-yes" onclick="checkItem(this,'yes')"><i class="fas fa-check"></i> Yes</button>
                  <button class="btn btn-outline btn-sm check-no" onclick="checkItem(this,'no')"><i class="fas fa-times"></i> No</button>
                </div>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
                <span style="font-size:13px">Manpower count matches contract scope</span>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-success btn-sm check-yes" onclick="checkItem(this,'yes')"><i class="fas fa-check"></i> Yes</button>
                  <button class="btn btn-outline btn-sm check-no" onclick="checkItem(this,'no')"><i class="fas fa-times"></i> No</button>
                </div>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0">
                <span style="font-size:13px">Representatives declared as per Customer Registration</span>
                <div style="display:flex;gap:6px">
                  <button class="btn btn-success btn-sm check-yes" onclick="checkItem(this,'yes')"><i class="fas fa-check"></i> Yes</button>
                  <button class="btn btn-outline btn-sm check-no" onclick="checkItem(this,'no')"><i class="fas fa-times"></i> No</button>
                </div>
              </div>
            </div>
            <div class="form-group" style="margin-top:16px">
              <label class="form-label">Verification Remarks</label>
              <textarea class="form-control" rows="3" placeholder="Add any observations or remarks..."></textarea>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <hr class="divider" />
        <input type="hidden" id="currentVerifyAppId" value="" />
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn btn-success" onclick="forwardToAuthority()"><i class="fas fa-forward"></i> Verified – Forward to Authority</button>
          <button class="btn btn-warning" onclick="raiseQuery()"><i class="fas fa-question-circle"></i> Raise Query</button>
          <button class="btn btn-danger" onclick="showModal('rejectModal')"><i class="fas fa-times"></i> Reject Application</button>
          <button class="btn btn-outline" onclick="document.getElementById('verifyPanel').style.display='none'"><i class="fas fa-times"></i> Cancel</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 style="font-size:16px;font-weight:700;color:var(--danger)"><i class="fas fa-times-circle"></i> Reject Application</h3>
      <i class="fas fa-times" style="cursor:pointer" onclick="hideModal('rejectModal')"></i>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Rejection Reason <span class="required">*</span></label>
        <select class="form-control">
          <option>Incomplete documentation</option>
          <option>Labour licence expired</option>
          <option>EPF/ESIC not valid</option>
          <option>Manpower count mismatch</option>
          <option>Other</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Detailed Remarks <span class="required">*</span></label>
        <textarea class="form-control" rows="4" placeholder="Provide detailed reason for rejection..."></textarea>
      </div>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Contractor will be notified via Email & SMS. They may resubmit after correction.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="hideModal('rejectModal')">Cancel</button>
      <button class="btn btn-danger" onclick="rejectApplication()"><i class="fas fa-times"></i> Confirm Rejection</button>
    </div>
  </div>
</div>

<script src="../../js/navigation.js"></script>
    <script src="../../js/utils.js"></script>
    <script src="../../js/screens.js"></script>

    <script>
      let currentAppId = null;

      async function loadQueue() {
        const body = document.getElementById('applicationQueueBody');
        const res = await window.apiFetch('get_welfare_applications.php?tab=pending');
        
        const apps = Array.isArray(res.data) ? res.data : (res.data ? [res.data] : []);
        if (res.success && apps.length > 0) {
          body.innerHTML = apps.map(app => {
            const appId = app.application_id || app.id || 'N/A';
            return `
            <tr>
              <td><strong>${app.application_id}</strong></td>
              <td>${app.contractor_name || 'N/A'}</td>
              <td>${app.contractor_id || 'N/A'}</td>
              <td><span class="badge badge-success">2/A</span></td>
              <td>${window.formatDate(app.submitted_at || app.created_at || new Date())}</td>
              <td><span class="badge badge-${(app.priority || 'medium') === 'high' ? 'danger' : 'warning'}">${(app.priority || 'Medium').toUpperCase()}</span></td>
              <td><span class="badge badge-warning">Pending</span></td>
              <td>
                <button class="btn btn-primary btn-sm" onclick="openVerify('${appId}')">
                  <i class="fas fa-eye"></i> Start Verification
                </button>
              </td>
            </tr>
          `;}).join('');
        } else {
          body.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-500)"><i class="fas fa-inbox" style="font-size:48px;opacity:0.3;margin-bottom:12px"></i><div style="font-size:18px">No applications pending verification</div></td></tr>';
        }
        
        // Refresh stats
        window.loadDashboardStats();
      }

      async function openVerify(appId) {
        if (!appId) return;
        currentAppId = appId;
        document.getElementById('currentVerifyAppId').value = appId;

        const panel = document.getElementById('verifyPanel');
        if (panel) {
          panel.style.display = 'block';
          panel.scrollIntoView({ behavior: 'smooth' });
        }
        
        if (document.getElementById('welfareVerifyTitle')) {
          document.getElementById('welfareVerifyTitle').textContent = 'Loading ' + appId + '...';
        }

        try {
          const res = await window.apiFetch(`get_application_details.php?application_id=${encodeURIComponent(appId)}`);
          if (!res.success) throw new Error(res.error || 'API returned error');
          
          const app = res.data.application || {};
          const reps = res.data.supervisors || [];
          
          // Populate Details
          if (document.getElementById('welfareVerifyTitle')) {
            document.getElementById('welfareVerifyTitle').textContent = (app.contractor_name || 'Application') + ' — ' + appId;
          }
          
          const appId = app.application_id || app.id || 'N/A';
          document.getElementById('val-ref_id').textContent = appId;
          document.getElementById('val-contractor_name').innerHTML = `${app.contractor_name || 'N/A'} <span class="sap-badge">SAP ✓</span>`;
          document.getElementById('val-contractor_id').innerHTML = `${app.contractor_id || 'N/A'} <span class="sap-badge">SAP ✓</span>`;
          document.getElementById('val-labour_license').textContent = app.labour_license || 'N/A';
          document.getElementById('val-labour_validity').textContent = window.formatDate(app.labour_validity);
          document.getElementById('val-epf_code').textContent = app.epf_code || 'N/A';
          document.getElementById('val-esic_code').textContent = app.esic_code || 'N/A';
          document.getElementById('val-office_address').textContent = app.office_address || 'N/A';
          document.getElementById('val-created_at').textContent = window.formatDate(app.submitted_at || app.created_at || new Date());
          
          const statusEl = document.getElementById('val-status');
          statusEl.textContent = app.workflow_status.toUpperCase();
          statusEl.className = 'badge ' + (app.workflow_status === 'verified' ? 'badge-warning' : 'badge-success');

          // Progress Bar
          document.getElementById('workflowProgressContainer').innerHTML = window.renderWorkflowProgress(app.workflow_status);

          // Manpower Stats
          document.getElementById('val-total_workmen').textContent = app.total_workmen || 0;
          document.getElementById('val-skilled').textContent = app.skilled || 0;
          document.getElementById('val-unskilled').textContent = app.unskilled || 0;
          document.getElementById('val-supervisors').textContent = app.supervisors || 0;
          document.getElementById('val-representatives').textContent = app.representatives || 0;
          document.getElementById('val-deployment_date').textContent = window.formatDate(app.deployment_date);

          // Representatives Table
          const repsBody = document.getElementById('repsTableBody');
          if (reps.length > 0) {
            repsBody.innerHTML = reps.map((r, i) => `
              <tr>
                <td>${i+1}</td>
                <td>${r.representative_name || 'N/A'}</td>
                <td>${r.designation || 'N/A'}</td>
                <td>${r.mobile || 'N/A'}</td>
                <td>${r.aadhaar ? 'XXXX-XXXX-' + r.aadhaar.slice(-4) : 'N/A'}</td>
                <td><span class="badge badge-success">Declared</span></td>
              </tr>
            `).join('');
          } else {
            repsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--gray-500)">No representatives declared</td></tr>';
          }

          // Docs Table
          const docsBody = document.getElementById('docsTableBody');
          const docs = res.data.documents || [];
          if (docs.length > 0) {
            docsBody.innerHTML = docs.map(d => `
              <tr>
                <td><strong>${d.doc_key ? d.doc_key.toUpperCase().replace(/_/g, ' ') : d.doc_name}</strong></td>
                <td><a href="../../uploads/${d.file_path}" target="_blank" class="text-primary"><i class="fas fa-file-pdf"></i> View Document</a></td>
                <td>${window.formatDate(d.uploaded_at)}</td>
                <td><span class="badge badge-${d.status === 'approved' ? 'success' : (d.status === 'rejected' ? 'danger' : 'warning')}">${d.status.toUpperCase()}</span></td>
                <td>
                  <button class="btn btn-sm btn-success" onclick="verifyDoc(${d.id}, 'approved')"><i class="fas fa-check"></i></button>
                  <button class="btn btn-sm btn-danger" onclick="verifyDoc(${d.id}, 'rejected')"><i class="fas fa-times"></i></button>
                </td>
              </tr>
            `).join('');
          } else {
            docsBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--gray-500)">No documents uploaded for this application</td></tr>';
          }

        } catch (error) {
          console.error('Verification load error:', error);
          alert('Failed to load details: ' + error.message);
        }
      }

      async function forwardToAuthority() {
        if (!currentAppId) { alert('No application selected.'); return; }
        const remarks = document.querySelector('#vt-checklist textarea')?.value || 'Verified by welfare user';
        if (!confirm('Forward application ' + currentAppId + ' to Welfare Authority for approval?')) return;

        try {
          const res = await window.apiFetch('update_status.php', {
            method: 'POST',
            body: JSON.stringify({ application_id: currentAppId, action: 'forward', remarks: remarks })
          });
          if (res.success) {
            showToast('✅', 'Application forwarded to Welfare Authority.');
            loadQueue();
            document.getElementById('verifyPanel').style.display = 'none';
          } else {
            alert('Error: ' + (res.error || 'Unknown error'));
          }
        } catch (e) {
          alert('Network error: ' + e.message);
        }
      }

      async function raiseQuery() {
        if (!currentAppId) { alert('No application selected.'); return; }
        const query = prompt('Enter your query / observation for the contractor:');
        if (!query) return;

        try {
          const res = await window.apiFetch('update_status.php', {
            method: 'POST',
            body: JSON.stringify({ application_id: currentAppId, action: 'verify', remarks: query })
          });
          if (res.success) {
            showToast('❓', 'Query raised successfully.');
            loadQueue();
            document.getElementById('verifyPanel').style.display = 'none';
          } else {
            alert('Error: ' + (res.error || 'Unknown error'));
          }
        } catch (e) {
          alert('Network error: ' + e.message);
        }
      }

      async function rejectApplication() {
        if (!currentAppId) { alert('No application selected.'); return; }
        const remarkEl = document.querySelector('#rejectModal textarea');
        const reason = remarkEl ? remarkEl.value.trim() : '';
        if (!reason) { alert('Please enter rejection reason.'); return; }

        try {
          const res = await window.apiFetch('update_status.php', {
            method: 'POST',
            body: JSON.stringify({ application_id: currentAppId, action: 'reject', remarks: reason })
          });
          if (res.success) {
            hideModal('rejectModal');
            showToast('❌', 'Application rejected.');
            loadQueue();
            document.getElementById('verifyPanel').style.display = 'none';
          } else {
            alert('Error: ' + (res.error || 'Unknown error'));
          }
        } catch (e) {
          alert('Network error: ' + e.message);
        }
      }

      async function verifyDoc(docId, status) {
        if (!confirm(`Are you sure you want to mark this document as ${status.toUpperCase()}?`)) return;
        
        try {
          const res = await window.apiFetch('../../api/welfare/update_document_status.php', {
            method: 'POST',
            body: JSON.stringify({ document_id: docId, status: status })
          });
          
          if (res.success) {
            showToast(status === 'approved' ? '✅' : '❌', `Document marked as ${status.toUpperCase()}`);
            // Refresh the verification panel
            openVerify(currentAppId);
          } else {
            alert('Error: ' + (res.error || 'Unknown error'));
          }
        } catch (e) {
          alert('Network error: ' + e.message);
        }
      }

      // Initialize
      document.addEventListener('DOMContentLoaded', () => {
        loadQueue();
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('ref')) openVerify(urlParams.get('ref'));
      });
    </script>

</body>
</html>

