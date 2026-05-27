<?php


require_once '../../include/auth.php';
checkAuth(['welfare_user', 'super_admin', 'execution_officer']);
include '../../include/config.php';

$_role = $_SESSION['role'] ?? '';
if (!in_array($_role, ['welfare_user', 'authority', 'admin'])) {
    header('Location: ../index.php');
    exit('Unauthorized');
}

$role = $_SESSION['role'];
// Stats using workflow_status column
$awaiting       = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='verified'");
$approved_month = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status IN ('welfare_approved', 'acc_approved', 'pass_generated') AND MONTH(updated_at)=MONTH(CURDATE())");
$rejected       = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='rejected'");
$resub          = db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status='verified' AND remarks LIKE '%resubmit%'");
$notif_count    = db_count($conn, "SELECT COUNT(*) c FROM notifications WHERE role_target=? AND is_read=0", 's', [$role]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welfare Authority – Approval / Reject Panel</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-stamp"></i></div>
    <div>
      <div class="topbar-title">Welfare Authority – Approval Panel</div>
      <div class="topbar-subtitle">Welfare Portal · Role: Welfare Authority</div>
    </div>
  </div>
  <div class="topbar-right">
<div class="notif-badge"><?= $notif_count ?></div>
    <div class="topbar-user">
      <div class="user-avatar">WA</div>
<div style="font-size:13px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Welfare Authority') ?></div><div style="font-size:11px;opacity:0.7">Welfare Authority</div>
    </div>
  </div>
</div>

<div class="layout-wrapper">
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Welfare Authority</div>
      <a href="welfare-verification.php" class="sidebar-item"><i class="fas fa-check-double"></i> Verification Queue</a>
      <a href="welfare-approval.php" class="sidebar-item active"><i class="fas fa-stamp"></i> Approval Panel</a>
      <a href="notifications.php" class="sidebar-item"><i class="fas fa-bell"></i> Notifications</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Onboarding</div>
      <a href="enrolment-4a.php" class="sidebar-item"><i class="fas fa-users"></i> Enrolment Review</a>
      <a href="welfare-pass-approval.php" class="sidebar-item"><i class="fas fa-door-open"></i> Gate Pass Approval</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Access</div>
      <a href="../index.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="main-content">
    <div class="page-header">
      <div class="page-title">Approval / Reject Workflow</div>
      <div class="page-subtitle">Applications forwarded by Welfare User for final authority decision. Approved applications trigger enrolment process.</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-inbox"></i></div>
        <div class="stat-value" data-stat="welfare_pending"><?= $awaiting ?></div><div class="stat-label">Awaiting Approval</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value" data-stat="approved"><?= $approved_month ?></div><div class="stat-label">Approved (Total)</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times-circle"></i></div>
        <div class="stat-value" data-stat="rejected"><?= $rejected ?></div><div class="stat-label">Rejected</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-redo"></i></div>
        <div class="stat-value" data-stat="resubmit"><?= $resub ?></div><div class="stat-label">Resubmissions</div>
      </div>
    </div>

    <!-- Approval Queue -->
    <div class="card" style="margin-bottom:20px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-list-alt"></i> Applications Awaiting Approval</div>
        <select class="form-control" style="width:180px">
          <option>All Applications</option>
          <option>Pending Approval</option>
          <option>Approved</option>
          <option>Rejected</option>
        </select>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ref No.</th>
              <th>Contractor</th>
              <th>Verified By</th>
              <th>Verification Date</th>
              <th>Workmen</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
            <tbody id="approvalQueueBody">
              <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500)"><i class="fas fa-spinner fa-spin"></i> Loading approval queue...</td></tr>
            </tbody>
        </table>
      </div>
    </div>

    <!-- Approval Decision Panel -->
    <div id="approvalPanel" style="display:none">
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <div class="card-title" id="approvalPanelTitle"><i class="fas fa-gavel"></i> Approval Decision</div>
          <button class="btn btn-outline btn-sm" onclick="document.getElementById('approvalPanel').style.display='none'"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
          <!-- Workflow Progress -->
          <div id="workflowProgressContainer"></div>

          <!-- Workflow Status -->
          <div class="alert alert-info" style="margin-bottom:20px">
            <i class="fas fa-info-circle"></i>
            <div id="approvalInfoAlert">Please review and make your decision. Approved applications trigger enrolment process.</div>
          </div>

          <!-- Summary -->
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">
            <div style="background:var(--gray-50);border-radius:10px;padding:14px">
              <div style="font-size:11px;color:var(--gray-500);font-weight:600;text-transform:uppercase;margin-bottom:8px">Contractor & Dept</div>
              <div class="info-row"><span class="info-label">Name</span><span class="info-value" id="sum-contractor_name">-</span></div>
              <div class="info-row"><span class="info-label">SAP Code</span><span class="info-value" id="sum-contractor_id">-</span></div>
              <div class="info-row"><span class="info-label">Department</span><span class="info-value" id="sum-department" style="color:var(--primary);font-weight:700;">-</span></div>
            </div>
            <div style="background:var(--gray-50);border-radius:10px;padding:14px">
              <div style="font-size:11px;color:var(--gray-500);font-weight:600;text-transform:uppercase;margin-bottom:8px">Statutory & Policy</div>
              <div class="info-row"><span class="info-label">EPF Code</span><span class="info-value" id="sum-epf">-</span></div>
              <div class="info-row"><span class="info-label">ESI Code</span><span class="info-value" id="sum-esi">-</span></div>
              <div class="info-row"><span class="info-label">ECP Policy</span><span class="info-value" id="sum-policy">-</span></div>
            </div>
            <div style="background:var(--gray-50);border-radius:10px;padding:14px">
              <div style="font-size:11px;color:var(--gray-500);font-weight:600;text-transform:uppercase;margin-bottom:8px">Manpower (Proposed)</div>
              <div class="info-row"><span class="info-label">Skilled</span><span class="info-value" id="sum-skilled">0</span></div>
              <div class="info-row"><span class="info-label">Semi/Unskilled</span><span class="info-value" id="sum-semi_unskilled">0</span></div>
              <div class="info-row"><span class="info-label">Total Workmen</span><span class="info-value" id="sum-total_workmen" style="font-weight:800;color:var(--primary);">0</span></div>
            </div>
          </div>

          <!-- Documents Review Section -->
          <div class="card" style="margin-bottom:20px; border-color:var(--primary);">
            <div class="card-header" style="background:rgba(99, 102, 241, 0.05);">
               <div class="card-title"><i class="fas fa-file-signature"></i> Contractor Documents</div>
            </div>
            <div class="card-body" style="padding:0">
              <table class="data-table" style="font-size:13px;">
                <thead>
                  <tr>
                    <th>Document Name</th>
                    <th>File</th>
                    <th>Status</th>
                    <th>Uploaded On</th>
                  </tr>
                </thead>
                <tbody id="approvalDocsBody">
                  <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--gray-500)">No documents found</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Welfare User Checklist Result -->
          <div class="card" style="margin-bottom:20px;border-color:var(--success)">
            <div class="card-header" style="background:#f0fdf4">
              <div class="card-title"><i class="fas fa-check-double" style="color:var(--success)"></i> Welfare User Verification Summary</div>
              <span class="badge badge-success">All Clear</span>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-wrap:wrap;gap:12px">
                <div style="display:flex;align-items:center;gap:6px;font-size:12px"><i class="fas fa-check-circle" style="color:var(--success)"></i> Labour Licence Valid</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:12px"><i class="fas fa-check-circle" style="color:var(--success)"></i> EPF/ESIC Active</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:12px"><i class="fas fa-check-circle" style="color:var(--success)"></i> Documents Genuine</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:12px"><i class="fas fa-check-circle" style="color:var(--success)"></i> Manpower Verified</div>
                <div style="display:flex;align-items:center;gap:6px;font-size:12px"><i class="fas fa-check-circle" style="color:var(--success)"></i> Representatives Declared</div>
              </div>
              <div style="margin-top:10px;font-size:12px;color:var(--gray-600);padding-top:10px;border-top:1px solid var(--gray-100)">
                <strong>Remarks by Priya Sharma:</strong> All documents verified and found in order. Recommend approval for enrolment.
              </div>
            </div>
          </div>

          <!-- Authority Decision Form -->
          <div class="form-group">
            <label class="form-label">Authority Approval Remarks <span class="required">*</span></label>
            <textarea class="form-control" rows="3" id="approvalRemarks" placeholder="Enter your decision remarks..."></textarea>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">Approved Workmen Strength</label>
              <input class="form-control" type="number" value="50" id="approvedStrength" />
              <div class="form-hint">Can be modified as per site capacity</div>
            </div>
            <div class="form-group">
              <label class="form-label">Validity Period</label>
              <select class="form-control">
                <option>Till Contract End (31 Dec 2025)</option>
                <option>6 Months</option>
                <option>3 Months</option>
                <option>1 Year</option>
              </select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px">
            <button class="btn btn-success btn-lg" onclick="approveApplication()">
              <i class="fas fa-check-circle"></i> Approve Application
            </button>
            <button class="btn btn-danger" onclick="showModal('rejectApprovalModal')">
              <i class="fas fa-times-circle"></i> Reject Application
            </button>
            <button class="btn btn-warning" onclick="requestResubmission()">
              <i class="fas fa-redo"></i> Request Resubmission
            </button>
            <button class="btn btn-outline" onclick="document.getElementById('approvalPanel').style.display='none'">
              <i class="fas fa-times"></i> Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- Workflow Diagram -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-sitemap"></i> Approval Workflow</div></div>
        <div class="card-body">
          <div class="workflow-chart">
            <div class="wf-node wf-start"><span class="wf-icon">📋</span><div><div class="wf-label">Contractor Submission</div><div class="wf-sublabel">Contractor Registration & Customer Registration</div></div></div>
            <div class="wf-arrow">↓</div>
            <div class="wf-node wf-action"><span class="wf-icon">🔍</span><div><div class="wf-label">Welfare User Verification</div><div class="wf-sublabel">Documents & Checklist</div></div></div>
            <div class="wf-arrow">↓</div>
            <div class="wf-node wf-decision" style="border-color:#f59e0b;background:#fffbeb"><span class="wf-icon">⚖️</span><div><div class="wf-label">Welfare Authority Decision</div><div class="wf-sublabel">Approve / Reject / Resubmit</div></div></div>
            <div style="display:flex;gap:60px;align-items:flex-start;margin:8px 0">
              <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                <span style="font-size:11px;color:var(--success);font-weight:600">✓ APPROVED</span>
                <div class="wf-node wf-action" style="min-width:180px"><span class="wf-icon">📣</span><div><div class="wf-label">Intimation Sent</div><div class="wf-sublabel">SMS / Email / Push</div></div></div>
                <div class="wf-arrow">↓</div>
                <div class="wf-node wf-process" style="min-width:180px"><span class="wf-icon">👥</span><div><div class="wf-label">Enrolment (4/A)</div><div class="wf-sublabel">Workmen Registration</div></div></div>
              </div>
              <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                <span style="font-size:11px;color:var(--danger);font-weight:600">✗ REJECTED</span>
                <div class="wf-node" style="min-width:180px;border-color:var(--danger);background:#fef2f2"><span class="wf-icon">❌</span><div><div class="wf-label">Rejection Notice</div><div class="wf-sublabel">SMS / Email</div></div></div>
                <div class="wf-arrow">↓</div>
                <div class="wf-node wf-process" style="min-width:180px"><span class="wf-icon">🔄</span><div><div class="wf-label">Resubmission Option</div><div class="wf-sublabel">Contractor corrects & resubmits</div></div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div id="rejectApprovalModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 style="font-size:16px;font-weight:700;color:var(--danger)"><i class="fas fa-times-circle"></i> Reject Application</h3>
      <i class="fas fa-times" style="cursor:pointer" onclick="hideModal('rejectApprovalModal')"></i>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Rejection Ground <span class="required">*</span></label>
        <select class="form-control">
          <option>Does not meet welfare standards</option>
          <option>Inadequate manpower plan</option>
          <option>Contract scope mismatch</option>
          <option>Pending compliance obligations</option>
          <option>Other</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Detailed Remarks <span class="required">*</span></label>
        <textarea class="form-control" rows="4" placeholder="Specify grounds for rejection..."></textarea>
      </div>
      <div class="alert alert-warning">
        <i class="fas fa-bell"></i>
        <span>Contractor & Welfare User will be notified. Contractor may resubmit after addressing the remarks.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="hideModal('rejectApprovalModal')">Cancel</button>
      <button class="btn btn-danger" onclick="confirmReject()"><i class="fas fa-times"></i> Confirm Rejection</button>
    </div>
  </div>
