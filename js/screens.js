// =============================================
// CONTRACTOR MODULE – SCREEN RENDERERS
// =============================================
const dashboards = {}; // 🔥 ADD THIS AT TOP

function normalizeWorkflowStatus(status) {
  if (!status) return 'draft';
  const map = {
    draft: 'draft',
    submitted: 'submitted',
    verified: 'verified',
    approved: 'approved',
    enrolment_done: 'enrolment_done',
    training_done: 'training_done',
    gatepass_requested: 'gatepass_requested',
    gatepass_verified: 'gatepass_verified',
    temporary_pass_issued: 'temporary_pass_issued',
    acc_generated: 'acc_generated',
    permanent_pass_issued: 'permanent_pass_issued'
  };
  return map[status] || status;
}

// ---- DASHBOARD (dynamic per role) ----
async function renderDashboard(role) {
  const el = document.getElementById('screen-dashboard');
  const contractor = window.APP_DATA?.sapContractor || {};
  
  // FIXED: Use safe() for all values - CRITICAL FOR NO UNDEFINED
  const contractorName = safe(contractor.name || contractor.contractor_name) || 'Contractor';
  const contractorCode = safe(contractor.code || contractor.contractor_code) || 'N/A';
  const contractorType = safe(contractor.type) || 'Contractor';

  // Role fallback using safe helper
  if (!role) {
    role = localStorage.getItem('userRole') || sessionStorage.getItem('userRole') || 'contractor';
  }

  // FIXED: Explicit pendingApplications fallback - use safe normalize
  const pendingApplications = normalizeArray(window.APP_DATA?.pendingApplications) || [];

  // Load dynamic stats from DB
  const stats = window.dashboardStats || await loadDashboardStats();

  // Get current workflow status for the application
  const currentAppId = getAppId();
  const currentStatus = normalizeWorkflowStatus(currentAppId ? await getApplicationStatus(currentAppId) : 'submitted');

  const dashboards = {
    contractor: `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><span>Home</span><span class="sep">›</span><span>Dashboard</span></div>
          <div class="page-title">Welcome, ${contractorName}</div>
          <div class="page-subtitle">Contractor Code: ${contractorCode} &nbsp;|&nbsp; ${contractorType}</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-secondary btn-sm" onclick="navigate('sap-details')"><i class="fas fa-database"></i> View SAP Details</button>
          <button class="btn btn-primary btn-sm" onclick="showNotifPreview()"><i class="fas fa-bell"></i> Notifications</button>
        </div>
      </div>

      <!-- Process Flow (Dynamic based on workflow_status) -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-title"><i class="fas fa-route"></i> Application Process Flow - Status: <span class="badge badge-${currentStatus === 'draft' ? 'info' : currentStatus === 'submitted' ? 'pending' : currentStatus === 'verified' ? 'warning' : currentStatus === 'approved' ? 'success' : currentStatus === 'enrolment_done' ? 'info' : currentStatus === 'training_done' ? 'success' : currentStatus === 'gatepass_requested' ? 'pending' : currentStatus === 'gatepass_verified' ? 'warning' : currentStatus === 'temporary_pass_issued' ? 'info' : currentStatus === 'acc_generated' ? 'success' : currentStatus === 'permanent_pass_issued' ? 'success' : 'info'}">${currentStatus.replace('_', ' ')}</span></div>
        <div class="process-flow">
          <div class="flow-step"><div class="flow-icon ${['submitted', 'verified', 'approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'draft' ? 'active-flow' : ''}">${['submitted', 'verified', 'approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'draft' ? '🔄' : '⏳'}</div><div class="flow-label">Contractor<br>Registration</div></div>
          <div class="flow-step"><div class="flow-icon ${['verified', 'approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'submitted' ? 'active-flow' : ''}">${['verified', 'approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'submitted' ? '🔄' : '⏳'}</div><div class="flow-label">Welfare<br>Verification</div></div>
          <div class="flow-step"><div class="flow-icon ${['approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'verified' ? 'active-flow' : ''}">${['approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'verified' ? '🔄' : '⏳'}</div><div class="flow-label">Welfare<br>Approval</div></div>
          <div class="flow-step"><div class="flow-icon ${['enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'approved' ? 'active-flow' : ''}">${['enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'approved' ? '🔄' : '⏳'}</div><div class="flow-label">Workmen<br>Enrolment</div></div>
          <div class="flow-step"><div class="flow-icon ${['training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'enrolment_done' ? 'active-flow' : ''}">${['training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'enrolment_done' ? '🔄' : '⏳'}</div><div class="flow-label">Safety<br>Training</div></div>
          <div class="flow-step"><div class="flow-icon ${['gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'training_done' ? 'active-flow' : ''}">${['gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'training_done' ? '🔄' : '⏳'}</div><div class="flow-label">Gate Pass<br>Request</div></div>
          <div class="flow-step"><div class="flow-icon ${['gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'gatepass_requested' ? 'active-flow' : ''}">${['gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'gatepass_requested' ? '🔄' : '⏳'}</div><div class="flow-label">Document<br>Verification</div></div>
          <div class="flow-step"><div class="flow-icon ${['temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'gatepass_verified' ? 'active-flow' : ''}">${['temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'gatepass_verified' ? '🔄' : '⏳'}</div><div class="flow-label">Temporary<br>Pass</div></div>
          <div class="flow-step"><div class="flow-icon ${['acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? 'done' : currentStatus === 'temporary_pass_issued' ? 'active-flow' : ''}">${['acc_generated', 'permanent_pass_issued'].includes(currentStatus) ? '✅' : currentStatus === 'temporary_pass_issued' ? '🔄' : '⏳'}</div><div class="flow-label">ACC Card<br>Generation</div></div>
          <div class="flow-step"><div class="flow-icon ${currentStatus === 'permanent_pass_issued' ? 'done' : currentStatus === 'acc_generated' ? 'active-flow' : ''}">${currentStatus === 'permanent_pass_issued' ? '✅' : currentStatus === 'acc_generated' ? '🔄' : '⏳'}</div><div class="flow-label">Permanent<br>Gate Pass</div></div>
        </div>
      </div>

      <!-- Stats (Dynamic from DB) -->
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-file-alt"></i></div><div><div class="stat-value">${stats.totalApplications || 0}</div><div class="stat-label">Total Applications</div></div></div>
        <div class="stat-card"><div class="stat-icon amber"><div class="fas fa-clock"></i></div><div><div class="stat-value">${stats.pending || 0}</div><div class="stat-label">Pending</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div><div class="stat-value">${stats.approved || 0}</div><div class="stat-label">Approved</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times"></i></div><div><div class="stat-value">${stats.rejected || 0}</div><div class="stat-label">Rejected</div></div></div>
      </div>

      <div class="grid-2">
        <!-- Application Status -->
        <div class="card">
          <div class="card-title"><i class="fas fa-tasks"></i> Application Status</div>
          <div style="display:flex;flex-direction:column;gap:10px;">
            ${[
              { label: 'Contractor Registration Submission', status: 'approved', action: null },
              { label: 'Contractor Info Submission', status: 'submitted', action: null },
              { label: 'Welfare User Verification', status: 'verified', action: null },
              { label: 'Welfare Authority Approval', status: 'approved', action: null },
              { label: 'Application Fee Payment', status: 'completed', action: 'payment' },
              { label: 'Workmen Enrolment', status: 'processing', action: 'enrolment' },
              { label: 'Safety Training', status: 'processing', action: 'safety-training' },
              { label: 'Gate Pass Request', status: 'submitted', action: 'gatepass-request' },
            ].map(item => `
              <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--bg);border-radius:8px;">
                <div style="font-size:13px;font-weight:600;">${item.label}</div>
                <div style="display:flex;align-items:center;gap:8px;">
                  <span class="badge badge-${item.status}">${item.status.replace('_', ' ')}</span>
                  ${item.action ? `<button class="btn btn-light btn-sm" onclick="navigate('${item.action}')"><i class="fas fa-eye"></i></button>` : ''}
                </div>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
          <div class="card-title"><i class="fas fa-history"></i> Recent Activity</div>
          <ul class="timeline">
            <li class="timeline-item">
              <div class="timeline-dot info"><i class="fas fa-info-circle"></i></div>
              <div class="timeline-body">
                <div class="timeline-title">System Log</div>
                <div class="timeline-time">${new Date().toLocaleString()}</div>
                <div class="timeline-desc">Dashboard initialized. Real-time updates active.</div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button class="btn btn-primary" onclick="navigate('enrolment')"><i class="fas fa-user-plus"></i> Enrol Workman</button>
          <button class="btn btn-secondary" onclick="navigate('temp-id')"><i class="fas fa-id-card"></i> View Temp IDs</button>
          <button class="btn btn-info" onclick="navigate('safety-training')"><i class="fas fa-hard-hat"></i> Request Training</button>
          <button class="btn btn-success" onclick="navigate('gatepass-request', window.currentAppId || 'CMS-2024-001')"><i class="fas fa-door-open"></i> Request Gate Pass</button>
          <button class="btn btn-warning" onclick="showNotifPreview()"><i class="fas fa-paper-plane"></i> Send Notification</button>
        </div>
      </div>
    `,

    welfare: `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><span>Home</span><span class="sep">›</span><span>Welfare Dashboard</span></div>
          <div class="page-title">Welfare Officer Dashboard</div>
          <div class="page-subtitle">Applications categorized by workflow status</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-primary btn-sm" onclick="loadWelfareDashboard()"><i class="fas fa-sync"></i> Refresh Data</button>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-file-alt"></i></div><div><div class="stat-value" id="stat-total">0</div><div class="stat-label">Total Applications</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-clock"></i></div><div><div class="stat-value" id="stat-pending">0</div><div class="stat-label">Pending Approval</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div><div class="stat-value" id="stat-approved">0</div><div class="stat-label">Approved</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times"></i></div><div><div class="stat-value" id="stat-rejected">0</div><div class="stat-label">Rejected</div></div></div>
      </div>
        <div class="card">
          <div class="card-title"><i class="fas fa-list"></i> Application Queue</div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>App ID</th><th>Contractor</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="welfareTableBody">
                <tr><td colspan="4" style="text-align:center;">Loading data...</td></tr>
              </tbody>
            </table>
          </div>
        </div>`,


    authority: `
      <div class="page-header">
        <div>
          <div class="page-title">Welfare Authority Dashboard</div>
          <div class="page-subtitle">Executing Officer – Approval Management</div>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-file-signature"></i></div><div><div class="stat-value">8</div><div class="stat-label">Awaiting Decision</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-double"></i></div><div><div class="stat-value">24</div><div class="stat-label">Approved This Month</div></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div><div><div class="stat-value">5</div><div class="stat-label">Training Approvals</div></div></div>
        <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-door-open"></i></div><div><div class="stat-value">15</div><div class="stat-label">Gate Passes Issued</div></div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-title"><i class="fas fa-exclamation-circle"></i> High Priority Queue</div>
          ${(window.APP_DATA?.pendingApplications || []).filter(a=>a.priority==='high').map(a=>`
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--bg);border-radius:8px;margin-bottom:8px;">
              <div><strong>${a.id}</strong><br><small>${a.contractor}</small></div>
              <button class="btn btn-primary btn-sm" onclick="navigate('welfare-approval')">Review</button>
            </div>`).join('')}
        </div>
        <div class="card">
          <div class="card-title"><i class="fas fa-chart-line"></i> Monthly Overview</div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            ${[['Total Applications','32'],['Approved','24'],['Rejected','3'],['Pending','5'],['Gate Passes Issued','15'],['Training Completed','18']].map(([l,v])=>`
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);">
                <span style="font-size:13px;color:var(--text-mid);">${l}</span>
                <strong>${v}</strong>
              </div>`).join('')}
          </div>
        </div>
      </div>`,

    safety: `
      <div class="page-header">
        <div>
          <div class="page-title">Safety Officer Dashboard</div>
          <div class="page-subtitle">Safety Training Management & Gate Pass Verification</div>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-hard-hat"></i></div><div><div class="stat-value">3</div><div class="stat-label">Training Sessions</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-user-check"></i></div><div><div class="stat-value">48</div><div class="stat-label">Qualified Persons</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-user-times"></i></div><div><div class="stat-value">5</div><div class="stat-label">Failed / Retake</div></div></div>
        <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-clock"></i></div><div><div class="stat-value">18</div><div class="stat-label">Upcoming Training</div></div></div>
      </div>
      <div class="card">
        <div class="card-title"><i class="fas fa-calendar-alt"></i> Upcoming Training Sessions</div>
      ${(window.APP_DATA?.trainingSessions || []).map(t=>`
          <div style="display:flex;align-items:center;gap:16px;padding:12px;background:var(--bg);border-radius:10px;margin-bottom:10px;flex-wrap:wrap;">
            <div style="background:var(--primary);color:#fff;border-radius:8px;padding:10px 14px;text-align:center;min-width:60px;">
              <div style="font-size:18px;font-weight:800;">${t.date.split(' ')[0]}</div>
              <div style="font-size:10px;">${t.date.split(' ').slice(1).join(' ')}</div>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700;">${t.venue}</div>
              <div style="font-size:12px;color:var(--text-mid);">${t.trainer} &nbsp;|&nbsp; ${t.time}</div>
              <div style="font-size:12px;margin-top:4px;">Capacity: ${t.enrolled}/${t.capacity} enrolled</div>
            </div>
            <span class="badge badge-${t.status === 'upcoming' ? 'info' : 'completed'}">${t.status}</span>
            <button class="btn btn-primary btn-sm" onclick="navigate('safety-training')">Manage</button>
          </div>`).join('')}
      </div>`,

    pass: `
      <div class="page-header">
        <div>
          <div class="page-title">Pass Issuing Officer Dashboard</div>
          <div class="page-subtitle">Gate Pass Verification & Issuance</div>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-file-alt"></i></div><div><div class="stat-value">5</div><div class="stat-label">Pending Verification</div></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-clock"></i></div><div><div class="stat-value">2</div><div class="stat-label">Awaiting Welfare Approval</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-id-card"></i></div><div><div class="stat-value">18</div><div class="stat-label">Passes Issued This Month</div></div></div>
        <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-check-double"></i></div><div><div class="stat-value">3</div><div class="stat-label">ACC Approvals Pending</div></div></div>
      </div>
      <div class="card">
        <div class="card-title"><i class="fas fa-list"></i> Gate Pass Queue</div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Request ID</th><th>Contractor</th><th>Persons</th><th>Type</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              ${(window.APP_DATA?.gatePassApplications || []).map(g=>`
                <tr>
                  <td><strong>${g.id}</strong></td>
                  <td>${g.contractor}</td>
                  <td style="text-align:center;">${g.persons}</td>
                  <td>${g.type}</td>
                  <td>${g.submitted}</td>
                  <td><span class="badge badge-${g.status==='under_verification'?'processing':g.status==='pending_welfare'?'pending':'approved'}">${g.status.replace(/_/g,' ')}</span></td>
                  <td class="actions">
                    <button class="btn btn-info btn-sm" onclick="navigate('pass-officer')"><i class="fas fa-eye"></i> Review</button>
                  </td>
                </tr>`).join('')}
            </tbody>
          </table>
        </div>
      </div>`,

    safety: `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><span>Home</span><span class="sep">›</span><span>Safety Dashboard</span></div>
          <div class="page-title">Safety Officer Dashboard</div>
          <div class="page-subtitle">Training and Safety Management</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-primary btn-sm" onclick="loadSafetyDashboard()"><i class="fas fa-sync"></i> Refresh Data</button>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-graduation-cap"></i></div><div><div class="stat-value" id="safety-stat-total">0</div><div class="stat-label">Training Sessions</div></div></div>
        <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-clock"></i></div><div><div class="stat-value" id="safety-stat-pending">0</div><div class="stat-label">Pending Training</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div><div class="stat-value" id="safety-stat-completed">0</div><div class="stat-label">Completed</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times"></i></div><div><div class="stat-value" id="safety-stat-failed">0</div><div class="stat-label">Failed</div></div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-title"><i class="fas fa-list"></i> Training Queue</div>
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>Application ID</th><th>Contractor</th><th>Workmen</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="safetyTableBody">
                <tr><td colspan="5" style="text-align:center;">Loading training data...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card">
          <div class="card-title"><i class="fas fa-calendar"></i> Upcoming Sessions</div>
          <ul class="timeline">
            <li class="timeline-item">
              <div class="timeline-dot info"><i class="fas fa-info-circle"></i></div>
              <div class="timeline-body">
                <div class="timeline-title">Safety Training Session</div>
                <div class="timeline-time">Tomorrow, 10:00 AM</div>
                <div class="timeline-desc">Basic safety procedures for new workmen</div>
              </div>
            </li>
          </ul>
        </div>
      </div>`,

    pass_officer: `
      <div class="page-header">
        <div>
          <div class="page-title">Pass Officer Dashboard</div>
          <div class="page-subtitle">Gate Pass Approval and Management</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-primary btn-sm" onclick="loadPassOfficerDashboard()"><i class="fas fa-sync"></i> Refresh Data</button>
        </div>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-file-signature"></i></div><div><div class="stat-value" id="pass-stat-pending">0</div><div class="stat-label">Pending Approval</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-double"></i></div><div><div class="stat-value" id="pass-stat-approved">0</div><div class="stat-label">Approved This Month</div></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-id-card"></i></div><div><div class="stat-value" id="pass-stat-temp">0</div><div class="stat-label">Temp Passes Issued</div></div></div>
        <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-door-open"></i></div><div><div class="stat-value" id="pass-stat-permanent">0</div><div class="stat-label">Permanent Passes</div></div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-title"><i class="fas fa-exclamation-circle"></i> High Priority Queue</div>
          <div id="passOfficerQueue">
            <div style="text-align:center;padding:20px;">Loading gate pass requests...</div>
          </div>
        </div>
        <div class="card">
          <div class="card-title"><i class="fas fa-chart-line"></i> Monthly Overview</div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            ${[['Total Requests','32'],['Approved','24'],['Rejected','3'],['Pending','5'],['Temp Passes','15'],['Permanent Passes','18']].map(([l,v])=>`
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);">
                <span style="font-size:13px;color:var(--text-mid);">${l}</span>
                <span style="font-weight:600;">${v}</span>
              </div>`).join('')}
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-title"><i class="fas fa-list"></i> Gate Pass Queue</div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Request ID</th><th>Contractor</th><th>Persons</th><th>Type</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="passOfficerTableBody">
              <tr><td colspan="7" style="text-align:center;">Loading data...</td></tr>
            </tbody>
          </table>
        </div>
      </div>`
  };

  // Role-based dashboard rendering
  if (['welfare', 'welfare_user', 'welfare_authority', 'authority'].includes(role)) {
    el.innerHTML = dashboards.welfare || '<div class="alert alert-info">Welfare dashboard loading...</div>';
    setTimeout(loadWelfareDashboard, 100);
    return;
  }
  if (role === 'safety') {
    el.innerHTML = dashboards.safety || '<div class="alert alert-info">Safety dashboard loading...</div>';
    setTimeout(loadSafetyDashboard, 100);
    return;
  }
  if (role === 'pass_officer') {
    el.innerHTML = dashboards.pass_officer || '<div class="alert alert-info">Pass Officer dashboard loading...</div>';
    setTimeout(loadPassOfficerDashboard, 100);
    return;
  }
  el.innerHTML = dashboards[role] || dashboards.contractor;
}

// 🔥 FIX: Welfare Dashboard Rendering
async function loadWelfareDashboard() {
    try {
        const res = await fetch('api/welfare/get_welfare_applications.php');
        const json = await res.json();

        console.log("Welfare Dashboard Data:", json);

        if (!json.success) {
            console.error("Failed to load data:", json.message);
            return;
        }

        const apps = normalizeArray(json.data);
        const counts = json.data.counts || {};

        // Update Stats
        if (document.getElementById('stat-total')) document.getElementById('stat-total').innerText = counts.total || 0;
        if (document.getElementById('stat-pending')) document.getElementById('stat-pending').innerText = counts.pending || 0;
        if (document.getElementById('stat-approved')) document.getElementById('stat-approved').innerText = counts.approved || 0;
        if (document.getElementById('stat-rejected')) document.getElementById('stat-rejected').innerText = counts.rejected || 0;

        renderWelfareTable(apps);
    } catch (err) {
        console.error("loadWelfareDashboard Error:", err);
    }
}

// 🔥 FIX: Table Render
function renderWelfareTable(data) {
    const tbody = document.getElementById("welfareTableBody");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No applications found</td></tr>';
        return;
    }

    data.forEach(item => {
        const appId = item.application_id || item.id;
        const status = normalizeWorkflowStatus(item.status || item.workflow_status || 'submitted');
        tbody.innerHTML += `
        <tr>
            <td><strong>${appId}</strong></td>
            <td>${item.contractor_name || 'N/A'}</td>
            <td><span class="badge badge-${status === 'approved' ? 'approved' : status === 'rejected' ? 'rejected' : 'pending'}">${status}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-success btn-sm" onclick="approve('${appId}')"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-danger btn-sm" onclick="reject('${appId}')"><i class="fas fa-times"></i> Reject</button>
                    <button class="btn btn-info btn-sm" onclick="navigate('welfare-verify', {applicationId: '${appId}'})"><i class="fas fa-eye"></i> View</button>
                </div>
            </td>
        </tr>`;
    });
}

// 🔥 Approve
function approve(id) {
    if (!confirm("Are you sure you want to approve this application?")) return;
    
    fetch('api/workflow_action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ application_id: id, action: 'approve' })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success){
            showToast("✅", "Application Approved");
            loadWelfareDashboard();
        } else {
            showToast("❌", res.message || "Approval failed");
        }
    })
    .catch(err => {
        console.error("Approve Error:", err);
        showToast("❌", "Network error");
    });
}

// 🔥 Reject
function reject(id) {
    const reason = prompt("Rejection reason:");
    if (!reason) return;

    fetch('api/workflow_action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ application_id: id, action: 'reject', remarks: reason })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success){
            showToast("❌", "Application Rejected");
            loadWelfareDashboard();
        } else {
            showToast("❌", res.message || "Rejection failed");
        }
    })
    .catch(err => {
        console.error("Reject Error:", err);
        showToast("❌", "Network error");
    });
}

window.loadWelfareDashboard = loadWelfareDashboard;
window.renderWelfareTable = renderWelfareTable;
window.approve = approve;
window.reject = reject;

// Safety Dashboard Functions
async function loadSafetyDashboard() {
    try {
        const res = await fetch('api/get_training_sessions.php');
        const json = await res.json();

        if (!json.success) {
            console.error("Failed to load safety data:", json.message);
            return;
        }

        const sessions = normalizeArray(json.data);
        const counts = json.counts || { total: 0, pending: 0, completed: 0, failed: 0 };

        // Update Stats
        if (document.getElementById('safety-stat-total')) document.getElementById('safety-stat-total').innerText = counts.total || 0;
        if (document.getElementById('safety-stat-pending')) document.getElementById('safety-stat-pending').innerText = counts.pending || 0;
        if (document.getElementById('safety-stat-completed')) document.getElementById('safety-stat-completed').innerText = counts.completed || 0;
        if (document.getElementById('safety-stat-failed')) document.getElementById('safety-stat-failed').innerText = counts.failed || 0;

        renderSafetyTable(sessions);
    } catch (err) {
        console.error("loadSafetyDashboard Error:", err);
    }
}

function renderSafetyTable(data) {
    const tbody = document.getElementById("safetyTableBody");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No training sessions found</td></tr>';
        return;
    }

    data.forEach(item => {
        const status = item.status || 'pending';
        tbody.innerHTML += `
        <tr>
            <td><strong>${item.application_id || 'N/A'}</strong></td>
            <td>${item.contractor_name || 'N/A'}</td>
            <td>${item.enrolled_count || 0} workmen</td>
            <td><span class="badge badge-${status === 'completed' ? 'approved' : status === 'scheduled' ? 'pending' : 'info'}">${status}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-info btn-sm" onclick="navigate('training-session', {sessionId: '${item.id}'})"><i class="fas fa-eye"></i> View</button>
                    <button class="btn btn-success btn-sm" onclick="conductTraining('${item.id}')"><i class="fas fa-play"></i> Conduct</button>
                </div>
            </td>
        </tr>`;
    });
}

// Pass Officer Dashboard Functions
async function loadPassOfficerDashboard() {
    try {
        const res = await fetch('api/get_gate_pass_requests.php');
        const json = await res.json();

        if (!json.success) {
            console.error("Failed to load pass officer data:", json.message);
            return;
        }

        const requests = normalizeArray(json.data);
        const counts = json.counts || { pending: 0, approved: 0, temp: 0, permanent: 0 };

        // Update Stats
        if (document.getElementById('pass-stat-pending')) document.getElementById('pass-stat-pending').innerText = counts.pending || 0;
        if (document.getElementById('pass-stat-approved')) document.getElementById('pass-stat-approved').innerText = counts.approved || 0;
        if (document.getElementById('pass-stat-temp')) document.getElementById('pass-stat-temp').innerText = counts.temp || 0;
        if (document.getElementById('pass-stat-permanent')) document.getElementById('pass-stat-permanent').innerText = counts.permanent || 0;

        renderPassOfficerTable(requests);
    } catch (err) {
        console.error("loadPassOfficerDashboard Error:", err);
    }
}

function renderPassOfficerTable(data) {
    const tbody = document.getElementById("passOfficerTableBody");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No gate pass requests found</td></tr>';
        return;
    }

    data.forEach(item => {
        const status = item.status || 'pending';
        tbody.innerHTML += `
        <tr>
            <td><strong>${item.request_no || item.id}</strong></td>
            <td>${item.contractor_name || 'N/A'}</td>
            <td>${item.worker_count || 0}</td>
            <td>${item.request_type || 'Gate Pass'}</td>
            <td>${item.created_at || 'N/A'}</td>
            <td><span class="badge badge-${status === 'approved' ? 'approved' : status === 'rejected' ? 'rejected' : 'pending'}">${status}</span></td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-info btn-sm" onclick="navigate('gatepass-review', {requestId: '${item.id}'})"><i class="fas fa-eye"></i> Review</button>
                    <button class="btn btn-success btn-sm" onclick="approveGatePass('${item.id}')"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-danger btn-sm" onclick="rejectGatePass('${item.id}')"><i class="fas fa-times"></i> Reject</button>
                </div>
            </td>
        </tr>`;
    });
}

function conductTraining(sessionId) {
    // Navigate to training conduct page
    navigate('conduct-training', {sessionId: sessionId});
}

function approveGatePass(requestId) {
    if (!confirm("Are you sure you want to approve this gate pass request?")) return;
    
    fetch('api/approve_gate_pass.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ request_id: requestId, action: 'approve' })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success){
            showToast("✅", "Gate Pass Approved");
            loadPassOfficerDashboard();
        } else {
            showToast("❌", res.message || "Approval failed");
        }
    })
    .catch(err => {
        console.error("Approve Gate Pass Error:", err);
        showToast("❌", "Network error");
    });
}

function rejectGatePass(requestId) {
    const reason = prompt("Rejection reason:");
    if (!reason) return;

    fetch('api/reject_gate_pass.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ request_id: requestId, action: 'reject', remarks: reason })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success){
            showToast("❌", "Gate Pass Rejected");
            loadPassOfficerDashboard();
        } else {
            showToast("❌", res.message || "Rejection failed");
        }
    })
    .catch(err => {
        console.error("Reject Gate Pass Error:", err);
        showToast("❌", "Network error");
    });
}

window.loadSafetyDashboard = loadSafetyDashboard;
window.renderSafetyTable = renderSafetyTable;
window.loadPassOfficerDashboard = loadPassOfficerDashboard;
window.renderPassOfficerTable = renderPassOfficerTable;
window.conductTraining = conductTraining;
window.approveGatePass = approveGatePass;
window.rejectGatePass = rejectGatePass;

// ---- SAP CONTRACTOR DETAILS ----
async function renderSAPDetails() {
  // Load data from API first
  const appId = getAppId() || window.currentAppId;
  let d = window.APP_DATA?.sapContractor || {};
  
  // Only fetch if no cached data or different appId
  if (!d.code || d.code === 'N/A' || d.code === undefined) {
    try {
      const sapData = await loadSAPContractor(appId);
      d = sapData;
      window.APP_DATA = window.APP_DATA || {};
      window.APP_DATA.sapContractor = d;
    } catch (e) {
      console.warn('[renderSAPDetails] Using fallback:', e);
      d = getSafeSAPData();
    }
  }
  
  // Use safe() fallback for all values
  const s = window.safe || ((v) => (v === null || v === undefined || v === '') ? 'N/A' : v);
  document.getElementById('screen-sap-details').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>SAP Contractor Details</span></div>
        <div class="page-title">SAP Integrated Contractor Details</div>
        <div class="page-subtitle">Data fetched from SAP system &nbsp;|&nbsp; Last Sync: ${d.sapSync}</div>
      </div>
      <div class="btn-group">
        <button class="btn btn-secondary btn-sm"><i class="fas fa-sync"></i> Refresh from SAP</button>
        <button class="btn btn-primary btn-sm" onclick="navigate('annexure2a')"><i class="fas fa-file-alt"></i> Submit Contractor Registration</button>
      </div>
    </div>

    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      <div><strong>SAP Integration Active</strong> – Contractor data is successfully fetched from SAP HR & Materials Management modules. Data is read-only and auto-populated in forms.</div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-building"></i> Contractor Basic Information</div>
      <div class="info-grid info-grid-3">
        ${[
          ['Contractor Name', d.name], ['Contractor Code', d.code], ['Contractor Type', d.type],
          ['PAN Number', d.pan], ['GSTIN', d.gstin], ['Registration No.', d.regNo],
          ['Contact Email', d.email], ['Contact Phone', d.phone], ['Status', `<span class="badge badge-active">${d.status}</span>`],
        ].map(([l,v]) => `<div class="info-row"><div class="info-label">${l}</div><div class="info-value">${v}</div></div>`).join('')}
      </div>
    </div>

    <div class="grid-2">
      <div class="form-section">
        <div class="form-section-title"><i class="fas fa-file-contract"></i> Work Order Details</div>
        <div class="info-grid">
          ${[
            ['Work Order No.', d.workOrder], ['WO Date', d.workOrderDate],
            ['Project Name', d.project], ['Site Location', d.location],
            ['Contract Value', d.contractValue], ['Start Date', d.startDate],
            ['End Date', d.endDate], ['License No.', d.licenseNo],
            ['License Validity', d.licenseValidity], ['', ''],
          ].map(([l,v]) => l ? `<div class="info-row"><div class="info-label">${l}</div><div class="info-value">${v}</div></div>` : '').join('')}
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title"><i class="fas fa-university"></i> Compliance & Bank Details</div>
        <div class="info-grid">
          ${[
            ['PF Account No.', d.pf], ['ESIC No.', d.esic],
            ['Labour License No.', d.labourLicense], ['Safety Officer', d.safetyOfficer],
            ['Bank Name', d.bankName], ['Account No.', d.bankAccount],
            ['IFSC Code', d.ifsc], ['', ''],
          ].map(([l,v]) => l ? `<div class="info-row"><div class="info-label">${l}</div><div class="info-value">${v}</div></div>` : '').join('')}
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Registered Address</div>
      <div class="info-row"><div class="info-label">Address</div><div class="info-value">${d.address}</div></div>
    </div>

    <div class="btn-group" style="margin-top:8px;">
      <button class="btn btn-primary" onclick="navigate('annexure2a')"><i class="fas fa-arrow-right"></i> Proceed to Contractor Registration</button>
      <!-- <button class="btn btn-secondary" onclick="navigate('annexure3a')"><i class="fas fa-file-contract"></i> Fill Contractor Info</button> -->
    </div>
  `;
}

