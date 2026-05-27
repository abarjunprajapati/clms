<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Temporary ID Cards – Generation</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <style>
    .id-card-grid { display:flex; flex-wrap:wrap; gap:20px; padding:20px; }
    @media print {
      body * { visibility: hidden; }
      #printSection, #printSection * { visibility: visible; }
      #printSection { position: absolute; left: 0; top: 0; }
    }
    .card-chip {
      width: 28px; height: 20px;
      background: linear-gradient(135deg, #d4a017, #f5c842, #d4a017);
      border-radius: 4px;
      display: inline-block;
    }
  </style>
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-id-card"></i></div>
    <div>
      <div class="topbar-title">Temporary ID Card Generation</div>
      <div class="topbar-subtitle">Contractor Portal · Unique Temp IDs</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="enrolment-4a.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-arrow-left"></i> Enrolment</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<div class="page-container">
  <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between">
    <div>
      <div class="page-title">Generate Temporary ID Cards</div>
      <div class="page-subtitle">Generate unique temporary ID cards for all enrolled workmen. Cards include unique ID, QR code, and validity period.</div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print All</button>
      <button class="btn btn-primary" onclick="generateAll()"><i class="fas fa-magic"></i> Generate All IDs</button>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:var(--primary)"><i class="fas fa-users"></i></div>
      <div class="stat-value" id="totalEnrolled">0</div><div class="stat-label">Total Enrolled</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-id-card"></i></div>
      <div class="stat-value" id="generatedCount">0</div><div class="stat-label">IDs Generated</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-clock"></i></div>
      <div class="stat-value" id="pendingCount">0</div><div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#f0fdf4;color:var(--success)"><i class="fas fa-check-circle"></i></div>
      <div class="stat-value" id="cardsIssued">0</div><div class="stat-label">Cards Issued</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <!-- Selection Table -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-list"></i> Enrolled Workmen</div>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer">
          <input type="checkbox" id="selectAll" onchange="selectAllFn()" /> Select All
        </label>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="selectAllTh" onchange="selectAllFn()" /></th>
              <th>Name</th>
              <th>Trade</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="workmanList">
            <tr id="workmenLoading"><td colspan="5" style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading enrolled workmen...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Card Preview -->
    <div>
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><div class="card-title"><i class="fas fa-eye"></i> Card Preview</div></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center;padding:30px">
          <div id="printSection">
            <div class="id-card" id="previewCard">
              <!-- Card Header -->
              <div class="id-card-header">
                <div class="id-card-photo"><i class="fas fa-user"></i></div>
                <div>
                  <div style="font-size:10px;opacity:0.8;font-weight:600;letter-spacing:1px">GOVT. WORKS DEPT.</div>
                  <div style="font-size:14px;font-weight:700">CONTRACTOR WORKMAN</div>
                  <div style="font-size:10px;opacity:0.75">Temporary Identification Card</div>
                </div>
              </div>
              <!-- Card Body -->
              <div class="id-card-body">
                <div class="id-card-id" id="cardId">TMP-0001</div>
                <div class="divider" style="margin:8px 0"></div>
                <div class="info-row"><span class="info-label">Name</span><span class="info-value" id="cardName">Ramesh Sharma</span></div>
                <div class="info-row"><span class="info-label">Trade</span><span class="info-value" id="cardTrade">Mason</span></div>
                <div class="info-row"><span class="info-label">Contractor</span><span class="info-value">Ravi Constructions</span></div>
                <div class="info-row"><span class="info-label">Contract</span><span class="info-value">PWD/2024/CNT/0187</span></div>
                <div class="info-row"><span class="info-label">Issue Date</span><span class="info-value">03 Apr 2025</span></div>
                <div class="info-row"><span class="info-label">Valid Till</span><span class="info-value" style="color:var(--danger);font-weight:700">31 Dec 2025</span></div>
                <div class="divider" style="margin:8px 0"></div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                  <div>
                    <div class="card-chip"></div>
                    <div style="font-size:10px;color:var(--gray-400);margin-top:4px">Biometric Enabled</div>
                  </div>
                  <div style="text-align:right">
                    <!-- QR Code representation -->
                    <div style="width:55px;height:55px;background:var(--gray-100);border:1px solid var(--gray-300);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:20px">
                      <i class="fas fa-qrcode" style="color:var(--gray-600)"></i>
                    </div>
                    <div style="font-size:9px;color:var(--gray-400);margin-top:2px">Scan to Verify</div>
                  </div>
                </div>
              </div>
              <!-- Card Footer -->
              <div style="background:var(--gray-50);padding:8px 14px;border-top:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center">
                <div style="font-size:10px;color:var(--gray-500)">Work Site: Km 42, Pune–Mumbai</div>
                <span class="badge badge-success" style="font-size:9px">ACTIVE</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Print Actions -->
      <div class="card">
        <div class="card-body">
          <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:12px">Card Actions</div>
          <div style="display:flex;flex-direction:column;gap:8px">
            <button class="btn btn-primary btn-full" onclick="window.print()"><i class="fas fa-print"></i> Print This Card</button>
            <button class="btn btn-outline btn-full" onclick="downloadCard()"><i class="fas fa-download"></i> Download as PDF</button>
            <button class="btn btn-outline btn-full" onclick="sendSMS()"><i class="fas fa-sms"></i> Send via SMS to Workman</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Proceed to Safety Training -->
  <div style="display:flex;justify-content:flex-end;margin-top:20px;gap:12px">
    <a href="safety-training-request.php" class="btn btn-primary btn-lg">
      <i class="fas fa-hard-hat"></i> Proceed to Safety Training Request
    </a>
  </div>
</div>

<script src="../js/navigation.js"></script>
<script>
// =============================================
// TEMP ID CARD - DYNAMIC DATA FETCH & GENERATION
// =============================================

// Fallback showToast if not defined
if (typeof showToast !== 'function') {
  function showToast(message, type = 'info') {
    console.log('[toast:' + type + ']', message);
    alert(message);
  }
}

// Get application_id from localStorage or session
function getAppId() {
  return localStorage.getItem('application_id') || 
         sessionStorage.getItem('application_id') ||
         localStorage.getItem('applicationId') ||
         null;
}

// Show loading spinner
function showLoading(msg) {
  return '<tr><td colspan="5" style="text-align:center;padding:20px"><i class="fas fa-spinner fa-spin"></i> ' + (msg || 'Loading...') + '</td></tr>';
}

// Show empty state
function showEmpty(msg) {
  return '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--gray-400)">' + (msg || 'No data found') + '</td></tr>';
}

// Build API URL — filter by appId only if available
function buildUrl(base, appId) {
  return appId ? base + '?application_id=' + encodeURIComponent(appId) : base;
}

// Render a single workman row for the list
function renderWorkmanListRow(w, index) {
  const tempId = w.temp_id || 'Pending';
  const isGenerated = tempId !== 'Pending';
  const badgeClass = isGenerated ? 'badge-success' : 'badge-warning';
  const badgeText = isGenerated ? tempId + ' ✓' : tempId;
  
  const name = w.name || 'N/A';
  const role = w.role || 'Helper';
  
  if (isGenerated) {
    return '<tr>' +
      '<td><input type="checkbox" class="wm-cb" data-id="' + w.id + '" data-name="' + name + '" data-role="' + role + '" data-tempid="' + tempId + '" /></td>' +
      '<td>' + name + '</td>' +
      '<td>' + role + '</td>' +
      '<td><span class="badge ' + badgeClass + '">' + badgeText + '</span></td>' +
      '<td><button class="btn btn-sm btn-outline" onclick="previewCard(' + w.id + ')"><i class="fas fa-eye"></i> Preview</button></td>' +
    '</tr>';
  } else {
    return '<tr>' +
      '<td><input type="checkbox" class="wm-cb" data-id="' + w.id + '" data-name="' + name + '" data-role="' + role + '" data-tempid="" /></td>' +
      '<td>' + name + '</td>' +
      '<td>' + role + '</td>' +
      '<td><span class="badge ' + badgeClass + '">' + badgeText + '</span></td>' +
      '<td><button class="btn btn-sm btn-primary" onclick="generateCard(' + w.id + ', this)"><i class="fas fa-plus"></i> Generate</button></td>' +
    '</tr>';
  }
}

// Load all enrolled workmen
async function loadWorkmen() {
  const appId = getAppId();
  const tbody = document.getElementById('workmanList');
  tbody.innerHTML = showLoading('Loading enrolled workmen...');

  console.log('[temp-id] application_id:', appId);
  if (!appId) {
    console.warn('[temp-id] No application_id — loading ALL workmen');
  }

  try {
    const response = await fetch(buildUrl('/clms/api/get_workmen.php', appId));
    const result = await response.json();
    console.log('[temp-id] Loaded workmen:', result.data);

    if (result.success && Array.isArray(result.data) && result.data.length > 0) {
      tbody.innerHTML = result.data.map((w, i) => renderWorkmanListRow(w, i)).join('');

      // Update stats with null safety
      const generated = result.data.filter(w => w.temp_id && w.temp_id !== 'Pending').length;
      const total     = result.data.length;
      const pending   = total - generated;

      const generatedEl = document.getElementById('generatedCount');
      const totalEl     = document.getElementById('totalEnrolled');
      const pendingEl   = document.getElementById('pendingCount');
      const issuedEl    = document.getElementById('cardsIssued');

      if (generatedEl) generatedEl.textContent = generated;
      if (totalEl)     totalEl.textContent     = total;
      if (pendingEl)   pendingEl.textContent   = pending;
      if (issuedEl)    issuedEl.textContent    = generated;
    } else {
      tbody.innerHTML = showEmpty('No workmen enrolled yet. Please enrol workmen from Annexure 4A first.');
    }
  } catch (error) {
    console.error('[temp-id] Error loading workmen:', error);
    tbody.innerHTML = showEmpty('Error loading data: ' + error.message);
  }
}

// Preview card for a specific workman
function previewCard(workmanId) {
  const appId = getAppId();

  fetch(buildUrl('/clms/api/get_workmen.php', appId))
    .then(res => res.json())
    .then(result => {
      if (result.success && result.data) {
        const w = result.data.find(x => x.id == workmanId);
        if (w) {
          document.getElementById('cardName').textContent  = w.name  || 'N/A';
          document.getElementById('cardTrade').textContent = w.role  || 'Helper';
          document.getElementById('cardId').textContent   = w.temp_id || 'Pending';

          const today    = new Date();
          const validTill = new Date(today);
          validTill.setFullYear(validTill.getFullYear() + 1);

          document.querySelector('.info-row:nth-child(5) .info-value').textContent = today.toLocaleDateString('en-IN');
          document.querySelector('.info-row:nth-child(6) .info-value').textContent = validTill.toLocaleDateString('en-IN');

          showToast('Card preview loaded for ' + w.name, 'info');
        }
      }
    })
    .catch(err => {
      console.error('[temp-id] Error preview:', err);
      showToast('Error loading card data', 'error');
    });
}

// Generate temp_id for a workman (if not already generated)
function generateCard(workmanId, btn) {
  const appId = getAppId();

  // Find workman data from the row
  const row  = btn.closest('tr');
  const name = row.querySelector('td:nth-child(2)').textContent;

  fetch(buildUrl('/clms/api/get_workmen.php', appId))
    .then(res => res.json())
    .then(result => {
      if (result.success && result.data) {
        const w = result.data.find(x => x.id == workmanId);
        if (w && w.temp_id) {
          // Update the row
          row.querySelector('.badge').className   = 'badge badge-success';
          row.querySelector('.badge').textContent  = w.temp_id + ' ✓';
          btn.innerHTML   = '<i class="fas fa-eye"></i> Preview';
          btn.className   = 'btn btn-sm btn-outline';
          btn.onclick     = () => previewCard(workmanId);

          const countEl = document.getElementById('generatedCount');
          if (countEl) countEl.textContent = parseInt(countEl.textContent) + 1;

          previewCard(workmanId);
          showToast('Temporary ID ' + w.temp_id + ' generated for ' + name + '!', 'success');
        } else {
          showToast('No temp_id found for this workman. Please complete enrolment first.', 'error');
        }
      }
    })
    .catch(err => {
      console.error('[temp-id] Error generating:', err);
      showToast('Error generating card', 'error');
    });
}

// Generate all cards
function generateAll() {
  const appId = getAppId();

  fetch(buildUrl('/clms/api/get_workmen.php', appId))
    .then(res => res.json())
    .then(result => {
      if (result.success && Array.isArray(result.data)) {
        const withoutTemp = result.data.filter(w => !w.temp_id || w.temp_id === 'Pending');
        if (withoutTemp.length === 0) {
          showToast('All workmen already have temp IDs!', 'info');
          return;
        }

        let generated = 0;
        withoutTemp.forEach(w => {
          const row = document.querySelector(`input[data-id="${w.id}"]`)?.closest('tr');
          if (row) {
            const btn = row.querySelector('.btn-primary');
            if (btn) {
              row.querySelector('.badge').className   = 'badge badge-success';
              row.querySelector('.badge').textContent  = (w.temp_id || 'Pending') + ' ✓';
              btn.innerHTML = '<i class="fas fa-eye"></i> Preview';
              btn.className = 'btn btn-sm btn-outline';
              btn.onclick   = () => previewCard(w.id);
              generated++;
            }
          }
        });

        const countEl   = document.getElementById('generatedCount');
        const pendingEl = document.getElementById('pendingCount');
        if (countEl)   countEl.textContent   = parseInt(countEl.textContent) + generated;
        if (pendingEl) pendingEl.textContent = Math.max(0, parseInt(pendingEl.textContent) - generated);

        showToast(generated + ' temporary IDs generated!', 'success');
      }
    })
    .catch(err => {
      console.error('[temp-id] Error generating all:', err);
      showToast('Error generating cards', 'error');
    });
}

// Select all checkbox
function selectAllFn() {
  const checked = document.getElementById('selectAll').checked || document.getElementById('selectAllTh').checked;
  document.querySelectorAll('.wm-cb').forEach(cb => cb.checked = checked);
}

// Download card as PDF (placeholder)
function downloadCard() {
  showToast('PDF download initiated!', 'success');
}

// Send SMS (placeholder)
function sendSMS() {
  showToast('SMS module is disabled in this CLMS build.', 'info');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  console.log('[temp-id] Initializing...');
  loadWorkmen();
});
</script>
</body>
</html>

