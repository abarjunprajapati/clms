<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Enrolment – Annexure 4/A</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-users"></i></div>
    <div>
      <div class="topbar-title">Annexure 4/A – Enrolment of Workmen</div>
      <div class="topbar-subtitle">Contractor Portal · Enrolment Module</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-notif"><i class="fas fa-bell" style="font-size:18px"></i><div class="notif-badge">2</div></div>
    <a href="contractor-dashboard.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-home"></i> Dashboard</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<!-- DYNAMIC PROGRESS TRACKER -->
<div class="page-container" style="padding-bottom:0">
  <div id="workflowProgressContainer"></div>
</div>

<div class="layout-wrapper">
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Enrolment</div>
      <a href="enrolment-4a.php" class="sidebar-item active"><i class="fas fa-users"></i> Enrolment 4/A</a>
      <a href="temp-id-card.php" class="sidebar-item"><i class="fas fa-id-card"></i> Temp ID Cards</a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Applications</div>
      <a href="contractor-dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="training_request.php" class="sidebar-item"><i class="fas fa-hard-hat"></i> Safety Training</a>
    </div>
  </div>

  <div class="main-content">
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      <div><strong>Application Approved!</strong> Your Contractor Registration & Customer Registration have been approved by the Welfare Authority. You can now enrol workmen, representatives, and supervisors.</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:var(--primary)"><i class="fas fa-user-hard-hat"></i></div>
        <div class="stat-value" id="approvedStrength">...</div><div class="stat-label">Approved Strength</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-user-check"></i></div>
        <div class="stat-value" id="enrolledCount">0</div><div class="stat-label">Enrolled</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-user-clock"></i></div>
        <div class="stat-value" id="pendingCount">...</div><div class="stat-label">Remaining</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-id-card"></i></div>
        <div class="stat-value" id="tempIdsIssued">0</div><div class="stat-label">Temp IDs Issued</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-users"></i> Enrolment Register — Annexure 4/A</div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary btn-sm" onclick="showModal('addWorkmanModal')"><i class="fas fa-plus"></i> Add Workman</button>
          <button class="btn btn-outline btn-sm"><i class="fas fa-file-excel"></i> Bulk Upload</button>
        </div>
      </div>
      <div class="card-body" style="padding:0">
        <div class="tabs" style="padding:0 16px;margin-bottom:0">
          <button class="tab-btn active" data-tab="tab-workmen" onclick="switchTab('tab-workmen')"><i class="fas fa-hard-hat"></i> Workmen</button>
          <button class="tab-btn" data-tab="tab-reps" onclick="switchTab('tab-reps')"><i class="fas fa-user-tie"></i> Representatives</button>
          <button class="tab-btn" data-tab="tab-supervisors" onclick="switchTab('tab-supervisors')"><i class="fas fa-user-cog"></i> Supervisors</button>
        </div>

        <!-- Workmen Tab -->
        <div class="tab-panel active" id="tab-workmen" style="padding:0">
          <div style="padding:12px 16px;display:flex;gap:8px;border-bottom:1px solid var(--gray-100)">
            <input class="form-control" style="width:220px" placeholder="Search by name, Aadhaar..." />
            <select class="form-control" style="width:150px">
              <option>All Status</option>
              <option>Active</option>
              <option>Temp ID Issued</option>
            </select>
          </div>
          <table class="data-table" id="workmenTable">
            <thead>
              <tr>
                <th>S.No.</th>
                <th>Name</th>
                <th>Father's Name</th>
                <th>Aadhaar</th>
                <th>Trade / Skill</th>
                <th>Mobile</th>
                <th>Enrolment Date</th>
                <th>Temp ID</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="workmenBody">
              <tr id="workmenLoading"><td colspan="9" style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading workmen...</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Representatives Tab -->
        <div class="tab-panel" id="tab-reps" style="padding:16px">
          <table class="data-table">
            <thead><tr><th>S.No.</th><th>Name</th><th>Father's Name</th><th>Aadhaar</th><th>Mobile</th><th>Temp ID</th><th>Action</th></tr></thead>
            <tbody id="repsBody">
              <tr id="repsLoading"><td colspan="7" style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading representatives...</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Supervisors Tab -->
        <div class="tab-panel" id="tab-supervisors" style="padding:16px">
          <table class="data-table">
            <thead><tr><th>S.No.</th><th>Name</th><th>Father's Name</th><th>Aadhaar</th><th>Mobile</th><th>Temp ID</th><th>Action</th></tr></thead>
            <tbody id="supervisorsBody">
              <tr id="supervisorsLoading"><td colspan="7" style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading supervisors...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Proceed Button -->
    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px">
      <a href="temp-id-card.php" class="btn btn-primary btn-lg">
        <i class="fas fa-id-card"></i> Proceed to Generate Temp ID Cards
      </a>
    </div>
  </div>