// ---- Contractor Registration FORM ----
function renderAnnexure2A() {
  document.getElementById('screen-annexure2a').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Contractor Registration</span></div>
        <div class="page-title">Contractor Registration – Contractor Registration Form</div>
        <div class="page-subtitle">Submission of additional contractor details for welfare registration</div>
      </div>
      <span class="badge badge-approved" style="font-size:13px;padding:6px 14px;"><i class="fas fa-check-circle"></i> Submitted & Approved</span>
    </div>

    <!-- Stepper -->
    <div class="stepper">
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Basic Info</div></div>
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Work Details</div></div>
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Compliance</div></div>
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Documents</div></div>
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Declaration</div></div>
    </div>

    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      <div><strong>Contractor Registration Approved</strong> – Application ID: CMS-2024-001 was approved on 03 Apr 2026 by Welfare Authority. <a href="#" style="color:var(--success);text-decoration:underline;">Download Approved Copy</a></div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-user"></i> Section A – Contractor Identity (Auto-filled from SAP)</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Contractor Name</label><input class="form-control readonly" value="M/s Sharma Construction Ltd." readonly/></div>
        <div class="form-group"><label class="form-label">Contractor Code</label><input class="form-control readonly" value="CONT-2024-001" readonly/></div>
        <div class="form-group"><label class="form-label">PAN Number</label><input class="form-control readonly" value="ABCPS1234D" readonly/></div>
        <div class="form-group"><label class="form-label">GSTIN</label><input class="form-control readonly" value="07ABCPS1234D1Z5" readonly/></div>
        <div class="form-group"><label class="form-label">Registration No.</label><input class="form-control readonly" value="PWD/CC/2024/0892" readonly/></div>
        <div class="form-group"><label class="form-label">Contractor Type</label><input class="form-control readonly" value="Civil Contractor – Class A" readonly/></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-briefcase"></i> Section B – Work Order & Project Details</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Work Order Number</label><input class="form-control readonly" value="WO/2024/PWD/0423" readonly/></div>
        <div class="form-group"><label class="form-label">Work Order Date</label><input class="form-control readonly" value="15 Jan 2024" readonly/></div>
        <div class="form-group full"><label class="form-label">Project Description</label><input class="form-control readonly" value="Construction of NH-48 Flyover – Phase II" readonly/></div>
        <div class="form-group"><label class="form-label">Site Location</label><input class="form-control readonly" value="Sector 22, Gurugram, Haryana" readonly/></div>
        <div class="form-group"><label class="form-label">Contract Value (₹)</label><input class="form-control readonly" value="4,85,00,000" readonly/></div>
        <div class="form-group"><label class="form-label">Project Start Date</label><input class="form-control readonly" value="01 Feb 2024" readonly/></div>
        <div class="form-group"><label class="form-label">Project End Date</label><input class="form-control readonly" value="31 Jan 2026" readonly/></div>
        <div class="form-group"><label class="form-label">Estimated Workmen Strength</label><input class="form-control readonly" value="45" readonly/></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-shield-alt"></i> Section C – Labour Welfare & Compliance</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">PF Registration No.</label><input class="form-control readonly" value="DL/40523/ENF/EMP/0001" readonly/></div>
        <div class="form-group"><label class="form-label">ESIC Registration No.</label><input class="form-control readonly" value="41000429350" readonly/></div>
        <div class="form-group"><label class="form-label">Labour License No.</label><input class="form-control readonly" value="LL/HRY/2024/0112" readonly/></div>
        <div class="form-group"><label class="form-label">Labour License Expiry</label><input class="form-control readonly" value="31 Mar 2025" readonly/></div>
        <div class="form-group"><label class="form-label">Safety Officer Name</label><input class="form-control readonly" value="Mr. Rajiv Sharma" readonly/></div>
        <div class="form-group"><label class="form-label">Safety Officer Contact</label><input class="form-control readonly" value="+91 98101 77291" readonly/></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-paperclip"></i> Section D – Document Uploads</div>
      <div class="form-grid">
        ${[
          ['Work Order Copy','fas fa-check-circle','text-success','Uploaded & Verified'],
          ['PAN Card','fas fa-check-circle','text-success','Uploaded & Verified'],
          ['GSTIN Certificate','fas fa-check-circle','text-success','Uploaded & Verified'],
          ['Labour License','fas fa-exclamation-circle','text-warning','Expiring Soon – Renewal Required'],
          ['PF Registration Certificate','fas fa-check-circle','text-success','Uploaded & Verified'],
          ['ESIC Certificate','fas fa-check-circle','text-success','Uploaded & Verified'],
        ].map(([doc,icon,cls,status]) => `
          <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:var(--bg);border-radius:8px;">
            <div style="font-size:13px;font-weight:600;"><i class="fas fa-file-pdf" style="color:var(--danger);margin-right:6px;"></i>${doc}</div>
            <div style="display:flex;align-items:center;gap:8px;">
              <span style="font-size:12px;" class="${cls}"><i class="${icon}"></i> ${status}</span>
              <button class="btn btn-light btn-sm"><i class="fas fa-eye"></i></button>
            </div>
          </div>`).join('')}
      </div>
    </div>

    <div class="btn-group">
      <button class="btn btn-secondary" onclick="window.location='contractor/annexure.php'"><i class="fas fa-arrow-left"></i> Back</button>
      <!-- <button class="btn btn-primary" onclick="window.location='pages/annexure-3a.php'"><i class="fas fa-arrow-right"></i> Go to Contractor Info</button> -->
    </div>
  `;
}

// ---- ANNEXURE 3A FORM ----
function renderAnnexure3A() {
  document.getElementById('screen-annexure3a').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Contractor Info</span></div>
        <div class="page-title">Contractor Info – Supplementary Details Form</div>
        <div class="page-subtitle">Sub-contractor details, insurance, and additional compliance information</div>
      </div>
      <span class="badge badge-submitted" style="font-size:13px;padding:6px 14px;"><i class="fas fa-paper-plane"></i> Submitted – Under Review</span>
    </div>

    <div class="stepper">
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Sub-Contractor Info</div></div>
      <div class="step active"><div class="step-circle">2</div><div class="step-label">Insurance Details</div></div>
      <div class="step"><div class="step-circle">3</div><div class="step-label">Work Zones</div></div>
      <div class="step"><div class="step-circle">4</div><div class="step-label">Declaration</div></div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-network-wired"></i> Section A – Sub-Contractor Details</div>
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <div>Fill details of all sub-contractors engaged under this work order. Sub-contractors must also register separately.</div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label required">Sub-Contractor Name</label><input class="form-control" value="M/s Gupta Plumbing Works" /></div>
        <div class="form-group"><label class="form-label required">Sub-Contract Work Type</label><input class="form-control" value="Plumbing & Sanitation" /></div>
        <div class="form-group"><label class="form-label">Sub-Contract Value (₹)</label><input class="form-control" value="45,00,000" /></div>
        <div class="form-group"><label class="form-label">Registration No.</label><input class="form-control" value="SC/HRY/2024/012" /></div>
        <div class="form-group"><label class="form-label">Workmen Strength</label><input class="form-control" type="number" value="8" /></div>
        <div class="form-group"><label class="form-label">Contact Person</label><input class="form-control" value="Mr. Anil Gupta" /></div>
      </div>
      <button class="btn btn-secondary btn-sm" style="margin-top:10px;"><i class="fas fa-plus"></i> Add Sub-Contractor</button>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-shield-alt"></i> Section B – Insurance & Liability</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label required">Workmen Compensation Policy No.</label><input class="form-control" value="WCP/2024/INS/00892" /></div>
        <div class="form-group"><label class="form-label required">Insurance Provider</label><input class="form-control" value="National Insurance Company" /></div>
        <div class="form-group"><label class="form-label required">Policy Validity From</label><input class="form-control" type="date" value="2024-02-01" /></div>
        <div class="form-group"><label class="form-label required">Policy Validity To</label><input class="form-control" type="date" value="2025-01-31" /></div>
        <div class="form-group"><label class="form-label">Sum Insured (₹)</label><input class="form-control" value="1,00,00,000" /></div>
        <div class="form-group"><label class="form-label">Third Party Liability</label><select class="form-control"><option selected>Yes</option><option>No</option></select></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-map-marked-alt"></i> Section C – Work Zones & Access Areas</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label required">Primary Work Zone</label><input class="form-control" value="Zone A – NH-48 Km 12-18" /></div>
        <div class="form-group"><label class="form-label">Secondary Work Zone</label><input class="form-control" value="Zone B – Approach Road" /></div>
        <div class="form-group"><label class="form-label">Access Gate No.</label><input class="form-control" value="Gate No. 3 & 5" /></div>
        <div class="form-group"><label class="form-label">Working Hours</label><input class="form-control" value="06:00 AM – 10:00 PM" /></div>
        <div class="form-group full"><label class="form-label">Special Access Requirements</label><textarea class="form-control" rows="2">Night work permission required for pile foundation work (Zone A)</textarea></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-file-signature"></i> Section D – Declaration</div>
      <div style="background:var(--bg);border-radius:8px;padding:14px;font-size:13px;color:var(--text-mid);margin-bottom:14px;line-height:1.8;">
        I/We hereby declare that all the information furnished in this Contractor Info is true, correct and complete to the best of my/our knowledge and belief. I/We undertake to comply with all applicable labour laws, welfare measures, and safety regulations as prescribed by the Public Works Department and as per the terms of the work order.
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" id="decl3a" checked />
        <label for="decl3a" style="font-size:13px;cursor:pointer;">I agree to the above declaration and confirm all details are accurate.</label>
      </div>
    </div>

    <div class="btn-group">
      <button class="btn btn-secondary" onclick="window.location='contractor/annexure.php'"><i class="fas fa-arrow-left"></i> Back</button>
      <!-- <button class="btn btn-light" onclick="window.location='pages/annexure-3a.php'"><i class="fas fa-edit"></i> Edit Annexure 3A</button> -->
      <button class="btn btn-primary" onclick="window.location='welfare/verification.php'"><i class="fas fa-eye"></i> View Status</button>
    </div>
  `;
}
// ---- WELFARE AUTHORITY VERIFICATION (DYNAMIC) ----
let _welfareVerifyLoading = false;