</div>

<script src="../../js/utils.js"></script>
<script src="../../js/navigation.js"></script>

<script>
  let currentAppId = null;

  function safe(val) {
    return val ?? 'N/A';
  }

  async function loadQueue() {
    const body = document.getElementById('approvalQueueBody');
    body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500)"><i class="fas fa-spinner fa-spin"></i> Loading approval queue...</td></tr>';
    
    try {
      const res = await window.apiFetch('get_welfare_applications.php?tab=pending_approval');
      
      const apps = (res.success && res.data && Array.isArray(res.data.applications)) ? res.data.applications : [];
      
      if (apps.length > 0) {
        body.innerHTML = apps.map(app => {
          const appId = app.application_id || 'N/A';
          return `
          <tr>
            <td><strong>${safe(appId)}</strong></td>
            <td>${safe(app.contractor_name)}</td>
            <td>${safe(app.project_name)}</td>
            <td>${window.formatDate(app.submitted_at)}</td>
            <td>${safe(app.total_workmen)}</td>
            <td><span class="badge badge-info">${safe(app.workflow_status).replace(/_/g, ' ')}</span></td>
            <td>
              <button class="btn btn-primary btn-sm" onclick="openApproval('${appId}')">
                <i class="fas fa-gavel"></i> Review & Decide
              </button>
            </td>
          </tr>
        `;}).join('');
      } else {
        body.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500)"><i class="fas fa-inbox" style="font-size:48px;opacity:0.3;margin-bottom:12px;display:block"></i><div style="font-size:18px">No Data Found</div></td></tr>';
      }
    } catch (e) {
      body.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--danger)">Error: ${e.message}</td></tr>`;
    }
    
    if (typeof window.loadDashboardStats === 'function') window.loadDashboardStats();
  }

  async function openApproval(appId) {
    if (!appId) return;
    currentAppId = appId;
    const panel = document.getElementById('approvalPanel');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth' });

    try {
      const res = await window.apiFetch(`get_application_details.php?application_id=${encodeURIComponent(appId)}`);
      if (res.success && res.data) {
        const app = res.data.application || {};
        
        // Update panel
        document.getElementById('approvalPanelTitle').innerHTML = `<i class="fas fa-gavel"></i> Approval Decision &mdash; ${app.contractor_name || appId}`;
        document.getElementById('workflowProgressContainer').innerHTML = window.renderWorkflowProgress(app.workflow_status);
        
        // Summary
        document.getElementById('sum-contractor_name').textContent = app.contractor_name || 'N/A';
        document.getElementById('sum-contractor_id').textContent = app.contractor_id || 'N/A';
        document.getElementById('sum-department').textContent = app.work_awarding_department || 'N/A';
        document.getElementById('sum-epf').textContent = app.epf_code || 'N/A';
        document.getElementById('sum-esi').textContent = app.esi_code || 'N/A';
        document.getElementById('sum-policy').textContent = app.ecp_number || 'N/A';
        document.getElementById('sum-skilled').textContent = app.skilled_count || 0;
        document.getElementById('sum-semi_unskilled').textContent = (parseInt(app.semi_skilled_count)||0) + (parseInt(app.unskilled_count)||0);
        document.getElementById('sum-total_workmen').textContent = app.workers_proposed || 0;

        const strengthEl = document.getElementById('approvedStrength');
        if (strengthEl) strengthEl.value = app.workers_proposed || 0;

        // Render Documents
        const docsBody = document.getElementById('approvalDocsBody');
        const docs = res.data.documents || [];
        if (docs.length > 0) {
          docsBody.innerHTML = docs.map(d => `
            <tr>
              <td><strong>${(d.doc_name || d.doc_key || 'Document').replace(/_/g, ' ')}</strong></td>
              <td><a href="${d.file_path}" target="_blank" class="text-primary"><i class="fas fa-external-link-alt"></i> View File</a></td>
              <td><span class="badge badge-${d.status === 'approved' ? 'success' : (d.status === 'rejected' ? 'danger' : 'warning')}">${(d.status || 'pending').toUpperCase()}</span></td>
              <td>${window.formatDate(d.uploaded_at)}</td>
            </tr>
          `).join('');
        } else {
          docsBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--gray-500)">No documents uploaded for this contractor</td></tr>';
        }
      }
    } catch (e) {
      console.error('Failed to load approval data:', e);
    }
  }

  async function approveApplication() {
    if (!currentAppId) { alert('No application selected.'); return; }
    const remarks = document.getElementById('approvalRemarks')?.value?.trim() || '';
    if (!remarks) { alert('Please enter approval remarks.'); return; }
    if (!confirm('Approve application ' + currentAppId + '?')) return;

    try {
      const res = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'approve', remarks })
      });
      if (res.success) {
        showToast('✅', 'Application approved successfully!');
        loadQueue();
        document.getElementById('approvalPanel').style.display = 'none';
      } else {
        alert('Error: ' + (res.error || 'Unknown error'));
      }
    } catch (e) {
      alert('Network error: ' + e.message);
    }
  }

  async function requestResubmission() {
    if (!currentAppId) { alert('No application selected.'); return; }
    const reason = prompt('Enter reason for resubmission request:');
    if (!reason) return;

    try {
      const res = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'resubmit', remarks: reason })
      });
      if (res.success) {
        showToast('🔄', 'Resubmission request sent.');
        loadQueue();
        document.getElementById('approvalPanel').style.display = 'none';
      } else {
        alert('Error: ' + (res.error || 'Unknown error'));
      }
    } catch (e) {
      alert('Network error: ' + e.message);
    }
  }

  async function confirmReject() {
    if (!currentAppId) { alert('No application selected.'); return; }
    const remarkEl = document.querySelector('#rejectApprovalModal textarea');
    const reason = remarkEl ? remarkEl.value.trim() : '';
    if (!reason) { alert('Please enter rejection reason.'); return; }

    try {
      const res = await window.apiFetch('update_status.php', {
        method: 'POST',
        body: JSON.stringify({ application_id: currentAppId, action: 'reject', remarks: reason })
      });
      if (res.success) {
        hideModal('rejectApprovalModal');
        showToast('❌', 'Application rejected.');
        loadQueue();
        document.getElementById('approvalPanel').style.display = 'none';
      } else {
        alert('Error: ' + (res.error || 'Unknown error'));
      }
    } catch (e) {
      alert('Network error: ' + e.message);
    }
  }

  document.addEventListener('DOMContentLoaded', loadQueue);
</script>
</body>
</html>