</div>

<!-- Add Workman Modal -->
<div id="addWorkmanModal" class="modal-overlay" style="display:none">
  <div class="modal" style="max-width:680px">
    <div class="modal-header">
      <h3 style="font-size:16px;font-weight:700"><i class="fas fa-user-plus" style="color:var(--primary)"></i> Enrol New Workman — Annexure 4/A</h3>
      <i class="fas fa-times" style="cursor:pointer" onclick="hideModal('addWorkmanModal')"></i>
    </div>
    <div class="modal-body">
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input class="form-control" id="wm-name" placeholder="Full name as per Aadhaar" />
        </div>
        <div class="form-group">
          <label class="form-label">Father's / Husband's Name <span class="required">*</span></label>
          <input class="form-control" id="wm-father-name" placeholder="Father/Husband name" />
        </div>
        <div class="form-group">
          <label class="form-label">Date of Birth <span class="required">*</span></label>
          <input class="form-control" type="date" id="wm-dob" />
        </div>
        <div class="form-group">
          <label class="form-label">Gender <span class="required">*</span></label>
          <select class="form-control" id="wm-gender">
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Aadhaar Number <span class="required">*</span></label>
          <input class="form-control" id="wm-aadhaar" placeholder="XXXX XXXX XXXX" maxlength="12" />
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number <span class="required">*</span></label>
          <input class="form-control" type="tel" id="wm-phone" placeholder="+91 XXXXX XXXXX" maxlength="10" />
        </div>
        <div class="form-group">
          <label class="form-label">Trade / Skill <span class="required">*</span></label>
          <select class="form-control" id="wm-role">
            <option value="Helper">Helper</option>
            <option value="Mason">Mason</option>
            <option value="Carpenter">Carpenter</option>
            <option value="Electrician">Electrician</option>
            <option value="Welder">Welder</option>
            <option value="Painter">Painter</option>
            <option value="Driver">Driver</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nationality</label>
          <select class="form-control">
            <option>Indian</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Permanent Address <span class="required">*</span></label>
          <input class="form-control" id="wm-address" placeholder="Full permanent address" />
        </div>
        <div class="form-group">
          <label class="form-label">State</label>
          <select class="form-control" id="wm-state">
            <option value="Maharashtra">Maharashtra</option>
            <option value="Uttar Pradesh">Uttar Pradesh</option>
            <option value="Bihar">Bihar</option>
            <option value="Rajasthan">Rajasthan</option>
            <option value="Gujarat">Gujarat</option>
            <option value="Madhya Pradesh">Madhya Pradesh</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
      <div class="section-divider"><span>Photo & Document</span></div>
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">Passport Photo <span class="required">*</span></label>
          <div class="upload-area" style="padding:12px" onclick="document.getElementById('wm-photo').click()">
            <i class="fas fa-camera" style="font-size:20px;margin-bottom:6px"></i>
            <div style="font-size:12px">Upload Photo</div>
            <div style="font-size:10px;color:var(--gray-400)">JPG/PNG · Max 1MB</div>
          </div>
          <input type="file" id="wm-photo" style="display:none" accept="image/*" />
        </div>
        <div class="form-group">
          <label class="form-label">Aadhaar Copy <span class="required">*</span></label>
          <div class="upload-area" style="padding:12px" onclick="document.getElementById('wm-aadhaar-doc').click()">
            <i class="fas fa-id-card" style="font-size:20px;margin-bottom:6px"></i>
            <div style="font-size:12px">Upload Aadhaar</div>
            <div style="font-size:10px;color:var(--gray-400)">PDF/JPG · Max 2MB</div>
          </div>
          <input type="file" id="wm-aadhaar-doc" style="display:none" />
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="hideModal('addWorkmanModal')">Cancel</button>
      <button class="btn btn-primary" id="enrolBtn" onclick="enrollWorkman()"><i class="fas fa-user-plus"></i> Enrol Workman</button>
    </div>
  </div>
</div>

<script src="../../js/utils.js"></script>
<script src="../../js/navigation.js"></script>
<script>
// =============================================
// ENROLMENT 4A - DYNAMIC DATA FETCH & SUBMIT
// =============================================