async function loadWelfareVerification(params) {
  return safeLoad(async () => {
  const applicationId = getAppId(params);
  setAppId(applicationId);

  console.log('[loadWelfareVerification] appId=', applicationId);
  if (!applicationId) {
    console.warn('[loadWelfareVerification] No applicationId provided');
    return;
  }

  const parsed = await apiFetch(`get_application_details.php?id=${encodeURIComponent(applicationId)}`);
  console.log('API RESPONSE:', parsed);

  if (parsed.success && parsed.data) {
    window.APP_DATA = parsed.data;
    renderWelfareVerify({ applicationId });
  } else {
    const msg = parsed.error || 'Failed to load verification data';
    console.error('[loadWelfareVerification]', msg, parsed);
    const el = document.getElementById('screen-welfare-verify') || document.querySelector('#verifyPanel .card-body');
    if (el) {
      el.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${msg}</div>`;
    } else {
      showToast('❌', msg);
    }
  }
  });
}

// 🔥 FULL DYNAMIC RENDERWELFAREVERIFY() - Production Ready
function renderWelfareVerify(params = {}) {
  const appId = getAppId(params);
  console.log('[renderWelfareVerify] appId=', appId || '(none)');

  // Auto-load if data is missing and we have an appId
  if ((!window.APP_DATA || !window.APP_DATA.application) && appId) {
    loadWelfareVerification({ applicationId: appId });
    return;
  }

  const el = document.getElementById('screen-welfare-verify') || 
             (document.querySelector('#verifyPanel .card-body') || document.body);
  
  const data = window.APP_DATA || {};
  const application = data.application || {};
  const workers = data.workers || [];
  const checklist = [];
  const documents = [];
  const remarksHistory = application.remarks ? [{remark: application.remarks, created_at: application.updated_at || application.created_at, created_by: 'System', action_type: 'info'}] : [];
  
  const statusBadge = {
    'submitted': 'pending', 
    'verified': 'verified', 
    'welfare_approved': 'approved', 
    'acc_approved': 'approved', 
    'pass_generated': 'approved',
    'rejected': 'rejected'
  }[application.workflow_status] || 'pending';
  
  el.innerHTML = `
    <div class="page-header" style="margin-bottom:20px;">
      <div>
        <div class="breadcrumb">
          <a onclick="navigate('welfare-approval')">Approvals</a>
          <span class="sep">›</span>
          <span>Welfare Verification: <strong>${application.application_id || application.ref_id || '-'}</strong></span>
        </div>
        <div class="page-title">Verification Dashboard</div>
        <div class="page-subtitle">
          ${application.contractor_name || 'Contractor'} · ${application.contractor_id || 'N/A'}
          <span class="badge badge-${statusBadge}" style="margin-left:12px;font-size:12px;padding:6px 12px;">
            <i class="fas fa-circle-${statusBadge === 'pending' ? 'dot' : statusBadge}"></i>
            ${application.workflow_status?.replace('_',' ') || 'Unknown'}
          </span>
        </div>
      </div>
      <div class="btn-group">
        <button class="btn btn-light btn-sm welfare-refresh-btn" data-id="${application.application_id || appId || ''}">
          <i class="fas fa-sync"></i> Refresh Data
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div><div class="stat-value">${workers.length}</div><div class="stat-label">Total Workmen</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-file-pdf"></i></div>
        <div><div class="stat-value">${documents.filter(d => d.status === 'verified').length}</div><div class="stat-label">Verified Docs</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-comments"></i></div>
        <div><div class="stat-value">${remarksHistory.length}</div><div class="stat-label">Remarks History</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon ${statusBadge}">
          <i class="fas fa-${statusBadge === 'approved' ? 'check-double' : statusBadge === 'rejected' ? 'times' : 'clock'}"></i>
        </div>
        <div><div class="stat-value">${application.workflow_status?.toUpperCase()}</div><div class="stat-label">Current Status</div></div>
      </div>
    </div>

    <div class="grid-2">
      <!-- Contractor Info Card -->
      <div class="card">
        <div class="card-title"><i class="fas fa-building"></i> Contractor Details (SAP)</div>
        <div class="info-grid">
          ${[
            ['Name', application.contractor_name || 'N/A'],
            ['Code', application.contractor_id || 'N/A'],
            ['Project', application.project_name || 'N/A'],
            ['Work Order', application.ref_id || 'N/A'],
            ['EPF Code', application.epf_code || 'N/A'],
            ['Status', `<span class="badge badge-active">${application.status || 'Active'}</span>`]
          ].map(([label, value]) => `<div class="info-row"><span class="info-label">${label}:</span><span class="info-value">${value}</span></div>`).join('')}
        </div>
      </div>

      <!-- Verification Summary -->
      <div class="card">
        <div class="card-title"><i class="fas fa-clipboard-check"></i> Current Verification</div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="badge badge-${statusBadge}">${application.workflow_status?.replace('_', ' ') || 'Pending'}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Submitted On:</span>
            <span>${application.submitted_at ? new Date(application.submitted_at).toLocaleString() : '-'}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Last Updated:</span>
            <span>${application.updated_at ? new Date(application.updated_at).toLocaleString() : '-'}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Current Remarks:</span>
            <span style="font-style:italic;color:var(--text-mid);">${application.remarks || 'No remarks yet'}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Dynamic Checklist -->
    <div class="card">
      <div class="card-title"><i class="fas fa-list-check"></i> Verification Checklist 
        <span class="badge badge-${checklist.length > 0 && checklist.filter(c => c.is_done).length === checklist.length ? 'success' : 'warning'}">
          ${checklist.filter(c => c.is_done).length}/${checklist.length}
        </span>
      </div>
      <div style="max-height:300px;overflow:auto;">
        ${checklist.map((item, idx) => `
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-bottom:1px solid var(--border);">
            <div style="flex:1;">
              <div style="font-weight:600;font-size:13px;">${item.item_name}</div>
              <div style="font-size:11px;color:var(--text-mid);">
                ${item.updated_by ? `By: ${item.updated_by}` : ''} 
                ${item.updated_at ? `| ${new Date(item.updated_at).toLocaleDateString()}` : ''}
              </div>
            </div>
            <label class="switch">
              <input type="checkbox" ${item.is_done ? 'checked' : ''} onchange="toggleChecklist('${item.id}', this.checked)">
              <span class="slider"></span>
            </label>
          </div>
        `).join('') || '<div style="padding:40px;text-align:center;color:var(--text-mid);"><i class="fas fa-inbox"></i><br>No checklist items</div>'}
      </div>
    </div>

    <!-- Workmen & Personnel Table -->
    <div class="card">
      <div class="card-title"><i class="fas fa-users"></i> Workmen & Personnel List</div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Name</th>
              <th>Role/Trade</th>
              <th>Aadhaar</th>
              <th>Training</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${[
              ...(data.supervisors || []).map(s => ({...s, typeLabel: 'Supervisor'})),
              ...(data.representatives || []).map(r => ({...r, typeLabel: 'Rep'})),
              ...(data.workmen || []).map(w => ({...w, typeLabel: 'Workman'}))
            ].map(p => `
              <tr>
                <td><span class="badge badge-info" style="font-size:10px;">${p.typeLabel}</span></td>
                <td><strong>${p.name || p.representative_name || p.supervisor_name || 'N/A'}</strong></td>
                <td>${p.role || p.designation || p.trade || '—'}</td>
                <td>${p.aadhar || p.aadhaar || '—'}</td>
                <td><span class="badge badge-${p.training_status === 'qualified' ? 'approved' : 'pending'}">${p.training_status || 'pending'}</span></td>
                <td><span class="badge badge-active">${p.status || 'active'}</span></td>
              </tr>
            `).join('') || '<tr><td colspan="6" style="text-align:center;padding:20px;">No personnel records found</td></tr>'}
          </tbody>
        </table>
      </div>
    </div>

    <!-- Documents Table (w/ doc_type filter) -->
    <div class="card">
      <div class="card-title"><i class="fas fa-file-pdf"></i> Documents 
        <span style="font-size:12px;">(${documents.length})</span>
        <select onchange="filterDocuments(this.value)" style="margin-left:12px;font-size:12px;">
          <option value="">All Types</option>
          <option value="mandatory">Mandatory (${documents.filter(d=>d.doc_type==='mandatory').length})</option>
          <option value="optional">Optional (${documents.filter(d=>d.doc_type==='optional').length})</option>
        </select>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Type</th><th>Uploaded</th><th>Valid Till</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="documentsTable">
            ${documents.map(doc => `
              <tr>
                <td><i class="fas fa-file-pdf text-danger"></i> ${doc.document_name}</td>
                <td><span class="badge badge-${doc.doc_type}">${doc.doc_type}</span></td>
                <td>${doc.uploaded_by} <br><small>${new Date(doc.upload_date).toLocaleDateString()}</small></td>
                <td>${doc.validity_date || '—'}</td>
                <td><span class="badge badge-${doc.status}">${doc.status}</span></td>
                <td>
                  ${doc.status === 'verified' ? 
                    '<span class="text-success"><i class="fas fa-check-circle"></i></span>' : 
                    `<button class="btn btn-sm btn-success verify-document-btn" data-doc-id="${doc.id}" data-id="${verification.application_id || appId || ''}">Verify</button>`
                  }
                </td>
              </tr>
            `).join('') || '<tr><td colspan="6" style="text-align:center;padding:20px;">No documents uploaded</td></tr>'}
          </tbody>
        </table>
      </div>
    </div>

    <!-- Remarks History Timeline -->
    <div class="card">
      <div class="card-title"><i class="fas fa-comments"></i> Remarks History (${remarksHistory.length})</div>
      <ul class="timeline">
        ${remarksHistory.map(r => `
          <li class="timeline-item">
            <div class="timeline-dot ${r.action_type === 'approval' ? 'success' : r.action_type === 'rejection' ? 'danger' : 'info'}">
              <i class="fas fa-${r.action_type === 'approval' ? 'check' : r.action_type === 'rejection' ? 'times' : 'comment'}"></i>
            </div>
            <div class="timeline-body">
              <div class="timeline-title">${r.remark}</div>
              <div class="timeline-time">${new Date(r.created_at).toLocaleString()} by ${r.created_by}</div>
              <div class="timeline-desc">${r.action_type?.replace('_',' ').toUpperCase()}</div>
            </div>
          </li>
        `).join('') || '<li style="text-align:center;padding:40px;color:var(--text-mid);"><i class="fas fa-comment-slash"></i><br>No remarks yet</li>'}
      </ul>
    </div>

    <!-- Action Buttons -->
    <div class="card" style="border-top:4px solid var(--${statusBadge});">
      <div class="card-title"><i class="fas fa-bolt"></i> Take Action</div>
      <div style="display:flex;flex-wrap:wrap;gap:12px;">
        <button class="btn btn-success btn-lg welfare-approve-btn" data-id="${application.application_id || appId || ''}" 
                ${application.workflow_status === 'welfare_pending' || application.workflow_status === 'welfare_approved' ? 'disabled' : ''}>
          <i class="fas fa-check-double"></i> Approve & Forward
        </button>
        <button class="btn btn-warning btn-lg remarks-btn" data-type="update" 
                ${application.workflow_status === 'rejected' ? 'disabled' : ''}>
          <i class="fas fa-edit"></i> Update Remarks
        </button>
        <button class="btn btn-danger btn-lg welfare-reject-btn" data-id="${application.application_id || appId || ''}"
                ${application.workflow_status === 'rejected' ? 'disabled' : ''}>
          <i class="fas fa-times"></i> Reject Application
        </button>
        <button class="btn btn-secondary" onclick="navigate('welfare-approval')">
          <i class="fas fa-arrow-left"></i> Back to Queue
        </button>
      </div>
    </div>
  `;

  attachWelfareEventListeners();
}

// 🛠 Helper Functions
async function toggleChecklist(itemId, isDone) {
  // TODO: PATCH /api/update_checklist.php
  showToast(isDone ? '✅ Checklist updated' : '❌ Checklist updated');
  await loadWelfareVerification({ applicationId: getAppId() });
}

async function verifyDocument(params = {}) {
  const appId = getAppId(params);
  if (!appId) {
    console.error('verifyDocument: missing appId');
    showToast('⚠️', 'Application ID missing');
    return;
  }
  
  try {
    const response = await apiFetch('verify_documents.php', {
      method: 'POST',
      body: { application_id: appId }
    });
    
    if (response.success) {
      showToast('✅', 'Documents verified successfully');
      return loadWelfareVerification({ applicationId: appId });
    } else {
      showToast('❌', response.message || 'Verification failed');
    }
  } catch (error) {
    console.error('verifyDocument error:', error);
    showToast('❌', 'Network error during verification');
  }
}

async function filterDocuments(type) {
  const rows = document.querySelectorAll('#documentsTable tr');
  rows.forEach(row => {
    const docType = row.cells[1]?.textContent?.trim().toLowerCase();
    row.style.display = type === '' || docType === type ? '' : 'none';
  });
}

// Render welfare verification list wrapper (fixes "renderWelfareVerifyList is not defined")
function renderWelfareVerifyList(params) {
  const appId = getAppId(params);
  console.log('[renderWelfareVerifyList] appId=', appId || '(none)');
  renderWelfareVerify({ applicationId: appId });
}

function showRemarksModal(type) {
  // Simple remarks update modal (extend as needed)
  const remarks = prompt(type === 'update' ? 'Add/update remarks:' : 'Query details:');
  if (remarks) {
    // POST to remarks API
    showToast('📝', 'Remarks updated');
    loadWelfareVerification({ applicationId: getAppId() });
  }
}

function attachWelfareEventListeners() {
  console.log('Welfare verify listeners attached');
}


// ---- WELFARE AUTHORITY APPROVAL ----
let _welfareApprovalLoading = false;

async function renderWelfareApproval() {
  if (_welfareApprovalLoading) {
    console.log('[renderWelfareApproval] Already loading, skipping duplicate call');
    return;
  }
  _welfareApprovalLoading = true;

  const el = document.getElementById('screen-welfare-approval');
  el.innerHTML = `<div class="loading-overlay"><div class="spinner-border"></div><div>Loading applications from database...</div></div>`;

  try {
    const parsed = await apiFetch('welfare/get_welfare_applications.php?tab=pending&limit=20');
    
    if (!parsed.success) {
      throw new Error(parsed.error || 'API failed - check server logs');
    }
    
    // FIX: API returns flat structure { success, data: [...], counts: {} }
    console.log('[renderWelfareApproval] API response:', parsed);
    // FIX: Handle both array and object cases for data
    const pendingApps = normalizeArray(parsed);
    const counts = parsed.counts || {};
    
    el.innerHTML = `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Welfare Authority Approval</span></div>
          <div class="page-title">Approval / Rejection Workflow</div>
          <div class="page-subtitle">Live data from annexure2a table (${pendingApps.length} applications)</div>
        </div>
      </div>

      <div class="tabs">
        <button class="tab-btn active" onclick="loadWelfareTab(this,'pending')">⏳ Pending (${counts.pending || 0})</button>
        <button class="tab-btn" onclick="loadWelfareTab(this,'approved')">✅ Approved (${counts.approved || 0})</button>
        <button class="tab-btn" onclick="loadWelfareTab(this,'rejected')">❌ Rejected (${counts.rejected || 0})</button>
        <button class="tab-btn" onclick="loadWelfareTab(this,'resubmitted')">🔄 Resubmissions (${counts.resubmitted || 0})</button>
      </div>

      <div id="tab-pending" class="tab-panel active">
        ${pendingApps.map(app => renderAppCard(app)).join('') || '<div class="empty-state"><i class="fas fa-inbox"></i><div>No Data Found</div></div>'}
      </div>

      <div id="tab-approved" class="tab-panel" style="display:none;">
        <div class="empty-state"><i class="fas fa-check-circle"></i><div>Loading approved apps...</div></div>
      </div>
      <div id="tab-rejected" class="tab-panel" style="display:none;">
        <div class="empty-state"><i class="fas fa-times-circle"></i><div>Loading rejected apps...</div></div>
      </div>
      <div id="tab-resubmitted" class="tab-panel" style="display:none;">
        <div class="empty-state"><i class="fas fa-redo"></i><div>Loading resubmissions...</div></div>
      </div>
    `;
  } catch (error) {
    console.error('Welfare approval load error:', error);
    el.innerHTML = `
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div><strong>Failed to load applications:</strong> ${error.message}</div>
        <button class="btn btn-primary btn-sm mt-2" onclick="renderWelfareApproval()">Retry</button>
      </div>
    `;
  }
  _welfareApprovalLoading = false;
}

function renderAppCard(app) {
  const statusBadge = {
    'submitted': 'pending',
    'verified': 'verified',
    'welfare_approved': 'approved',
    'acc_approved': 'approved',
    'pass_generated': 'approved',
    'rejected': 'rejected'
  }[app.workflow_status || app.status] || 'info';
  
  return `
    <div class="card" style="border-left:4px solid var(--${statusBadge});">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
          <div style="font-size:16px;font-weight:800;color:var(--primary);">${app.id}</div>
          <div style="font-size:14px;margin-top:2px;">${app.contractor} <small class="text-muted">(${app.code})</small></div>
          <div style="font-size:12px;color:var(--text-mid);margin-top:2px;">📍 ${app.project}</div>
          <div style="font-size:12px;color:var(--text-light);margin-top:2px;">
            Submitted: ${new Date(app.submitted).toLocaleDateString()} 
            | ${app.days_pending}d ago 
            | <span class="badge badge-${app.priority}">${app.priority?.toUpperCase()}</span>
          </div>
          ${app.remarks ? `<div style="font-size:11px;color:var(--warning);margin-top:4px;">📝 ${app.remarks}</div>` : ''}
        </div>
        <div class="btn-group">
          <button class="btn btn-info btn-sm review-btn" data-id="${app.id}">
            <i class="fas fa-eye"></i> Review
          </button>
          <button class="btn btn-success btn-sm welfare-approve-btn" data-id="${app.id}">
            <i class="fas fa-check"></i> Approve
          </button>
          <button class="btn btn-danger btn-sm welfare-reject-btn" data-id="${app.id}">
            <i class="fas fa-times"></i> Reject
          </button>
        </div>
      </div>
    </div>
  `;
}

// Dynamic tab loader
async function loadWelfareTab(btn, tab) {
  // Switch tabs
  document.querySelectorAll('.tabs .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
  const targetPanel = document.getElementById(`tab-${tab}`);
  if (!targetPanel) {
    console.error(`[loadWelfareTab] Panel #tab-${tab} not found`);
    return;
  }
  targetPanel.style.display = 'block';
  
  // Load data
  if (targetPanel.classList.contains('loaded')) return;
  
  targetPanel.innerHTML = '<div class="loading-overlay"><div class="spinner-border"></div>Loading...</div>';
  
  try {
    const parsed = await apiFetch(`welfare/get_welfare_applications.php?tab=${encodeURIComponent(tab)}`);
    console.log(`[loadWelfareTab] ← parsed response:`, parsed);
    
    const apps = normalizeArray(parsed);
    
    console.log(`[loadWelfareTab:${tab}] extracted apps:`, apps);
    
    targetPanel.innerHTML = apps.map(renderAppCard).join('') || 
      `<div class="empty-state"><i class="fas fa-inbox"></i><div>No Data Found</div></div>`;
    targetPanel.classList.add('loaded');
  } catch (error) {
    console.error('[loadWelfareTab] error:', error);
    targetPanel.innerHTML = `<div class="alert alert-danger">Load failed: ${error.message}</div>`;
  }
}

// Action handlers
async function welfareApprove(params) {
  const appId = getAppId(params);
  if (!appId) { showToast('⚠️', 'No application ID'); return; }
  return approve(appId);
}

async function welfareReject(params) {
  const appId = getAppId(params);
  if (!appId) { showToast('⚠️', 'No application ID'); return; }
  return reject(appId);
}


// ---- ENROLMENT ----
// function renderEnrolment() {
//   document.getElementById('screen-enrolment').innerHTML = `
//     <div class="page-header">
//       <div>
//         <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Enrolment</span></div>
//         <div class="page-title">Annexure 4/A – Enrolment of Personnel</div>
//         <div class="page-subtitle">Enrol contractor representatives, supervisors, and workmen</div>
//       </div>
//       <button class="btn btn-primary" onclick="showEnrolForm()"><i class="fas fa-user-plus"></i> Enrol New Person</button>
//     </div>

//     <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
//       <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-tie"></i></div><div><div class="stat-value">${APP_DATA.representatives.length}</div><div class="stat-label">Representatives</div></div></div>
//       <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-user-cog"></i></div><div><div class="stat-value">${APP_DATA.supervisors.length}</div><div class="stat-label">Supervisors</div></div></div>
//       <div class="stat-card"><div class="stat-icon green"><i class="fas fa-hard-hat"></i></div><div><div class="stat-value">${APP_DATA.workmen.length}</div><div class="stat-label">Workmen</div></div></div>
//       <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-users"></i></div><div><div class="stat-value">${APP_DATA.representatives.length + APP_DATA.supervisors.length + APP_DATA.workmen.length}</div><div class="stat-label">Total Enrolled</div></div></div>
//     </div>

//     <div class="tabs">
//       <button class="tab-btn active" onclick="switchTab(this,'enrol-workmen')">👷 Workmen (${APP_DATA.workmen.length})</button>
//       <button class="tab-btn" onclick="switchTab(this,'enrol-supervisors')">👔 Supervisors (${APP_DATA.supervisors.length})</button>
//       <button class="tab-btn" onclick="switchTab(this,'enrol-representatives')">👤 Representatives (${APP_DATA.representatives.length})</button>
//     </div>

//     <div id="enrol-workmen" class="tab-panel active">
//       <div class="search-bar">
//         <input class="search-input" placeholder="Search workmen..."/>
//         <select class="form-control" style="width:140px;"><option>All Status</option><option>Active</option><option>Pending</option></select>
//         <button class="btn btn-primary btn-sm" onclick="showEnrolForm()"><i class="fas fa-plus"></i> Add Workman</button>
//       </div>
//       <div class="table-wrap">
//         <table class="data-table">
//           <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Aadhar</th><th>Age</th><th>Temp ID</th><th>Training</th><th>Status</th><th>Actions</th></tr></thead>
//           <tbody>
//             ${APP_DATA.workmen.map((w,i) => `
//               <tr>
//                 <td>${i+1}</td>
//                 <td><span style="font-size:20px;margin-right:8px;">${w.photo}</span><strong>${w.name}</strong></td>
//                 <td>${w.role}</td>
//                 <td>${w.aadhar}</td>
//                 <td>${w.age}</td>
//                 <td><span style="font-family:monospace;font-weight:700;color:var(--primary);">${w.tempId}</span></td>
//                 <td><span class="badge badge-${w.trainingStatus === 'qualified' ? 'approved' : w.trainingStatus === 'pending' ? 'pending' : 'info'}">${w.trainingStatus.replace('_',' ')}</span></td>
//                 <td><span class="badge badge-${w.status}">${w.status}</span></td>
//                 <td class="actions">
//                   <button class="btn btn-info btn-sm" onclick="navigate('temp-id')"><i class="fas fa-id-card"></i></button>
//                   <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
//                   <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
//                 </td>
//               </tr>`).join('')}
//           </tbody>
//         </table>
//       </div>
//     </div>

//     <div id="enrol-supervisors" class="tab-panel">
//       <div class="table-wrap">
//         <table class="data-table">
//           <thead><tr><th>ID</th><th>Name</th><th>Designation</th><th>Qualification</th><th>Experience</th><th>Temp ID</th><th>Training</th><th>Status</th><th>Actions</th></tr></thead>
//           <tbody>
//             ${APP_DATA.supervisors.map(s => `
//               <tr>
//                 <td><strong>${s.id}</strong></td>
//                 <td>${s.name}</td>
//                 <td>${s.designation}</td>
//                 <td>${s.qualification}</td>
//                 <td>${s.experience}</td>
//                 <td><span style="font-family:monospace;font-weight:700;color:var(--primary);">${s.tempId}</span></td>
//                 <td><span class="badge badge-${s.trainingStatus === 'qualified' ? 'approved' : 'pending'}">${s.trainingStatus}</span></td>
//                 <td><span class="badge badge-active">${s.status}</span></td>
//                 <td class="actions">
//                   <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
//                   <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
//                 </td>
//               </tr>`).join('')}
//           </tbody>
//         </table>
//       </div>
//     </div>

//     <div id="enrol-representatives" class="tab-panel">
//       <div class="table-wrap">
//         <table class="data-table">
//           <thead><tr><th>ID</th><th>Name</th><th>Designation</th><th>Phone</th><th>Email</th><th>Authority Level</th><th>Temp ID</th><th>Actions</th></tr></thead>
//           <tbody>
//             ${APP_DATA.representatives.map(r => `
//               <tr>
//                 <td><strong>${r.id}</strong></td>
//                 <td>${r.name}</td>
//                 <td>${r.designation}</td>
//                 <td>${r.phone}</td>
//                 <td>${r.email}</td>
//                 <td><span class="badge badge-${r.authority === 'Full' ? 'approved' : 'info'}">${r.authority}</span></td>
//                 <td><span style="font-family:monospace;font-weight:700;color:var(--primary);">${r.tempId}</span></td>
//                 <td class="actions">
//                   <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
//                   <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
//                 </td>
//               </tr>`).join('')}
//           </tbody>
//         </table>
//       </div>
//     </div>

//     <!-- New Enrolment Form (hidden) -->
//     <div id="enrol-form-section" style="display:none;" class="form-section">
//       <div class="form-section-title"><i class="fas fa-user-plus"></i> Enrol New Workman / Personnel</div>
//       <div class="form-grid">
//         <div class="form-group"><label class="form-label required">Full Name</label><input class="form-control" placeholder="As per Aadhar Card"/></div>
//         <div class="form-group"><label class="form-label required">Role / Designation</label><select class="form-control"><option>Mason</option><option>Carpenter</option><option>Plumber</option><option>Electrician</option><option>Helper</option><option>Supervisor</option><option>Representative</option></select></div>
//         <div class="form-group"><label class="form-label required">Aadhar Number</label><input class="form-control" placeholder="XXXX XXXX XXXX"/></div>
//         <div class="form-group"><label class="form-label required">Date of Birth</label><input class="form-control" type="date"/></div>
//         <div class="form-group"><label class="form-label">Mobile Number</label><input class="form-control" placeholder="+91 XXXXX XXXXX"/></div>
//         <div class="form-group"><label class="form-label">Emergency Contact</label><input class="form-control" placeholder="Emergency contact no."/></div>
//         <div class="form-group full">
//           <label class="form-label">Photograph</label>
//           <div class="upload-area" onclick="showToast('📷','Camera/Upload feature activated')">
//             <div class="upload-icon">📷</div>
//             <div class="upload-text">Click to upload photo or use camera</div>
//             <div class="upload-hint">JPG, PNG – Max 2MB. Clear face photo required</div>
//           </div>
//         </div>
//       </div>
//       <div class="btn-group" style="margin-top:12px;">
//         <button class="btn btn-light" onclick="hideEnrolForm()">Cancel</button>
//         <button class="btn btn-primary" onclick="submitAndToast('✅ Person enrolled successfully! Temporary ID generated.','temp-id')"><i class="fas fa-check"></i> Enrol & Generate Temp ID</button>
//       </div>
//     </div>
//   `;
// }

function renderEnrolment() {
  document.getElementById('screen-enrolment').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb">
          <a onclick="navigate('dashboard')">Dashboard</a>
          <span class="sep">›</span>
          <span>Enrolment</span>
        </div>
        <div class="page-title">Annexure 4/A – Enrolment of Personnel</div>
        <div class="page-subtitle">Enrol contractor representatives, supervisors, and workmen</div>
      </div>
      <button class="btn btn-primary" onclick="showEnrolForm()">
        <i class="fas fa-user-plus"></i> Enrol New Person
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-user-tie"></i></div>
        <div>
          <div class="stat-value" id="repCount">0</div>
          <div class="stat-label">Representatives</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user-cog"></i></div>
        <div>
          <div class="stat-value" id="supervisorCount">0</div>
          <div class="stat-label">Supervisors</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-hard-hat"></i></div>
        <div>
          <div class="stat-value" id="workmenCount">0</div>
          <div class="stat-label">Workmen</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-users"></i></div>
        <div>
          <div class="stat-value" id="totalCount">0</div>
          <div class="stat-label">Total Enrolled</div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn active" onclick="showWorkmen()">👷 Workmen</button>
      <button class="tab-btn" onclick="showSupervisors()">👔 Supervisors</button>
      <button class="tab-btn" onclick="showRepresentatives()">👤 Representatives</button>
    </div>

    <!-- Unified Table -->
    <div class="card">
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th><th>Name</th><th>Role</th><th>Aadhaar</th>
              <th>Age</th><th>Temp ID</th><th>Training</th>
              <th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="enrolmentTableBody">
            <tr><td colspan="8">Loading data...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  `;

  // Fetch data
  loadEnrolment();
}

async function loadEnrolment() {
  try {
    const res = await fetch('api/get_enrolment_data.php');
    const json = await res.json();

    console.log("ENROLMENT DATA:", json); // DEBUG

    if (!json.success) return;

    window.enrolmentData = json.data || [];
    renderEnrolmentData(window.enrolmentData);
  } catch (err) {
    console.error("loadEnrolment Error:", err);
  }
}

function renderEnrolmentData(data) {
  const workmen = data.filter(x => x.role === 'workman');
  const supervisors = data.filter(x => x.role === 'supervisor');
  const reps = data.filter(x => x.role === 'representative');

  // update counts
  if (document.getElementById("workmenCount")) document.getElementById("workmenCount").innerText = workmen.length;
  if (document.getElementById("supervisorCount")) document.getElementById("supervisorCount").innerText = supervisors.length;
  if (document.getElementById("repCount")) document.getElementById("repCount").innerText = reps.length;
  if (document.getElementById("totalCount")) document.getElementById("totalCount").innerText = data.length;

  // default show workmen
  renderTable(workmen);
}

function renderTable(data) {
  const tbody = document.querySelector("#enrolmentTableBody");
  if (!tbody) return;

  tbody.innerHTML = "";

  if (!data.length) {
    tbody.innerHTML = "<tr><td colspan='9' style='text-align:center;padding:20px;'>No data found</td></tr>";
    return;
  }

  data.forEach((item, i) => {
    tbody.innerHTML += `
    <tr>
        <td>${i+1}</td>
        <td>${item.name || ''}</td>
        <td>${item.role || ''}</td>
        <td>${item.aadhaar || ''}</td>
        <td>${item.age || ''}</td>
        <td>${item.temp_id || ''}</td>
        <td>${item.training_status || ''}</td>
        <td>${item.status || ''}</td>
        <td class="actions">
          <button class="btn btn-info btn-sm" onclick="showEnrolForm('${item.role}', window.enrolmentData[${window.enrolmentData.indexOf(item)}])"><i class="fas fa-edit"></i></button>
          <button class="btn btn-danger btn-sm" onclick="deletePerson('${item.id}', '${item.role}')"><i class="fas fa-trash"></i></button>
        </td>
    </tr>`;
  });
}

function showWorkmen() {
  // Update active tab UI
  document.querySelectorAll('.tabs .tab-btn').forEach((btn, idx) => {
    btn.classList.toggle('active', idx === 0);
  });
  renderTable(window.enrolmentData.filter(x => x.role === 'workman'));
}

function showSupervisors() {
  document.querySelectorAll('.tabs .tab-btn').forEach((btn, idx) => {
    btn.classList.toggle('active', idx === 1);
  });
  renderTable(window.enrolmentData.filter(x => x.role === 'supervisor'));
}

function showRepresentatives() {
  document.querySelectorAll('.tabs .tab-btn').forEach((btn, idx) => {
    btn.classList.toggle('active', idx === 2);
  });
  renderTable(window.enrolmentData.filter(x => x.role === 'representative'));
}




async function loadWorkmen() {
  const appId = getAppId();
  const tbody = document.querySelector("#workmenTable tbody") || document.getElementById("workmenTableBody");
  console.log("APP ID:", appId);
  console.log('[loadWorkmen] start', { application_id: appId, tableFound: Boolean(tbody) });
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">Loading workmen...</td></tr>';
  }
  if (!appId) {
    console.error("No application_id found");
    workmenData = [];
    updateStats();
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No application selected</td></tr>';
    }
    return;
  }
  try {
    const json = await apiFetch('get_workmen.php?application_id=' + encodeURIComponent(appId));
    console.log("API RESPONSE:", json);
    const workmen = normalizeArray(json);
    console.log('[loadWorkmen] extracted workmen array:', {
      count: workmen.length,
      firstRecord: workmen?.[0] || null
    });
    workmenData = workmen;
    updateStats();
    renderWorkmenTable(workmenData);
  } catch (error) {
    console.error('[loadWorkmen] Error:', error);
    workmenData = [];
    updateStats();
    renderWorkmenTable(workmenData);
  }
}

function updateStats() {
  const workmenCountEl = document.getElementById("workmenCount");
  const workmenTabCountEl = document.getElementById("workmenTabCount");
  const supervisorsCountEl = document.getElementById("supervisorsCount");
  const supervisorsTabCountEl = document.getElementById("supervisorsTabCount");
  const representativesCountEl = document.getElementById("representativesCount");
  const representativesTabCountEl = document.getElementById("representativesTabCount");
  const totalCountEl = document.getElementById("totalCount");

  if (workmenCountEl) workmenCountEl.innerText = (Array.isArray(workmenData) ? workmenData.length : 0) || 0;
  if (workmenTabCountEl) workmenTabCountEl.innerText = (Array.isArray(workmenData) ? workmenData.length : 0) || 0;
  if (supervisorsCountEl) supervisorsCountEl.innerText = (Array.isArray(supervisorsData) ? supervisorsData.length : 0) || 0;
  if (supervisorsTabCountEl) supervisorsTabCountEl.innerText = (Array.isArray(supervisorsData) ? supervisorsData.length : 0) || 0;
  if (representativesCountEl) representativesCountEl.innerText = (Array.isArray(representativesData) ? representativesData.length : 0) || 0;
  if (representativesTabCountEl) representativesTabCountEl.innerText = (Array.isArray(representativesData) ? representativesData.length : 0) || 0;

  let total = 
    (Array.isArray(workmenData) ? workmenData.length : 0) +
    (Array.isArray(supervisorsData) ? supervisorsData.length : 0) +
    (Array.isArray(representativesData) ? representativesData.length : 0);

  if (totalCountEl) totalCountEl.innerText = total;
}

let supervisorsData = [];
let representativesData = [];

async function loadSupervisors() {
  const appId = getAppId();
  const tbody = document.querySelector("#supervisorsTable tbody") || document.getElementById("supervisorsTableBody");

  console.log("APP ID:", appId);
  console.log('[loadSupervisors] start', {
    application_id: appId,
    tableFound: Boolean(tbody)
  });

  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;">Loading supervisors...</td></tr>';
  }

  if (!appId) {
    console.error("No application_id found");
    supervisorsData = [];
    updateStats();
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;">No application selected</td></tr>';
    }
    return;
  }

  try {
    const json = await apiFetch(`get_supervisors.php?application_id=${encodeURIComponent(appId)}`);
    console.log("API RESPONSE:", json);
    const list = normalizeArray(json);
    console.log('[loadSupervisors] extracted supervisors:', { count: list.length, firstRecord: list?.[0] || null });

    supervisorsData = list;
    updateStats();

    if (!tbody) {
      console.warn('[loadSupervisors] #supervisorsTable tbody not found');
      return;
    }

    tbody.innerHTML = '';
    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;">No supervisors found</td></tr>';
      return;
    }

    list.forEach((item, i) => {
      const trainingStatus = String(item?.training_status || item?.trainingStatus || 'pending');
      const trainingBadge = trainingStatus === 'qualified' ? 'approved' : trainingStatus === 'pending' ? 'pending' : 'info';
      const aadhar = item?.aadhar ? String(item.aadhar) : '';
      const maskedAadhar = aadhar ? aadhar.substring(0, 4) + ' XXXX ' + aadhar.slice(-4) : '-';

      tbody.insertAdjacentHTML('beforeend', `
        <tr data-id="${escapeHtml(item?.id || '')}" data-type="supervisor">
          <td>${i + 1}</td>
          <td><strong>${escapeHtml(item?.name || '-')}</strong></td>
          <td>${escapeHtml(item?.designation || item?.role || '-')}</td>
          <td>${escapeHtml(maskedAadhar)}</td>
          <td>${escapeHtml(item?.phone || '-')}</td>
          <td>${escapeHtml(item?.qualification || item?.role || '-')}</td>
          <td>${escapeHtml(item?.experience ?? '-')}</td>
          <td><span class="badge badge-${trainingBadge}">${escapeHtml(trainingStatus.replace('_', ' '))}</span></td>
          <td><span class="badge badge-${escapeHtml(item?.status || 'active')}">${escapeHtml(item?.status || 'active')}</span></td>
          <td class="actions">
            <button class="btn btn-info btn-sm" onclick="showEnrolForm('supervisor', supervisorsData[${i}])"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm" onclick="deletePerson('${escapeHtml(item?.id || '')}', 'supervisor')"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      `);
    });
  } catch (error) {
    console.error('[loadSupervisors] Error:', error);
    supervisorsData = [];
    updateStats();
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;">No supervisors found</td></tr>';
    }
  }
}

async function loadRepresentatives() {
  const appId = getAppId();
  const tbody = document.querySelector("#representativesTable tbody") || document.getElementById("representativesTableBody");

  console.log("APP ID:", appId);
  console.log('[loadRepresentatives] start', {
    application_id: appId,
    tableFound: Boolean(tbody)
  });

  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">Loading representatives...</td></tr>';
  }

  if (!appId) {
    console.error("No application_id found");
    representativesData = [];
    updateStats();
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No application selected</td></tr>';
    }
    return;
  }

  try {
    const json = await apiFetch(`get_representatives.php?application_id=${encodeURIComponent(appId)}`);
    console.log("API RESPONSE:", json);
    const list = normalizeArray(json);
    console.log('[loadRepresentatives] extracted representatives:', { count: list.length, firstRecord: list?.[0] || null });

    representativesData = list;
    updateStats();

    if (!tbody) {
      console.warn('[loadRepresentatives] #representativesTable tbody not found');
      return;
    }

    tbody.innerHTML = '';
    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No representatives found</td></tr>';
      return;
    }

    list.forEach((item, i) => {
      const aadhar = item?.aadhar ? String(item.aadhar) : '';
      const maskedAadhar = aadhar ? aadhar.substring(0, 4) + ' XXXX ' + aadhar.slice(-4) : '-';

      tbody.insertAdjacentHTML('beforeend', `
        <tr data-id="${escapeHtml(item?.id || '')}" data-type="representative">
          <td>${i + 1}</td>
          <td><strong>${escapeHtml(item?.name || '-')}</strong></td>
          <td>${escapeHtml(item?.designation || item?.role || '-')}</td>
          <td>${escapeHtml(maskedAadhar)}</td>
          <td>${escapeHtml(item?.phone || '-')}</td>
          <td>${escapeHtml(item?.email || '-')}</td>
          <td><span class="badge badge-${(item?.authority || 'Partial') === 'Full' ? 'approved' : 'info'}">${escapeHtml(item?.authority || 'Partial')}</span></td>
          <td><span style="font-family:monospace;font-weight:700;color:var(--primary);">${escapeHtml(item?.temp_id || item?.tempId || 'Pending')}</span></td>
          <td class="actions">
            <button class="btn btn-info btn-sm" onclick="showEnrolForm('representative', representativesData[${i}])"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm" onclick="deletePerson('${escapeHtml(item?.id || '')}', 'representative')"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
      `);
    });
  } catch (error) {
    console.error('[loadRepresentatives] Error:', error);
    representativesData = [];
    updateStats();
    if (tbody) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No representatives found</td></tr>';
    }
  }
}