function maskAadhaar(aadhar) {
  if (!aadhar) return 'N/A';
  const s = String(aadhar);
  if (s.length < 4) return s;
  return 'XXXX XXXX ' + s.slice(-4);
}

async function loadAllData() {
  const appId = window.getAppId();
  if (!appId) {
    showToast('⚠️', 'No active application found.');
    return;
  }

  console.log('[enrolment] Loading data for:', appId);
  
  // Load Details & Progress
  const appRes = await window.apiFetch(`get_application_details.php?id=${encodeURIComponent(appId)}`);
  if (appRes.success && appRes.data.application) {
      const app = appRes.data.application;
      document.getElementById('workflowProgressContainer').innerHTML = window.renderWorkflowProgress(app.workflow_status);
      document.getElementById('approvedStrength').textContent = app.total_workmen || 0;
  }

  // Load Lists
  await Promise.all([
    loadList(appId, 'workman', 'workmenBody', 9),
    loadList(appId, 'representative', 'repsBody', 7),
    loadList(appId, 'supervisor', 'supervisorsBody', 7)
  ]);
  
  updateRemainingStats();
}

async function loadList(appId, type, containerId, cols) {
    const tbody = document.getElementById(containerId);
    tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>`;
    
    const res = await window.apiFetch(`get_workmen.php?application_id=${encodeURIComponent(appId)}&type=${encodeURIComponent(type)}`);
    const list = normalizeArray(res);
    
    if (list.length > 0) {
        tbody.innerHTML = list.map((w, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${w.name || 'N/A'}</td>
                <td>${w.father_name || '—'}</td>
                <td>${maskAadhaar(w.aadhar)}</td>
                ${type === 'workman' ? `<td>${w.role || 'Helper'}</td>` : ''}
                <td>${w.phone || '—'}</td>
                ${type === 'workman' ? `<td>${window.formatDate(w.created_at)}</td>` : ''}
                <td><span class="badge ${w.temp_id ? 'badge-success' : 'badge-warning'}">${w.temp_id || 'Pending'}</span></td>
                <td><button class="btn btn-outline btn-sm" onclick="viewWorkman(${w.id})"><i class="fas fa-eye"></i></button></td>
            </tr>
        `).join('');
        
        if (type === 'workman') {
            document.getElementById('enrolledCount').textContent = list.length;
            const issued = list.filter(w => w.temp_id).length;
            document.getElementById('tempIdsIssued').textContent = issued;
        }
    } else {
        tbody.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:20px;color:var(--gray-400)">No ${type}s enrolled yet.</td></tr>`;
    }
}

function updateRemainingStats() {
    const total = parseInt(document.getElementById('approvedStrength').textContent) || 0;
    const enrolled = parseInt(document.getElementById('enrolledCount').textContent) || 0;
    document.getElementById('pendingCount').textContent = Math.max(0, total - enrolled);
}

async function enrollWorkman() {
  const appId = window.getAppId();
  if (!appId) return;

  const btn = document.getElementById('enrolBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';

  const data = {
    application_id: appId,
    name: document.getElementById('wm-name').value.trim(),
    father_name: document.getElementById('wm-father-name').value.trim(),
    dob: document.getElementById('wm-dob').value,
    gender: document.getElementById('wm-gender').value,
    aadhar: document.getElementById('wm-aadhaar').value.trim(),
    phone: document.getElementById('wm-phone').value.trim(),
    role: document.getElementById('wm-role').value,
    address: document.getElementById('wm-address').value.trim(),
    state: document.getElementById('wm-state').value,
    type: 'workman'
  };

  if (!data.name || data.aadhar.length !== 12 || data.phone.length !== 10) {
    showToast('⚠️', 'Please fill all required fields correctly.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-user-plus"></i> Enrol Workman';
    return;
  }

  const res = await window.apiFetch('insert_workman.php', {
    method: 'POST',
    body: JSON.stringify(data)
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-user-plus"></i> Enrol Workman';

  if (res.success) {
    showToast('✅', 'Workman enrolled successfully!');
    hideModal('addWorkmanModal');
    loadAllData();
  } else {
    showToast('❌', res.error || 'Enrollment failed');
  }
}

function viewWorkman(id) {
  // Placeholder for detail view
}

function switchTab(tabId) {
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
  document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
  document.getElementById(tabId).classList.add('active');
}

document.addEventListener('DOMContentLoaded', loadAllData);
</script>
</body>
</html>