function renderSupervisorTableLegacy() {
  let tbody = document.getElementById("supervisorsTableBody");
  if (!tbody) return;

  tbody.innerHTML = supervisorsData.map((s, i) => `
    <tr>
      <td>${i+1}</td>
      <td><strong>${s.name}</strong></td>
      <td>${s.designation}</td>
      <td>${s.aadhar}</td>
      <td>${s.phone}</td>
      <td>${s.qualification}</td>
      <td>${s.experience}</td>
      <td>
        <span class="badge badge-${s.trainingStatus === 'qualified' ? 'approved' : s.trainingStatus === 'pending' ? 'pending' : 'info'}">
          ${s.trainingStatus?.replace('_', ' ') || 'N/A'}
        </span>
      </td>
      <td>
        <span class="badge badge-${s.status}">
          ${s.status}
        </span>
      </td>
      <td class="actions">
        <button class="btn btn-info btn-sm"><i class="fas fa-id-card"></i></button>
        <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
        <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="10">No supervisors found</td></tr>';
}

function renderRepresentativeTableLegacy() {
  let tbody = document.getElementById("representativesTableBody");
  if (!tbody) return;
  if (!Array.isArray(representativesData)) {
    representativesData = [];
  }

  tbody.innerHTML = representativesData.map((r, i) => `
    <tr data-id="${r.id || ''}" data-type="representative" onclick="editPerson(this, 'representative')">
      <td>${i+1}</td>
      <td><strong>${r.name || 'N/A'}</strong></td>
      <td>${r.designation || '—'}</td>
      <td>${r.aadhar ? (r.aadhar.substring(0, 4) + ' XXXX ' + r.aadhar.slice(-4)) : 'N/A'}</td>
      <td>${r.phone || '—'}</td>
      <td>${r.email || '—'}</td>
      <td>
        <span class="badge badge-${(r.authority || 'Partial') === 'Full' ? 'approved' : 'info'}">
          ${r.authority || 'Partial'}
        </span>
      </td>
      <td>
        <span style="font-family:monospace;font-weight:700;color:var(--primary);">
          ${r.temp_id || r.tempId || 'Pending'}
        </span>
      </td>
      <td class="actions">
        <button class="btn btn-info btn-sm"><i class="fas fa-edit"></i></button>
        <button class="btn btn-danger btn-sm" onclick="deletePerson('${r.id || ''}', 'representative')"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="9">No representatives found</td></tr>';
}

function editPerson(row, type) {
  const data = Object.fromEntries([...row.children].slice(1).map((cell, idx) => {
    if (idx === 0) return ['name', cell.textContent.trim()];
    // Simple parse, for complex use data attrs fully
  }));
  // Better: use data attrs or full row data
  const rowData = representativesData.find(r => r.id === row.dataset.id); // adjust per type
  showEnrolForm(type, rowData);
}

// CRUD functions
function showEnrolForm(type = 'workman', editData = null) {
  document.getElementById('personModal').classList.add('show');
  document.getElementById('personType').value = type;
  document.getElementById('modalTitle').textContent = editData ? 'Edit ' + type : 'Enrol New ' + type;
  const fieldsDiv = document.getElementById('dynamicFields');
  let fieldsHTML = '';
  if (type === 'workman') {
    fieldsHTML = `
      <div class="form-group"><label>Role</label><input name="role" class="form-control" value="${editData?.role || ''}"></div>
      <div class="form-group"><label>Age</label><input name="age" type="number" class="form-control" value="${editData?.age || ''}"></div>
      <div class="form-group"><label>Photo</label><input name="photo" class="form-control" value="${editData?.photo || '👷'}"></div>
    `;
  } else if (type === 'supervisor') {
    fieldsHTML = `
      <div class="form-group"><label>Designation</label><input name="designation" class="form-control" value="${editData?.designation || ''}"></div>
      <div class="form-group"><label>Phone</label><input name="phone" class="form-control" value="${editData?.phone || ''}"></div>
      <div class="form-group"><label>Qualification</label><input name="qualification" class="form-control" value="${editData?.qualification || ''}"></div>
      <div class="form-group"><label>Experience</label><input name="experience" class="form-control" value="${editData?.experience || ''}"></div>
    `;
  } else if (type === 'representative') {
    fieldsHTML = `
      <div class="form-group"><label>Designation</label><input name="designation" class="form-control" value="${editData?.designation || ''}"></div>
      <div class="form-group"><label>Phone</label><input name="phone" class="form-control" value="${editData?.phone || ''}"></div>
      <div class="form-group"><label>Email</label><input name="email" class="form-control" value="${editData?.email || ''}"></div>
      <div class="form-group"><label>Authority</label><select name="authority" class="form-control">
        <option ${editData?.authority === 'Full' ? 'selected' : ''}>Full</option>
        <option ${editData?.authority === 'Partial' ? 'selected' : ''}>Partial</option>
      </select></div>
    `;
  }
  fieldsDiv.innerHTML = fieldsHTML;
  
  if (editData) {
    document.getElementById('personId').value = editData.id;
    document.getElementById('editId').value = editData.id;
  } else {
    document.getElementById('personId').value = '';
    document.getElementById('editId').value = '';
  }
}

function closePersonModal() {
  document.getElementById('personModal').classList.remove('show');
}

function deletePerson(id, type) {
  if (!confirm('Delete this record?')) return;
  apiFetch('delete_person.php', {
    method: 'POST',
    body: { id: id, type: type }
  })
    .then(data => {
      if (data.success) {
        loadCurrentTab();
        showToast('✅', 'Deleted');
      } else {
        showToast('❌', data.error || 'Delete failed');
      }
    })
    .catch(err => {
      console.error('Delete error:', err);
      showToast('❌', 'Error deleting record');
    });
}

function loadCurrentTab() {
  loadEnrolment();
}

// Form submit — FIXED: sends JSON to api/insert_workman.php with application_id
document.addEventListener('DOMContentLoaded', function() {
  const personForm = document.getElementById('personForm');
  if (personForm) {
    personForm.onsubmit = async function(e) {
      e.preventDefault();

      const application_id = getAppId();
      console.log('[personForm submit] application_id:', application_id);

      if (!application_id) {
        showToast('❌', 'Application ID missing. Submit Contractor Registration first.');
        return;
      }

      const name        = document.getElementById('personName')?.value.trim() || '';
      const aadhar      = document.getElementById('personAadhar')?.value.trim() || '';
      const type        = document.getElementById('personType')?.value || 'workman';
      const father_name = '';
      const dob         = null;
      const gender      = 'Male';
      const phone       = '';
      const role        = type === 'workman' ? 'Helper' : type;
      const address     = '';
      const state       = '';

      if (!name || !aadhar) {
        showToast('❌', 'Name and Aadhaar are required');
        return;
      }
      if (aadhar.length !== 12 || !/^\d{12}$/.test(aadhar)) {
        showToast('❌', 'Aadhaar must be exactly 12 digits');
        return;
      }

      const id = document.getElementById('editId')?.value || null;

      const payload = {
        id,
        application_id,
        name,
        father_name,
        dob,
        gender,
        aadhar,
        phone,
        role,
        address,
        state,
        type
      };

      console.log('[personForm submit] Sending payload:', payload);

      try {
        const result = await apiFetch('insert_workman.php', {
          method: 'POST',
          body: payload
        });
        console.log('[personForm submit] API response:', result);

        if (result.success && result.data && result.data.id) {
          closePersonModal();
          loadCurrentTab();
          showToast('✅', `${name} enrolled! Temp ID: ${result.data.temp_id || ''}`);
        } else {
          showToast('❌', result.error || result.message || 'Enrolment failed');
        }
      } catch (err) {
        console.error('[personForm submit] Error:', err);
        showToast('❌', 'Network error. Please try again.');
      }
    };
  }
});

function renderWorkmenTableLegacy() {
  let tbody = document.getElementById("workmenTableBody");
  if (!tbody) return;
  if (!Array.isArray(workmenData)) {
    workmenData = [];
  }

  tbody.innerHTML = workmenData.map((w, i) => `
    <tr data-id="${w.id || ''}" data-type="workman">
      <td>${i+1}</td>
      <td>
        <span style="font-size:20px;margin-right:8px;">${w.photo || '👷'}</span>
        <strong>${w.name || 'N/A'}</strong>
      </td>
      <td>${w.role || 'Helper'}</td>
      <td>${w.aadhar ? (w.aadhar.substring(0, 4) + ' XXXX ' + w.aadhar.slice(-4)) : 'N/A'}</td>
      <td>${w.age || '—'}</td>
      <td>
        <span style="font-family:monospace;font-weight:700;color:var(--primary);">
          ${w.temp_id || w.tempId || 'Pending'}
        </span>
      </td>
      <td>
        <span class="badge badge-${(w.training_status || w.trainingStatus || 'pending') === 'qualified' ? 'approved' : (w.training_status || w.trainingStatus || 'pending') === 'pending' ? 'pending' : 'info'}">
          ${(w.training_status || w.trainingStatus || 'pending').replace('_',' ')}
        </span>
      </td>
      <td>
        <span class="badge badge-${w.status || 'active'}">
          ${w.status || 'active'}
        </span>
      </td>
      <td class="actions">
        <button class="btn btn-info btn-sm" onclick="showEnrolForm('workman', ${JSON.stringify(w).replace(/"/g, '"')})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-danger btn-sm" onclick="deletePerson('${w.id || ''}', 'workman')"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="9">No workmen found</td></tr>';
}

function renderWorkmenTable(workmen = workmenData) {
  const tbody = document.querySelector("#workmenTable tbody") || document.getElementById("workmenTableBody");
  if (!tbody) {
    console.warn('[renderWorkmenTable] Workmen table body not found. Expected #workmenTable tbody or #workmenTableBody.');
    return;
  }

  const rows = Array.isArray(workmen) ? workmen : [];
  console.log('[renderWorkmenTable] rendering rows:', rows.length);

  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No workmen found</td></tr>';
    return;
  }

  tbody.innerHTML = rows.map((w, i) => {
    const trainingStatus = String(w?.training_status || w?.trainingStatus || 'pending');
    const trainingBadge = trainingStatus === 'qualified' ? 'approved' : trainingStatus === 'pending' ? 'pending' : 'info';
    const aadhaar = w?.aadhar ? String(w.aadhar) : '';
    const maskedAadhaar = aadhaar ? aadhaar.substring(0, 4) + ' XXXX ' + aadhaar.slice(-4) : 'N/A';

    return `
      <tr data-id="${escapeHtml(w?.id || '')}" data-type="workman">
        <td>${i + 1}</td>
        <td>
          <span style="font-size:20px;margin-right:8px;">${escapeHtml(w?.photo || '👷')}</span>
          <strong>${escapeHtml(w?.name || 'N/A')}</strong>
        </td>
        <td>${escapeHtml(w?.role || 'Helper')}</td>
        <td>${escapeHtml(maskedAadhaar)}</td>
        <td>${escapeHtml(w?.age || '—')}</td>
        <td>
          <span style="font-family:monospace;font-weight:700;color:var(--primary);">
            ${escapeHtml(w?.temp_id || w?.tempId || 'Pending')}
          </span>
        </td>
        <td>
          <span class="badge badge-${trainingBadge}">
            ${escapeHtml(trainingStatus.replace('_', ' '))}
          </span>
        </td>
        <td>
          <span class="badge badge-${escapeHtml(w?.status || 'active')}">
            ${escapeHtml(w?.status || 'active')}
          </span>
        </td>
        <td class="actions">
          <button class="btn btn-info btn-sm" onclick="showEnrolForm('workman', workmenData[${i}])"><i class="fas fa-edit"></i></button>
          <button class="btn btn-danger btn-sm" onclick="deletePerson('${escapeHtml(w?.id || '')}', 'workman')"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
  }).join('');
}

function renderSupervisorTableLegacy2() {
  let tbody = document.getElementById("supervisorsTableBody");
  if (!tbody) return;
  if (!Array.isArray(supervisorsData)) {
    supervisorsData = [];
  }

  tbody.innerHTML = supervisorsData.map((s, i) => `
    <tr data-id="${s.id || ''}" data-type="supervisor">
      <td>${i+1}</td>
      <td><strong>${s.name || 'N/A'}</strong></td>
      <td>${s.designation || '—'}</td>
      <td>${s.aadhar ? (s.aadhar.substring(0, 4) + ' XXXX ' + s.aadhar.slice(-4)) : 'N/A'}</td>
      <td>${s.phone || '—'}</td>
      <td>${s.qualification || '—'}</td>
      <td>${s.experience || '—'}</td>
      <td>
        <span class="badge badge-${(s.training_status || s.trainingStatus || 'pending') === 'qualified' ? 'approved' : (s.training_status || s.trainingStatus || 'pending') === 'pending' ? 'pending' : 'info'}">
          ${(s.training_status || s.trainingStatus || 'pending').replace('_', ' ')}
        </span>
      </td>
      <td>
        <span class="badge badge-${s.status || 'active'}">
          ${s.status || 'active'}
        </span>
      </td>
      <td class="actions">
        <button class="btn btn-info btn-sm" onclick="showEnrolForm('supervisor', ${JSON.stringify(s).replace(/"/g, '"')})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-danger btn-sm" onclick="deletePerson('${s.id || ''}', 'supervisor')"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="10">No supervisors found</td></tr>';
}

function renderSupervisorTable(supervisors = supervisorsData) {
  const tbody = document.querySelector("#supervisorsTable tbody") || document.getElementById("supervisorsTableBody");
  if (!tbody) {
    console.warn('[renderSupervisorTable] Supervisors table body not found. Expected #supervisorsTable tbody or #supervisorsTableBody.');
    return;
  }

  const rows = Array.isArray(supervisors) ? supervisors : [];
  console.log('[renderSupervisorTable] rendering rows:', rows.length);

  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:20px;">No supervisors found</td></tr>';
    return;
  }

  tbody.innerHTML = rows.map((item, i) => {
    const trainingStatus = String(item?.training_status || item?.trainingStatus || 'pending');
    const trainingBadge = trainingStatus === 'qualified' ? 'approved' : trainingStatus === 'pending' ? 'pending' : 'info';
    const aadhar = item?.aadhar ? String(item.aadhar) : '';
    const maskedAadhar = aadhar ? aadhar.substring(0, 4) + ' XXXX ' + aadhar.slice(-4) : '-';

    return `
      <tr data-id="${escapeHtml(item?.id || '')}" data-type="supervisor">
        <td>${i + 1}</td>
        <td><strong>${escapeHtml(item?.name || '-')}</strong></td>
        <td>${escapeHtml(item?.designation || item?.role || '-')}</td>
        <td>${escapeHtml(maskedAadhar)}</td>
        <td>${escapeHtml(item?.phone || '-')}</td>
        <td>${escapeHtml(item?.qualification || item?.role || '-')}</td>
        <td>${escapeHtml(item?.experience ?? '-')}</td>
        <td>
          <span class="badge badge-${trainingBadge}">
            ${escapeHtml(trainingStatus.replace('_', ' '))}
          </span>
        </td>
        <td>
          <span class="badge badge-${escapeHtml(item?.status || 'active')}">
            ${escapeHtml(item?.status || 'active')}
          </span>
        </td>
        <td class="actions">
          <button class="btn btn-info btn-sm" onclick="showEnrolForm('supervisor', supervisorsData[${i}])"><i class="fas fa-edit"></i></button>
          <button class="btn btn-danger btn-sm" onclick="deletePerson('${escapeHtml(item?.id || '')}', 'supervisor')"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
  }).join('');
}

function renderRepresentativeTable(representatives = representativesData) {
  const tbody = document.querySelector("#representativesTable tbody") || document.getElementById("representativesTableBody");
  if (!tbody) {
    console.warn('[renderRepresentativeTable] Representatives table body not found. Expected #representativesTable tbody or #representativesTableBody.');
    return;
  }

  const rows = Array.isArray(representatives) ? representatives : [];
  console.log('[renderRepresentativeTable] rendering rows:', rows.length);

  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No representatives found</td></tr>';
    return;
  }

  tbody.innerHTML = rows.map((item, i) => {
    const aadhar = item?.aadhar ? String(item.aadhar) : '';
    const maskedAadhar = aadhar ? aadhar.substring(0, 4) + ' XXXX ' + aadhar.slice(-4) : '-';

    return `
      <tr data-id="${escapeHtml(item?.id || '')}" data-type="representative">
        <td>${i + 1}</td>
        <td><strong>${escapeHtml(item?.name || '-')}</strong></td>
        <td>${escapeHtml(item?.designation || item?.role || '-')}</td>
        <td>${escapeHtml(maskedAadhar)}</td>
        <td>${escapeHtml(item?.phone || '-')}</td>
        <td>${escapeHtml(item?.email || '-')}</td>
        <td>
          <span class="badge badge-${(item?.authority || 'Partial') === 'Full' ? 'approved' : 'info'}">
            ${escapeHtml(item?.authority || 'Partial')}
          </span>
        </td>
        <td>
          <span style="font-family:monospace;font-weight:700;color:var(--primary);">
            ${escapeHtml(item?.temp_id || item?.tempId || 'Pending')}
          </span>
        </td>
        <td class="actions">
          <button class="btn btn-info btn-sm" onclick="showEnrolForm('representative', representativesData[${i}])"><i class="fas fa-edit"></i></button>
          <button class="btn btn-danger btn-sm" onclick="deletePerson('${escapeHtml(item?.id || '')}', 'representative')"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
  }).join('');
}

// ---- TEMP ID CARDS ----
function loadAllPersonsForTempID() {
  const appId = getAppId();
  
  // Check if application is selected
  if (!appId) {
    const screen = document.getElementById('screen-temp-id');
    if (screen) {
      screen.innerHTML = `
        <div class="page-header">
          <div>
            <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Temporary ID Cards</span></div>
            <div class="page-title">Temporary ID Card Generation</div>
            <div class="page-subtitle">No application selected</div>
          </div>
        </div>
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i>
          <div><strong>No Application Selected</strong><br>Please submit Contractor Registration first to enrol personnel and generate temporary IDs.</div>
        </div>
        <div style="text-align:center;padding:40px;">
          <button class="btn btn-primary" onclick="navigate('annexure2a')">
            <i class="fas fa-file-alt"></i> Submit Contractor Registration
          </button>
        </div>
      `;
    }
    return;
  }
  
  Promise.all([
    apiFetch('get_representatives.php?application_id=' + encodeURIComponent(appId), { method: 'GET' }),
    apiFetch('get_supervisors.php?application_id=' + encodeURIComponent(appId), { method: 'GET' }),
    apiFetch('get_workmen.php?application_id=' + encodeURIComponent(appId), { method: 'GET' })
  ])
  .then(([rep, sup, work]) => {
    const repData = (rep && rep.success && Array.isArray(rep.data)) ? rep.data : (Array.isArray(rep) ? rep : []);
    const supData = (sup && sup.success && Array.isArray(sup.data)) ? sup.data : (Array.isArray(sup) ? sup : []);
    const workData = (work && work.success && Array.isArray(work.data)) ? work.data : (Array.isArray(work) ? work : []);
    const allPersons = [
      ...repData.map(r => ({...r, type: 'Representative', role: r.designation})),
      ...supData.map(s => ({...s, type: 'Supervisor', role: s.designation})),
      ...workData.map(w => ({...w, type: 'Workman', role: w.role || 'Worker'}))
    ];
    renderTempID(allPersons);
  })
  .catch(err => {
    console.error("TEMP ID LOAD ERROR:", err);
    const screen = document.getElementById('screen-temp-id');
    if (screen) {
      screen.innerHTML = `
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i>
          <div><strong>Error loading personnel:</strong> ${err.message}</div>
        </div>
      `;
    }
  });
}

function renderTempID(allPersons = []) {
  const generateTempId = (p, index) => p.tempId || `TID-${p.type[0]}${p.id || (index + 1).toString().padStart(3, '0')}`;
  
  const today = new Date();
  const expiry = new Date(today);
  expiry.setDate(today.getDate() + 30);

  document.getElementById('screen-temp-id').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Temporary ID Cards</span></div>
        <div class="page-title">Temporary ID Card Generation</div>
        <div class="page-subtitle">System-generated unique temporary identification cards for all enrolled personnel</div>
      </div>
      <div class="btn-group">
        <button class="btn btn-secondary btn-sm" onclick="showToast('🖨️','Bulk printing initiated for all temp IDs')"><i class="fas fa-print"></i> Print All</button>
        <button class="btn btn-primary btn-sm" onclick="showToast('📥','All ID cards exported as PDF')"><i class="fas fa-download"></i> Export PDF</button>
      </div>
    </div>

    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i>
      <div><strong>Temporary ID Cards</strong> are valid for <strong>30 days</strong> from the date of issue. Permanent gate passes will be issued after successful safety training and final approval.</div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;margin-top:8px;">
${allPersons.map((p, i) => `
        <div>
          <div class="id-card">
            <div class="id-card-header">
              <span class="id-card-logo">🏗️</span>
              <div>
                <div class="id-card-org">Public Works Department</div>
                <div class="id-card-sub">Contractor Management System</div>
              </div>
              <div class="id-card-type">TEMP ID</div>
            </div>
            <div class="id-card-body">
              <div class="id-card-photo"><i class="fas fa-user-circle"></i></div>
              <div class="id-card-details">
                <div class="id-card-name">${p.name}</div>
                <div class="id-card-designation">${p.role} &nbsp;|&nbsp; <span style="color:var(--info);">${p.type}</span></div>
                <div class="id-card-row"><span class="id-card-key">ID No:</span><span class="id-card-val" style="color:var(--primary);font-family:monospace;">${p.tempId || generateTempId(p, i)}</span></div>
                <div class="id-card-row"><span class="id-card-key">Aadhar:</span><span class="id-card-val">${p.aadhar}</span></div>
                <div class="id-card-row"><span class="id-card-key">Contractor:</span><span class="id-card-val">Sharma Const. Ltd.</span></div>
                <div class="id-card-row"><span class="id-card-key">Project:</span><span class="id-card-val">NH-48 Flyover Ph.II</span></div>
              </div>
            </div>
            <div class="id-card-footer">
              <div class="id-card-qr"><i class="fas fa-qrcode" style="font-size:20px;color:#333;"></i></div>
              <div class="id-card-validity">
                <div>Valid From: ${today.toLocaleDateString()}</div>
                <div style="color:var(--accent);">Valid Till: ${expiry.toLocaleDateString()}</div>
              </div>
              <div style="text-align:right;font-size:9px;opacity:.8;">NOT FOR PERMANENT USE</div>
            </div>
          </div>
          <div class="btn-group" style="margin-top:8px;justify-content:center;">
            <button class="btn btn-light btn-sm" onclick="showToast('🖨️','Printing ${p.name}')"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-secondary btn-sm" onclick="showToast('📥','Downloading ${p.name} ID')"><i class="fas fa-download"></i> Download</button>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

// ---- SAFETY TRAINING (DYNAMIC) ----
let trainingSessionsCache = null;
let trainingSessionsLoading = false;

window.showTrainingRequestForm = function () {
  console.log("[UI] Training Request Form triggered");
  window.showToast && window.showToast('Training request form coming soon', 'info');
};

window.showNewSessionForm = function () {
  console.log("[UI] New Session Form triggered");
  window.showToast && window.showToast('New session form coming soon', 'info');
};

async function fetchTrainingSessions() {
  if (trainingSessionsCache && !trainingSessionsLoading) {
    return trainingSessionsCache;
  }
  
  try {
    trainingSessionsLoading = true;
    const parsed = await apiFetch('get_training_sessions.php');
    console.log('[fetchTrainingSessions] actual response structure:', parsed);
    
    const sessions = normalizeArray(parsed);
    console.log('[fetchTrainingSessions] list:', sessions);
    trainingSessionsCache = sessions;
    return sessions;
  } catch (error) {
    console.error('Fetch training sessions error:', error);
    return [];
  } finally {
    trainingSessionsLoading = false;
  }
}

// ---- SAFETY TRAINING (DYNAMIC VERSION) ----
async function renderSafetyTraining(role) {
  const screen = document.getElementById('screen-safety-training');
  if (!screen) return;
  screen.innerHTML = '';
  screen.innerHTML = `
    <div class="loading-overlay">
      <div class="spinner-border"></div>
      <div>Loading training sessions...</div>
    </div>
  `;

  try {
    const list = await fetchTrainingSessions();
    const sessions = list || [];
    
    screen.innerHTML = `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Safety Training</span></div>
        <div class="page-title">Safety Training Management (${sessions.length} sessions)</div>
          <div class="page-subtitle">${role === 'contractor' ? 'Request and track safety training for enrolled workmen' : role === 'safety' ? 'Manage training sessions and results' : 'Approve safety training requests'}</div>
        </div>
        ${role === 'contractor' ? `<button class="btn btn-primary" id="btnTrainingRequest"><i class="fas fa-paper-plane"></i> Request Training</button>` : ''}
        ${role === 'safety' ? `<button class="btn btn-primary" id="btnNewSession"><i class="fas fa-plus"></i> Schedule Session</button>` : ''}
      </div>

      ${sessions.length === 0 ? `
      <div style="text-align:center;padding:60px;">
        <i class="fas fa-calendar-check" style="font-size:64px;color:var(--success);"></i>
        <div style="margin-top:16px;font-size:18px;font-weight:600;">No Data Found</div>
        <div style="margin-top:8px;font-size:14px;color:var(--text-mid);">Contact Safety Officer to schedule training</div>
      </div>
      ` : `
      <div class="card">
        <div class="card-title"><i class="fas fa-calendar-alt"></i> Training Sessions</div>
        ${sessions.map(t => `
          <div style="border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
              <div>
                <div style="font-weight:800;">${escapeHtml(t.venue || t.location || 'Training Venue')} - ${escapeHtml(t.date || t.session_date || '-')} ${escapeHtml(t.time || t.session_time || '')}</div>
                <div style="margin-top:4px;font-size:13px;color:var(--text-mid);">Trainer: ${escapeHtml(t.trainer || t.trainer_name || '-')}</div>
              </div>
              <span class="badge badge-info">${escapeHtml(t.enrolled ?? t.enrolled_count ?? 0)}/${escapeHtml(t.capacity ?? '-')} enrolled</span>
            </div>
            <button class="btn btn-primary btn-sm mt-2">Enrol/View</button>
          </div>`).join('')}
      </div>
      `}
    `;

    document.getElementById('btnTrainingRequest')?.addEventListener('click', window.showTrainingRequestForm);
    document.getElementById('btnNewSession')?.addEventListener('click', window.showNewSessionForm);
  } catch (error) {
    console.error('Training load error:', error);
    screen.innerHTML = `
      <div class="page-header">
        <div class="page-title">Safety Training Management</div>
        <div class="page-subtitle">Unable to load training sessions</div>
      </div>
      <div style="text-align:center;padding:60px;">
        <i class="fas fa-exclamation-triangle" style="font-size:48px;color:var(--warning);"></i>
        <div style="margin-top:16px;font-size:16px;">Unable to load training data</div>
        <div style="margin-top:8px;font-size:13px;color:var(--text-mid);">Please try again later</div>
      </div>
    `;
  }
}


// ---- PAYMENT PWO ----
function renderPayment() {
  document.getElementById('screen-payment').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Application Fee Payment</span></div>
        <div class="page-title">Application Fee Payment (PWO)</div>
        <div class="page-subtitle">Payment gateway integration for contractor application fee</div>
      </div>
    </div>

    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      <div><strong>Payment Successful!</strong> Transaction ID: TXN-2024-7291 &nbsp;|&nbsp; Receipt No: RCP-2024-7291 &nbsp;|&nbsp; Date: 01 Apr 2026</div>
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="card-title"><i class="fas fa-receipt"></i> Fee Details & Payment Status</div>
        <div style="background:var(--bg);border-radius:10px;padding:16px;">
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:13px;color:var(--text-mid);">Application Fee (PWO)</span>
            <strong>₹ 2,000.00</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:13px;color:var(--text-mid);">Processing Fee</span>
            <strong>₹ 350.00</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
            <span style="font-size:13px;color:var(--text-mid);">GST (18%)</span>
            <strong>₹ 150.00</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:15px;">
            <strong>Total Amount Paid</strong>
            <strong style="color:var(--success);">₹ 2,500.00</strong>
          </div>
        </div>
        <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
          ${[
            ['Payment Mode','Online – UPI'],
            ['Transaction ID','TXN-2024-7291'],
            ['Receipt Number','RCP-2024-7291'],
            ['Payment Date','01 Apr 2026, 11:32 AM'],
            ['Status','<span class="badge badge-completed">Paid</span>'],
          ].map(([l,v]) => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);">
              <span style="font-size:12px;color:var(--text-light);">${l}</span>
              <span style="font-size:13px;font-weight:600;">${v}</span>
            </div>`).join('')}
        </div>
        <div class="btn-group" style="margin-top:14px;">
          <button class="btn btn-primary btn-sm" onclick="showToast('📥','Payment receipt downloaded')"><i class="fas fa-download"></i> Download Receipt</button>
          <button class="btn btn-secondary btn-sm" onclick="showToast('📧','Receipt sent to registered email')"><i class="fas fa-envelope"></i> Email Receipt</button>
        </div>
      </div>

      <div class="card">
        <div class="card-title"><i class="fas fa-credit-card"></i> Payment Gateway</div>
        <div style="background:linear-gradient(135deg,var(--primary-dark),var(--primary-light));color:#fff;border-radius:12px;padding:20px;margin-bottom:16px;">
          <div style="font-size:12px;opacity:0.8;margin-bottom:14px;">CONTRACTOR APPLICATION FEE</div>
          <div style="font-size:28px;font-weight:800;">₹ 2,500.00</div>
          <div style="margin-top:10px;font-size:12px;opacity:0.9;">CMS-2024-001 &nbsp;|&nbsp; M/s Sharma Construction Ltd.</div>
          <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:6px 12px;font-size:11px;font-weight:700;">✅ PAID</div>
            <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:6px 12px;font-size:11px;">01 Apr 2026</div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--border);border-radius:8px;opacity:0.7;">
            <i class="fas fa-mobile-alt" style="color:var(--success);font-size:18px;"></i>
            <div><div style="font-size:13px;font-weight:700;">UPI Payment</div><div style="font-size:11px;color:var(--text-mid);">Pay via any UPI app</div></div>
            <div style="margin-left:auto;font-size:11px;color:var(--success);">✓ Used</div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--border);border-radius:8px;opacity:0.5;">
            <i class="fas fa-university" style="color:var(--info);font-size:18px;"></i>
            <div><div style="font-size:13px;font-weight:700;">Net Banking</div><div style="font-size:11px;color:var(--text-mid);">All major banks</div></div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--border);border-radius:8px;opacity:0.5;">
            <i class="fas fa-credit-card" style="color:var(--warning);font-size:18px;"></i>
            <div><div style="font-size:13px;font-weight:700;">Credit / Debit Card</div><div style="font-size:11px;color:var(--text-mid);">Visa, Mastercard, RuPay</div></div>
          </div>
        </div>
      </div>
    </div>
  `;
}

// ---- TRAINING RESULT (DYNAMIC) ----
async function loadTrainingResults(sessionId, callback) {
  try {
    const res = await apiFetch(`get_training_results.php?session_id=${encodeURIComponent(sessionId)}`);
    console.log('[loadTrainingResults] response:', res);
    APP_DATA.trainingResults = normalizeArray(res);
  } catch (error) {
    console.error('No training results found', error);
    APP_DATA.trainingResults = [];
  }
  if (callback) callback();
}

function renderTrainingResult() {
  const screen = document.getElementById('screen-training-result');
  if (!screen) return;
  screen.innerHTML = '';
  const list = APP_DATA.trainingResults || [];
  screen.innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Training Results</span></div>
        <div class="page-title">Safety Training Result Processing</div>
        <div class="page-subtitle">View and manage safety training qualification results</div>
      </div>
      <button class="btn btn-primary btn-sm" onclick="showToast('📊','Result report exported successfully')"><i class="fas fa-download"></i> Export Results</button>
    </div>

    <div class="alert alert-success">
      <i class="fas fa-graduation-cap"></i>
      <div><strong>Your Results Loaded from DB:</strong> ${list.length} trainees found.</div>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-users"></i></div><div><div class="stat-value">${list.length}</div><div class="stat-label">Total Trainees</div></div></div>
      <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div><div class="stat-value">${list.filter(r => (r.result || '').toLowerCase() === 'qualified').length}</div><div class="stat-label">Qualified</div></div></div>
      <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times"></i></div><div><div class="stat-value">${list.filter(r => (r.result || '').toLowerCase() === 'failed').length}</div><div class="stat-label">Failed</div></div></div>
      <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-redo"></i></div><div><div class="stat-value">${list.filter(r => (r.result || '').toLowerCase() === 'pending').length}</div><div class="stat-label">Pending</div></div></div>
    </div>

    <div class="card">
      <div class="card-title"><i class="fas fa-list"></i> Individual Results (Live from DB)</div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Attendance</th><th>Written Test</th><th>Practical</th><th>Total</th><th>Result</th><th>Certificate</th></tr></thead>
          <tbody>
            ${list.map((r, i) => `
              <tr>
                <td>${i+1}</td>
                <td><strong>${escapeHtml(r.name || r.workman_name || '-')}</strong></td>
                <td>${escapeHtml(r.role || r.trade || '-')}</td>
                <td style="text-align:center;">${escapeHtml(r.attendance || r.attendance_status || '-')}</td>
                <td style="text-align:center;">${escapeHtml(r.written || r.theory_score || '-')}</td>
                <td style="text-align:center;">${escapeHtml(r.practical || r.practical_score || '-')}</td>
                <td style="text-align:center;font-weight:700;">${escapeHtml(r.total || r.overall_score || '-')}</td>
                <td>
                  <span class="badge badge-${
                    r.result === 'Qualified' ? 'approved' :
                    r.result === 'Failed' ? 'rejected' : 'pending'
                  }">${escapeHtml(r.result || 'Pending')}</span>
                </td>
                <td>
                  ${r.result === 'Qualified' 
                    ? `<button class="btn btn-success btn-sm" onclick="showToast('📜','Certificate for ${r.name}')"><i class="fas fa-certificate"></i></button>`
                    : r.result === 'Failed'
                    ? `<button class="btn btn-warning btn-sm" onclick="showToast('📅','Retake for ${r.name}')"><i class="fas fa-redo"></i></button>`
                    : `<button class="btn btn-light btn-sm"><i class="fas fa-calendar"></i> Schedule</button>`
                  }
                </td>
              </tr>
            `).join('') || '<tr><td colspan="9" style="text-align:center;padding:40px;"><i class="fas fa-inbox" style="font-size:48px;color:var(--text-light);"></i><br><strong>No Data Found</strong><br><small>Results will appear here when available</small></td></tr>'}
          </tbody>
        </table>
      </div>
    </div>

    <div class="btn-group">
      <button class="btn btn-secondary" onclick="navigate('safety-training')"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="btn btn-primary" onclick="navigate('gatepass-request', window.currentAppId || 'CMS-2024-001')"><i class="fas fa-door-open"></i> Gate Pass Request</button>
    </div>
  `;
}


// ---- GATE PASS REQUEST (ANNEXURE 6A) ----
let _gatePassRequestLoading = false;

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[char]));
}

window.loadGatePassPersonnel = async function () {
  if (window._gatePassPersonnelLoading) return;

  window._gatePassPersonnelLoading = true;
  window.APP_DATA = window.APP_DATA || {};

  console.log("[API] Loading Gate Pass Personnel...");

  try {
    const response = await apiFetch('get_gatepass_personnel.php');
    console.log("[API] Gate Pass Personnel response:", response);
    window.APP_DATA.personnel = normalizeArray(response);
  } catch (error) {
    console.error("Failed to load personnel", error);
    window.APP_DATA.personnel = [];
  } finally {
    console.log("[APP_DATA.personnel]", window.APP_DATA.personnel);
    window._gatePassPersonnelLoading = false;
  }
};

function renderVehicleForm() {
  const appId = window.currentAppId || 'CMS-2024-001';
  return `
    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-car"></i> Section C - Vehicle Details (If Applicable)</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Vehicle Registration No.</label><input id="vehicleNumber" class="form-control" placeholder="HR-XX-XXXX"/></div>
        <div class="form-group"><label class="form-label">Vehicle Type</label><input id="vehicleType" class="form-control" placeholder="Vehicle Type"/></div>
        <div class="form-group"><label class="form-label">Driver Name</label><input id="driverName" class="form-control" placeholder="Driver name"/></div>
        <div class="form-group"><label class="form-label">Application ID</label><input id="vehicleApplicationId" class="form-control readonly" value="${escapeHtml(appId)}" readonly/></div>
      </div>
      <div class="btn-group" style="margin-top:12px;">
        <button class="btn btn-primary" onclick="submitVehicleDetails()"><i class="fas fa-save"></i> Submit Vehicle Details</button>
      </div>
    </div>
  `;
}

window.submitVehicleDetails = async function () {
  const appId = window.currentAppId || document.getElementById('vehicleApplicationId')?.value || 'CMS-2024-001';
  
  const vehicle_number = document.getElementById('vehicleNumber')?.value.trim() || '';
  const vehicle_type = document.getElementById('vehicleType')?.value.trim() || '';
  const driver_name = document.getElementById('driverName')?.value.trim() || '';

  if (!vehicle_number || !vehicle_type || !driver_name) {
    window.showToast ? window.showToast('All vehicle fields are required', 'warning') : alert("All vehicle fields are required");
    return;
  }

  const data = {
    vehicle_number,
    vehicle_type,
    driver_name,
    application_id: appId
  };

  console.log("[SUBMIT VEHICLE]", data);

  try {
    const res = await apiFetch('insert_vehicle_details.php', {
      method: 'POST',
      body: data // Pass object directly, apiFetch will handle stringification
    });

    console.log("[SUBMIT VEHICLE RESPONSE]", res);
    if (res.success) {
      window.showToast ? window.showToast(res.message || 'Vehicle added successfully', 'success') : alert("Vehicle added successfully");
    } else {
      window.showToast ? window.showToast(res.error || 'Failed to add vehicle', 'error') : alert("Failed to add vehicle");
    }
  } catch (error) {
    console.error("[SUBMIT VEHICLE ERROR]", error);
    window.showToast ? window.showToast(error.message || 'Connection error', 'error') : alert("Connection error: " + error.message);
  }
};

async function renderGatePassRequest() {
  if (_gatePassRequestLoading) {
    console.log('[renderGatePassRequest] Already loading, skipping duplicate call');
    return;
  }
  _gatePassRequestLoading = true;

  const el = document.getElementById('screen-gatepass-request');
  if (!el) return;
  el.innerHTML = '';
  if (!window.currentAppId) {
    console.warn("Missing application ID");
    window.currentAppId = 'CMS-2024-001';
  }
  console.log("[renderGatePassRequest] currentAppId:", window.currentAppId);
  el.innerHTML = '<div class="loading-overlay"><div class="spinner-border"></div><div>Loading gate pass personnel...</div></div>';

  await window.loadGatePassPersonnel();
  const personnel = APP_DATA.personnel || [];
  console.log("[renderGatePassRequest] personnel:", personnel);

  el.innerHTML = `

    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Gate Pass Request</span></div>
        <div class="page-title">Annexure 6/A – Gate Pass Request</div>
        <div class="page-subtitle">Request for site access gate passes for qualified personnel</div>
      </div>
      <span class="badge badge-submitted" style="font-size:13px;padding:6px 14px;"><i class="fas fa-paper-plane"></i> Submitted – Under Verification</span>
    </div>

    <div class="stepper">
      <div class="step completed"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">Request Submitted</div></div>
      <div class="step active"><div class="step-circle">2</div><div class="step-label">PIO Verification</div></div>
      <div class="step"><div class="step-circle">3</div><div class="step-label">Welfare Approval</div></div>
      <div class="step"><div class="step-circle">4</div><div class="step-label">Temp Pass</div></div>
      <div class="step"><div class="step-circle">5</div><div class="step-label">ACC Approval</div></div>
      <div class="step"><div class="step-circle">6</div><div class="step-label">Permanent Pass</div></div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-building"></i> Section A – Contractor Details (Auto-filled)</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Contractor Name</label><input class="form-control readonly" value="M/s Sharma Construction Ltd." readonly/></div>
        <div class="form-group"><label class="form-label">Application ID</label><input class="form-control readonly" value="CMS-2024-001" readonly/></div>
        <div class="form-group"><label class="form-label">Work Order No.</label><input class="form-control readonly" value="WO/2024/PWD/0423" readonly/></div>
        <div class="form-group"><label class="form-label">Project</label><input class="form-control readonly" value="NH-48 Flyover – Phase II" readonly/></div>
        <div class="form-group"><label class="form-label">Site Location</label><input class="form-control readonly" value="Sector 22, Gurugram" readonly/></div>
        <div class="form-group"><label class="form-label">Gate Pass Type</label><select class="form-control"><option selected>Temporary (30 days)</option><option>Permanent</option></select></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-users"></i> Section B – Personnel Requesting Gate Pass</div>
      <div class="alert alert-info" style="margin-bottom:14px;">
        <i class="fas fa-info-circle"></i>
        <div>Only personnel who have <strong>qualified the safety training</strong> are eligible for gate pass. Absent/Failed trainees are excluded automatically.</div>
      </div>
      <div class="table-wrap">
        <table class="data-table" id="screenGpTable">
          <thead><tr><th><input type="checkbox" onchange="selectAllGP()" /></th><th>Name</th><th>Temp ID</th><th>Role</th><th>Training</th><th>Access Zone</th><th>Access Hours</th></tr></thead>
          <tbody id="screenGpTableBody">
            ${personnel.length === 0 ? `
              <tr><td colspan="7"><div class="empty-state" style="text-align:center;padding:60px;color:var(--text-mid);"><i class="fas fa-users-slash" style="font-size:48px;"></i><br><strong>No Data Found</strong><br><small>No workmen/supervisors with qualified safety training. Complete safety training first.</small></div></td></tr>
            ` : personnel.map(p => `
              <tr>
                <td><input type="checkbox" checked data-id="${escapeHtml(p.id)}"></td>
                <td><strong>${escapeHtml(p.name)}</strong></td>
                <td><span style="font-family:monospace;font-weight:700;color:var(--primary);">${escapeHtml(p.temp_id || p.tempId || p.temp_id_no || '-')}</span></td>
                <td>${escapeHtml(p.role || p.trade || p.designation || '-')}</td>
                <td><span class="badge badge-success">${escapeHtml(p.training || p.result || 'Qualified')}</span></td>
                <td>
                  <select class="form-control" style="font-size:12px;padding:4px 8px;" data-zone="${escapeHtml(p.id)}">
                    <option>Zone A & B</option>
                    <option>Zone A only</option>
                    <option>Zone B only</option>
                    <option>All Zones</option>
                  </select>
                </td>
                <td>
                  <select class="form-control" style="font-size:12px;padding:4px 8px;" data-hours="${escapeHtml(p.id)}">
                    <option>06:00-22:00</option>
                    <option>24x7</option>
                    <option>Day Only</option>
                    <option>Night Only</option>
                  </select>
                </td>
              </tr>
            `).join('')}
          </tbody>

        </table>
        <div id="screenGpTableFooter" style="padding:12px 16px;background:var(--gray-50);border-top:1px solid var(--gray-100);font-size:12px;color:var(--gray-500)">
          ${personnel.length ? `Showing ${personnel.length} qualified personnel from database. Only training-qualified personnel shown.` : 'No Data Found'}
        </div>
      </div>
    </div>


    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-car"></i> Section C – Vehicle Details (If Applicable)</div>
      <div class="form-grid">
        <div class="form-group"><label class="form-label">Vehicle Registration No.</label><input id="vehicleNumber" class="form-control" placeholder="HR-XX-XXXX"/></div>
        <div class="form-group"><label class="form-label">Vehicle Type</label><input id="vehicleType" class="form-control" placeholder="Vehicle Type"/></div>
        <div class="form-group"><label class="form-label">Driver Name</label><input id="driverName" class="form-control" placeholder="Driver name"/></div>
        <div class="form-group"><label class="form-label">Application ID</label><input id="vehicleApplicationId" class="form-control readonly" value="${escapeHtml(window.currentAppId || 'CMS-2024-001')}" readonly/></div>
      </div>
      <div class="btn-group" style="margin-top:12px;">
        <button class="btn btn-primary" onclick="submitVehicleDetails()"><i class="fas fa-save"></i> Submit Vehicle Details</button>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section-title"><i class="fas fa-file-signature"></i> Section D – Declaration</div>
      <div style="background:var(--bg);border-radius:8px;padding:14px;font-size:13px;color:var(--text-mid);margin-bottom:12px;line-height:1.8;">
        I/We hereby declare that all persons listed in this request have undergone mandatory safety training and are qualified to work on site. I/We undertake responsibility for their conduct and compliance with site rules.
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" id="decl6a" checked/>
        <label for="decl6a" style="font-size:13px;cursor:pointer;">I agree to the above declaration</label>
      </div>
    </div>

    <div class="btn-group">
      <button class="btn btn-secondary" onclick="navigate('training-result')"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="btn btn-light"><i class="fas fa-save"></i> Save Draft</button>
      <button class="btn btn-primary" onclick="submitAndToast('Gate Pass request submitted! Pass Issuing Officer has been notified.','pass-officer')"><i class="fas fa-paper-plane"></i> Submit Gate Pass Request</button>
    </div>
  `;

  _gatePassRequestLoading = false;
}

function selectAllGP() {
  const checked = document.querySelector('input[onchange*="selectAllGP()"]').checked;
  document.querySelectorAll('#screenGpTable input[type="checkbox"]').forEach(cb => cb.checked = checked);
}


// ---- PASS ISSUING OFFICER ----
let passOfficerData = [];
async function loadPassOfficerData(callback) {
  console.log('[loadPassOfficerData] 🔄 Loading PIO data...');
  const parsed = await apiFetch('get_pass_officer_data.php');
  
  console.log('[loadPassOfficerData] API response:', parsed);
  
  // FIX: API returns flat structure { success, data: [...] } but also handle object case
  if (parsed.success && parsed.data) {
    passOfficerData = Array.isArray(parsed.data) ? parsed.data : Object.values(parsed.data);
    console.log('[loadPassOfficerData] ✅ length:', passOfficerData.length);
    if (callback) callback();
  } else {
    console.error('[loadPassOfficerData] ❌ No data array', parsed);
    passOfficerData = [];
    if (callback) callback();
  }
}

function groupByApplication(data) {
  const grouped = {};
  data.forEach(item => {
    const key = `${item.application_id}_${item.pass_ref}`;
    if (!grouped[key]) {
      grouped[key] = {
        key,
        application_id: item.application_id,
        pass_ref: item.pass_ref,
        issued_at: item.issued_at,
        status: item.status,
        approval_level: item.approval_level || 1,
        gate_location: item.gate_location,
        shift_type: item.shift_type,
        valid_from: item.valid_from,
        valid_to: item.valid_to,
        persons: []
      };
    }
    grouped[key].persons.push({
      workman_id: item.workman_id,
      name: item.name || '-',
      trade: item.trade || '-',
      result: item.result || 'pending',
      certificate_no: item.certificate_no || '-',
      attendance_status: item.attendance_status || 'pending',
      pio_status: item.pio_status || 'pending'
    });
  });
  return Object.values(grouped);
}

function canForward(persons) {
  return persons.every(p => p.pio_status === 'approved');
}

async function updatePIO(id, action) {
    const res = await apiFetch('update_pio_status.php', {
        application_id: id,
        action: action
    });

    if(res.success){
        alert("Updated");
        location.reload();
    } else {
        alert(res.message);
    }
}

let groupedData = []; // Global for forwardToWelfare

async function forwardToWelfare(app_key) {
  const appData = groupedData.find(g => g.key === app_key);
  if (!appData) return;
  
  const formData = new FormData();
  formData.append('application_id', appData.application_id);
  
  const parsed = await apiFetch('welfare/forward_to_welfare.php', {
    method: 'POST',
    body: formData
  });
  
  if (parsed.success && parsed.data && parsed.data.success) {
    showToast('🚀', parsed.data.message || 'Forwarded');
    loadPassOfficerData(renderPassOfficerTable);
  } else {
    const msg = parsed.data?.error || parsed.error || 'Forward failed';
    console.error('Forward error:', msg, parsed);
    showToast('❌', msg);
  }
}

function renderPassOfficerTable() {
  console.log('🧮 Grouping data, raw length:', passOfficerData.length);
  groupedData = groupByApplication(passOfficerData || []);
  console.log('📊 GROUPED data:', groupedData);
  console.log('📊 Number of groups:', groupedData.length);
  const el = document.getElementById('screen-pass-officer');
  
  el.innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Pass Issuing Officer</span></div>
        <div class="page-title">PIO Verification Queue (${groupedData.length} applications)</div>
        <div class="page-subtitle">Review training results and approve/reject individual workmen before forwarding</div>
      </div>
      <button class="btn btn-light btn-sm" onclick="loadPassOfficerData(renderPassOfficerTable)"><i class="fas fa-refresh"></i> Refresh</button>
    </div>

        ${!groupedData || groupedData.length === 0 ? `
      <div style="text-align:center;padding:80px;">
        <i class="fas fa-inbox" style="font-size:64px;color:var(--text-light);"></i>
        <div style="margin-top:20px;font-size:18px;font-weight:500;">No pending verifications</div>
        <div style="margin-top:8px;font-size:14px;color:var(--text-mid);">All applications processed or no new requests</div>
      </div>
    ` : groupedData.map(app => `
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
              <div style="font-size:18px;font-weight:800;">
                <i class="fas fa-id-badge" style="color:var(--primary);margin-right:8px;"></i>
                Application: ${app.application_id} | Pass Ref: ${app.pass_ref}
              </div>
              <div style="font-size:13px;color:var(--text-mid);margin-top:4px;">
                Issued: ${app.issued_at || 'N/A'} | 
                Location: ${app.gate_location || '-'} | 
                Shift: ${app.shift_type || '-'} | 
                Valid: ${app.valid_from || '-'} to ${app.valid_to || '-'}
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <span class="badge badge-${app.status === 'verified' ? 'verified' : 'pending'}">${app.status || 'pending'}</span>
              <span class="badge badge-info">${app.persons.length} persons</span>
              ${app.approval_level ? `<span class="badge badge-${app.approval_level >= 2 ? 'verified' : 'pending'}">Level ${app.approval_level}</span>` : ''}
              <button class="btn btn-success btn-sm ${canForward(app.persons) ? '' : 'btn-disabled'}" 
                      ${canForward(app.persons) ? `onclick="forwardToWelfare('${app.key}')" title="All persons must be approved"` : 'disabled title="Approve all persons first"'}>
                <i class="fas fa-forward"></i> ${canForward(app.persons) ? 'Forward' : 'Approve All First'}
              </button>
            </div>
          </div>
        </div>
        
        <div class="card-body" style="padding:0;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>ID</th>
                <th>Trade</th>
                <th>Training</th>
                <th>Attendance</th>
                <th>PIO Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              ${app.persons.map(p => `
                <tr>
                  <td><strong>${p.name}</strong></td>
                  <td>${p.workman_id}</td>
                  <td>${p.trade}</td>
                  <td>
                    ${p.result === 'qualified' ? '<span class="badge badge-success">✅ Qualified</span>' : 
                      p.result === 'failed' ? '<span class="badge badge-danger">❌ Failed</span>' : 
                      '<span class="badge badge-pending">⏳ Pending</span>'}
                  </td>
                  <td>
                    ${p.attendance_status === 'present' ? '<span class="badge badge-success">✅ Present</span>' : 
                      '<span class="badge badge-warning">❌ Absent</span>'}
                  </td>
                  <td>
                    <span class="badge badge-${
                      p.pio_status === 'approved' ? 'verified' :
                      p.pio_status === 'rejected' ? 'danger' : 'pending'
                    }">
                      ${p.pio_status?.toUpperCase() || 'PENDING'}
                    </span>
                  </td>
                  <td style="white-space:nowrap;">
                    <button class="btn btn-success btn-sm me-1 ${p.pio_status === 'rejected' ? 'disabled opacity-50' : ''}" onclick="if('${p.pio_status}' !== 'rejected') updatePIO('${app.application_id}', 'approved')" title="${p.pio_status === 'rejected' ? 'Rejected - Use Resubmit' : 'Approve'}">
                      <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-danger btn-sm ${p.pio_status === 'rejected' ? 'disabled opacity-50' : ''}" onclick="if('${p.pio_status}' !== 'rejected') updatePIO('${app.application_id}', 'rejected')" title="${p.pio_status === 'rejected' ? 'Rejected - Use Resubmit' : 'Reject'}">
                      <i class="fas fa-times"></i> Reject
                    </button>
                    ${p.pio_status === 'rejected' ? `<button class="btn btn-warning btn-sm" onclick="updatePIO('${app.application_id}', 'resubmit')"><i class="fas fa-redo"></i> Resubmit</button>` : ''}
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `).join('')}
  `;
}

function renderPassOfficer() {
  loadPassOfficerData(renderPassOfficerTable);
}

// ---- FINAL APPROVAL (Welfare + ACC) ----
let _finalApprovalLoading = false;

async function renderFinalApproval() {
  if (_finalApprovalLoading) {
    console.log('[renderFinalApproval] Already loading, skipping duplicate call');
    return;
  }
  _finalApprovalLoading = true;

  const el = document.getElementById('screen-final-approval');
  if (!el) return;
  el.innerHTML = '';
  el.innerHTML = '<div class="loading-overlay"><div class="spinner-border"></div><div>Loading final approval data...</div></div>';

  try {
    const parsed = await apiFetch('get_final_approval.php?tab=pending&limit=10');
    
    if (!parsed.success) throw new Error(parsed.error || 'API failed');
    
    console.log("[renderFinalApproval] API response:", parsed);
    
    // FIX: Handle nested data structure - API may return { success, data: { success, data: [...] } }
    let applications = [];
    if (parsed.data) {
      if (Array.isArray(parsed.data)) {
        applications = parsed.data;
      } else if (parsed.data.data && Array.isArray(parsed.data.data)) {
        applications = parsed.data.data;
      } else {
        applications = Object.values(parsed.data).filter(v => Array.isArray(v))[0] || [];
      }
    }
    const counts = parsed.counts || (parsed.data?.counts || {});
    const recentEvents = parsed.recent_events || (parsed.data?.recent_events || []);
    const currentApp = applications[0] || {};
    
    el.innerHTML = `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Final Approval</span></div>
          <div class="page-title">Final Approval Workflow</div>
          <div class="page-subtitle">${applications.length} applications | Current Step: ${currentApp.current_step || 0}/4 | Counts: ${Object.keys(counts).length}</div>
        </div>
        <div class="btn-group">
          <button class="btn btn-light btn-sm" onclick="renderFinalApproval()"><i class="fas fa-sync"></i> Refresh</button>
        </div>
      </div>

      <!-- FIXED STEPPER - Safe null checks -->
      <div class="stepper" style="margin-bottom:24px;">
        <div class="step ${currentApp.current_step >= 0 ? 'completed' : ''}"><div class="step-circle"><i class="fas fa-check"></i></div><div class="step-label">PIO Verified</div></div>
        <div class="step ${(currentApp.current_step || 0) >= 1 ? 'completed' : ''} ${(currentApp.current_step || 0) === 1 ? 'active' : ''}"><div class="step-circle">${(currentApp.current_step || 0) >= 1 ? '<i class="fas fa-check"></i>' : '2'}</div><div class="step-label">Welfare Officer</div></div>
        <div class="step ${(currentApp.current_step || 0) >= 2 ? 'completed' : ''}"><div class="step-circle">3</div><div class="step-label">Temp Pass Issued</div></div>
        <div class="step ${(currentApp.current_step || 0) >= 3 ? 'completed' : ''}"><div class="step-circle">4</div><div class="step-label">ACC Approval</div></div>
        <div class="step ${(currentApp.current_step || 0) >= 4 ? 'completed' : ''}"><div class="step-circle">5</div><div class="step-label">Permanent Pass</div></div>
      </div>

      <!-- PENDING APPLICATIONS TABLE -->
      <div class="card">
        <div class="card-title"><i class="fas fa-list"></i> Pending Final Approvals <span class="badge badge-info">${applications.length}</span></div>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>App ID</th><th>Contractor</th><th>Project</th><th>PIO Count</th><th>Current Step</th><th>Days</th><th>Priority</th><th>Action</th></tr></thead>
            <tbody>
              ${applications.length > 0 ? applications.map(app => `
                <tr>
                  <td><strong>${escapeHtml(app.application_id || app.ref_id || 'N/A')}</strong></td>
                  <td>${escapeHtml(app.contractor || app.contractor_name || 'N/A')} <small>(${escapeHtml(app.contractor_code || '')})</small></td>
                  <td>${escapeHtml(app.project || app.type || 'N/A')}</td>
                  <td style="text-align:center;">${app.gate_passes_count || 0}/${app.pio_approved_count || app.approved_gate_passes || 0}</td>
                  <td><span class="badge badge-${(app.current_step || 0) === 1 ? 'warning' : (app.current_step || 0) > 1 ? 'success' : 'pending'}">Step ${(app.current_step || 0)}</span></td>
                  <td>${escapeHtml(app.days_pending || 0)}</td>
                  <td><span class="badge badge-${escapeHtml(app.priority || 'low')}">${escapeHtml((app.priority || 'low').toUpperCase())}</span></td>
                  <td>
                    <button class="btn btn-success btn-sm final-approve-btn" data-id="${app.application_id || app.ref_id || ''}"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm final-reject-btn" data-id="${app.application_id || app.ref_id || ''}"><i class="fas fa-times"></i></button>
                  </td>
                </tr>
              `).join('') : '<tr><td colspan="8" style="text-align:center;padding:40px;"><i class="fas fa-inbox"></i><br><strong>No Data Found</strong><br><small>Check back later or contact administrator</small></td></tr>'}
            </tbody>
          </table>
        </div>
      </div>

        <!-- DYNAMIC APPROVAL CHAIN -->
        <div class="grid-2">
          ${(currentApp.application_id || currentApp.ref_id) ? `
        <!-- Current Application Chain -->
        <div class="card">
          <div class="card-title"><i class="fas fa-sitemap"></i> Current Application Chain (ID: ${currentApp.application_id || currentApp.ref_id})</div>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="padding:12px;background:var(--bg);border-radius:8px;">
              <div style="font-weight:600;">Welfare Verification: <span class="badge badge-${currentApp.welfare_verification === 'yes' ? 'success' : 'pending'}">${escapeHtml(currentApp.welfare_verification || 'pending')}</span></div>
              ${currentApp.welfare_approved_by ? `<div style="font-size:12px;color:var(--text-mid);">By: ${currentApp.welfare_approved_by} on ${new Date(currentApp.welfare_approved_at).toLocaleString()}</div>` : ''}
            </div>
            <div style="padding:12px;background:var(--bg);border-radius:8px;">
              <div style="font-weight:600;">PIO Approvals: <span class="badge badge-info">${currentApp.pio_approved_count || 0}/${currentApp.gate_passes_count || 0}</span></div>
            </div>
          </div>
        </div>` : ''}
        
        <!-- Stats -->
        <div class="card">
          <div class="card-title"><i class="fas fa-chart-bar"></i> Workflow Stats</div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            ${Object.entries(counts).map(([k,v]) => `
              <div style="display:flex;justify-content:space-between;">
                <span>${escapeHtml(k.replace('_',' ').toUpperCase())}</span>
                <strong>${escapeHtml(v)}</strong>
              </div>
            `).join('') || '<div style="text-align:center;padding:24px;color:var(--text-mid);">No Data Found</div>'}
          </div>
        </div>
      </div>

      <!-- DYNAMIC TIMELINE -->
      <div class="card">
        <div class="card-title"><i class="fas fa-history"></i> Recent Activity (${recentEvents.length})</div>
        <ul class="timeline">
          ${recentEvents.map(e => `
            <li class="timeline-item">
              <div class="timeline-dot ${e.action_type === 'approval' ? 'success' : e.action_type === 'rejection' ? 'danger' : 'info'}">
                <i class="fas fa-${e.action_type === 'approval' ? 'check' : e.action_type === 'rejection' ? 'times' : 'comment'}"></i>
              </div>
              <div class="timeline-body">
                <div class="timeline-title">${escapeHtml(e.remarks || e.remark || e.action_type || 'Activity')}</div>
                <div class="timeline-time">${e.created_at ? new Date(e.created_at).toLocaleString() : '-'} by ${escapeHtml(e.action_by || e.created_by || e.role || '-')}</div>
                <div class="timeline-desc">Application ${escapeHtml(e.application_id || 'N/A')}</div>
              </div>
            </li>
          `).join('') || '<li style="text-align:center;padding:40px;color:var(--text-mid);"><i class="fas fa-clock"></i><br>No Data Found</li>'}
        </ul>
      </div>
    `;
  } catch (error) {
    console.error('Final approval load error:', error);
    el.innerHTML = `
      <div class="alert alert-danger">
        <strong>Error:</strong> ${error.message}
        <button class="btn btn-primary btn-sm mt-2" onclick="renderFinalApproval()">Retry</button>
      </div>
    `;
  }
  _finalApprovalLoading = false;
}

// Final approval actions
async function finalApprove(appId) {
  if (!appId) { showToast('⚠️', 'No application ID'); return; }
  const remarks = prompt('Approval remarks:') || 'Approved';
  if (!confirm('Approve final application?')) return;
  
  try {
    const parsed = await apiFetch('update_final_status.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({application_id: appId, status: 'approved', remarks})
    });
    const result = parsed.data || {};
    if (parsed.success && result.success) {
      showToast('✅', 'Approved & forwarded!');
      renderFinalApproval();
    } else {
      showToast('❌', result.error || parsed.error || 'Update failed');
    }
  } catch (e) {
    showToast('❌', e.message || 'Network error');
  }
}

async function finalReject(appId) {
  if (!appId) { showToast('⚠️', 'No application ID'); return; }
  const reason = prompt('Rejection reason:');
  if (!reason) return;
  
  try {
    const parsed = await apiFetch('update_final_status.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({application_id: appId, status: 'rejected', remarks: reason})
    });
    const result = parsed.data || {};
    if (parsed.success && result.success) {
      showToast('❌', 'Rejected. Contractor notified.');
      renderFinalApproval();
    } else {
      showToast('❌', result.error || parsed.error || 'Update failed');
    }
  } catch (e) {
    showToast('❌', e.message || 'Network error');
  }
}


// ---- PERMANENT GATE PASS ----
function renderPermanentPass() {
  loadPermanentPasses();
}

async function loadPermanentPasses() {
  const el = document.getElementById('screen-permanent-pass');
  if (!el) return;
  el.innerHTML = '<div class="loading-overlay"><div class="spinner-border"></div><div>Loading permanent passes...</div></div>';

  const appId = getAppId();
  console.log("APP ID:", appId);

  if (!appId) {
    el.innerHTML = `
      <div class="page-header">
        <div>
          <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Permanent Gate Pass</span></div>
          <div class="page-title">Permanent Gate Pass Issuance</div>
          <div class="page-subtitle">No application selected</div>
        </div>
      </div>
      <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>No application selected</div></div>
    `;
    return;
  }

  const parsed = await apiFetch(`get_permanent_passes.php?application_id=${encodeURIComponent(appId)}`, { method: 'GET' });
  console.log('[loadPermanentPasses] API response:', parsed);

  const passes = parsed?.success ? (Array.isArray(parsed?.data) ? parsed.data : []) : [];
  console.log("PASSES:", passes);

  let passesHtml = '';
  if (passes.length === 0) {
      passesHtml = '<div class="alert alert-info"><i class="fas fa-info-circle"></i><div>No permanent gate passes found for this application. Passes are issued after training and final approval.</div></div>';
  } else {
      passesHtml = `
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px;">
        ${passes.map(p => `
          <div>
            <div class="gate-pass">
              <div class="gate-pass-header">
                <div style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:1px;">Permanent Gate Pass</div>
                <div style="font-size:11px;opacity:0.85;margin-top:2px;">Public Works Department – CMS</div>
              </div>
              <div class="gate-pass-body">
                <div style="display:flex;gap:14px;align-items:flex-start;">
                  <div style="width:64px;height:80px;background:#f0f4f8;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;border:2px solid var(--border);flex-shrink:0;">
                    <i class="fas fa-user" style="color:#94a3b8;"></i>
                  </div>
                  <div style="flex:1;">
                    <div style="font-size:16px;font-weight:800;color:var(--primary);">${p.worker_name}</div>
                    <div style="font-size:12px;color:var(--text-mid);">${p.trade}</div>
                    <div style="margin-top:8px;display:flex;flex-direction:column;gap:4px;">
                      <div class="id-card-row"><span class="id-card-key">Pass No:</span><span class="id-card-val" style="color:var(--primary);font-family:monospace;font-size:12px;">${p.pass_number}</span></div>
                      <div class="id-card-row"><span class="id-card-key">App ID:</span><span class="id-card-val">${p.application_id}</span></div>
                      <div class="id-card-row"><span class="id-card-key">Contractor:</span><span class="id-card-val">${p.contractor}</span></div>
                    </div>
                  </div>
                </div>
                <div class="gate-pass-stamp">
                  <div><div style="font-size:10px;font-weight:800;line-height:1.2;">PERMANENTLY</div><div style="font-size:9px;">APPROVED</div></div>
                </div>
                <div style="text-align:center;margin-top:8px;">
                  <div style="font-size:11px;color:var(--text-mid);">Valid: ${p.issue_date} – ${p.valid_till}</div>
                </div>
              </div>
              <div style="background:var(--primary);color:#fff;padding:8px 14px;display:flex;align-items:center;justify-content:space-between;font-size:10px;">
                <div>
                  <div>Issued by: System Admin</div>
                  <div style="opacity:.8;">ACC Verified</div>
                </div>
                <div><i class="fas fa-qrcode" style="font-size:28px;color:rgba(255,255,255,.8);"></i></div>
              </div>
            </div>
            <div class="btn-group" style="margin-top:10px;justify-content:center;">
              <button class="btn btn-light btn-sm" onclick="showToast('🖨️','Printing gate pass for ${p.worker_name}')"><i class="fas fa-print"></i> Print</button>
            </div>
          </div>`).join('')}
      </div>`;
  }

  el.innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>Permanent Gate Pass</span></div>
        <div class="page-title">Permanent Gate Pass Issuance</div>
        <div class="page-subtitle">Final issued permanent gate passes for qualified personnel</div>
      </div>
      <div class="btn-group">
        <button class="btn btn-secondary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print All</button>
      </div>
    </div>
    ${passesHtml}
    
    <div class="card" style="margin-top:24px;">
      <div class="card-title"><i class="fas fa-list"></i> Issuance Register</div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Pass No.</th><th>Name</th><th>Trade</th><th>Issued On</th><th>Valid Till</th><th>Status</th></tr></thead>
          <tbody>
            ${passes.length === 0 ? '<tr><td colspan="6" style="text-align:center;">No passes issued</td></tr>' : passes.map(pass => `
              <tr>
                <td><strong>${pass.pass_number}</strong></td>
                <td>${pass.worker_name}</td>
                <td>${pass.trade}</td>
                <td>${pass.issue_date}</td>
                <td>${pass.valid_till}</td>
                <td><span class="badge badge-${pass.status === 'active' ? 'active' : 'rejected'}">${pass.status}</span></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
    </div>
  `;
}

// ---- NOTIFICATIONS ----
// ---- NOTIFICATIONS (OBSOLETE - Use pages/notifications.php now)
function renderNotifications() {
  // Redirect to dedicated notifications page or load dynamic
  window.location.href = 'pages/notifications.php';
}

// ---- PROFILE SCREEN ----
function renderProfile() {
  const d = APP_DATA?.sapContractor || {};
  document.getElementById('screen-profile').innerHTML = `
    <div class="page-header">
      <div>
        <div class="breadcrumb"><a onclick="navigate('dashboard')">Dashboard</a><span class="sep">›</span><span>My Profile</span></div>
        <div class="page-title">Contractor Profile</div>
        <div class="page-subtitle">Manage your profile information and settings</div>
      </div>
      <button class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit Profile</button>
    </div>
    <div class="grid-2">
      <div class="card" style="text-align:center;padding:28px;">
        <div style="width:80px;height:80px;background:var(--primary);border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff;font-weight:800;">SC</div>
        <div style="font-size:18px;font-weight:800;color:var(--primary);">${d.name}</div>
        <div style="font-size:13px;color:var(--text-mid);margin-top:4px;">${d.type}</div>
        <div style="margin-top:10px;"><span class="badge badge-active">Active</span></div>
        <div class="separator"></div>
        <div style="font-size:13px;color:var(--text-mid);">${d.email}</div>
        <div style="font-size:13px;color:var(--text-mid);margin-top:4px;">${d.phone}</div>
        <div style="margin-top:14px;"><button class="btn btn-secondary btn-sm btn-block"><i class="fas fa-key"></i> Change Password</button></div>
        <div style="margin-top:8px;"><button class="btn btn-light btn-sm btn-block"><i class="fas fa-bell"></i> Notification Settings</button></div>
      </div>
      <div class="card">
        <div class="card-title"><i class="fas fa-info-circle"></i> Profile Details</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          ${[['Contractor Code',d.code],['Registration No.',d.regNo],['PAN Number',d.pan],['GSTIN',d.gstin],['Work Order',d.workOrder],['Project',d.project],['SAP Last Sync',d.sapSync]].map(([l,v])=>`
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
              <span style="font-size:12px;color:var(--text-light);">${l}</span>
              <strong style="font-size:13px;">${v}</strong>
            </div>`).join('')}
        </div>
      </div>
    </div>
  `;
}

