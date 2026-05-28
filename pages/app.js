/* ============================================================
   CLMS – Contract Labour Management System
   Main Application JavaScript
   ============================================================ */

'use strict';

// ── State ──────────────────────────────────────────────────
let sidebarCollapsed = false;
let currentScreen    = 'dashboard';
let selectedRole     = 'Admin';
let chartInstances   = {};

// ── Page metadata ─────────────────────────────────────────
const pageMeta = {
  dashboard:            { title: 'Dashboard',                sub: 'Real-time Operations Overview — 27 Mar 2025' },
  contractors:          { title: 'Contractor Management',    sub: 'Manage registered contractors & approvals' },
  'contractor-reg':     { title: 'Contractor Self-Registration', sub: 'New contractor onboarding portal' },
  workmen:              { title: 'Workmen Management',       sub: 'Contract worker registry & profiles' },
  'worker-profile':     { title: 'Worker Profile',           sub: 'Ramesh Kumar — ACC-8821' },
  safety:               { title: 'Safety Training',          sub: 'Batch scheduling, assessments & certificates' },
  gatepass:             { title: 'Gate Pass Management',     sub: 'Issue, track and manage entry passes' },
  attendance:           { title: 'Attendance & Biometric',   sub: 'Real-time gate entry/exit log' },
  compliance:           { title: 'Compliance Management',    sub: 'ESI, EPF & statutory document tracking' },
  reports:              { title: 'Reports & Analytics',      sub: 'Generate statutory and operational reports' },
  admin:                { title: 'Administration',           sub: 'Users, settings, SAP integration & gates' },
  audit:                { title: 'Audit Log',                sub: 'System activity trail & access log' },
  security:             { title: 'Security Gate Console',    sub: 'Real-time entry verification — Gate 1' },
  'contractor-portal':  { title: 'Contractor Self-Service',  sub: 'Manage your workers, compliance & passes' },
};

// ── Bootstrap ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  startOTPTimer();
});

// ── Login ──────────────────────────────────────────────────
function selectRole(el, role) {
  selectedRole = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}

function sendOTP() {
  document.getElementById('login-step-1').style.display = 'none';
  document.getElementById('login-step-2').style.display = 'block';
  startOTPTimer();
}

function startOTPTimer() {
  let seconds = 45;
  const el = document.getElementById('otp-timer');
  if (!el) return;
  const t = setInterval(() => {
    seconds--;
    if (el) el.innerHTML = `Resend OTP in <strong>0:${seconds.toString().padStart(2,'0')}</strong>`;
    if (seconds <= 0) {
      clearInterval(t);
      if (el) el.innerHTML = '<a href="#" style="color:var(--primary);font-weight:600" onclick="startOTPTimer()">Resend OTP</a>';
    }
  }, 1000);
}

function verifyOTP() {
  document.getElementById('login-page').style.display = 'none';
  document.getElementById('app').style.display = 'block';
  navigate('dashboard', null);
  setTimeout(initCharts, 300);
}

function logout() {
  document.getElementById('app').style.display = 'none';
  document.getElementById('login-page').style.display = 'flex';
  document.getElementById('login-step-1').style.display = 'block';
  document.getElementById('login-step-2').style.display = 'none';
}

// ── Sidebar ────────────────────────────────────────────────
function toggleSidebar() {
  sidebarCollapsed = !sidebarCollapsed;
  const sb  = document.getElementById('sidebar');
  const tb  = document.getElementById('topbar');
  const mc  = document.getElementById('main-content');
  sb.classList.toggle('collapsed', sidebarCollapsed);
  tb.classList.toggle('sidebar-collapsed', sidebarCollapsed);
  mc.classList.toggle('sidebar-collapsed', sidebarCollapsed);
}

// ── Navigation ─────────────────────────────────────────────
function navigate(screenId, navEl) {
  // Hide all screens
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));

  // Show target screen
  const target = document.getElementById('screen-' + screenId);
  if (target) {
    target.classList.add('active');
    currentScreen = screenId;
  }

  // Update nav active state
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (navEl) navEl.classList.add('active');
  else {
    // auto-highlight matching nav items
    document.querySelectorAll('.nav-item').forEach(n => {
      const label = n.querySelector('.nav-label');
      if (label) {
        const map = {
          dashboard: 'Dashboard', contractors: 'Contractors', workmen: 'Workmen',
          safety: 'Safety Training', gatepass: 'Gate Pass', attendance: 'Attendance',
          compliance: 'Compliance', reports: 'Reports', admin: 'Admin', audit: 'Audit Log',
          security: 'Security Gate Console', 'contractor-portal': 'Contractor Self-Service',
        };
        if (map[screenId] && label.textContent.trim() === map[screenId]) {
          n.classList.add('active');
        }
      }
    });
  }

  // Update topbar
  const meta = pageMeta[screenId] || { title: screenId, sub: '' };
  const ptEl = document.getElementById('page-title');
  const psEl = document.getElementById('page-sub');
  if (ptEl) ptEl.textContent = meta.title;
  if (psEl) psEl.textContent = meta.sub;

  // Refresh charts if needed
  setTimeout(() => {
    if (screenId === 'dashboard') initCharts();
    if (screenId === 'attendance') initAttendanceScreenCharts();
    if (screenId === 'compliance') initComplianceChart();
    if (screenId === 'reports')    initReportChart();
  }, 100);

  // Close notification panel
  const np = document.getElementById('notif-panel');
  if (np) np.classList.remove('open');

  // Scroll to top
  const mc = document.getElementById('main-content');
  if (mc) mc.scrollTop = 0;
}

// ── Modals ─────────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}
// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ── Notifications ──────────────────────────────────────────
function toggleNotifications() {
  const panel = document.getElementById('notif-panel');
  if (panel) panel.classList.toggle('open');
}
document.addEventListener('click', e => {
  const panel = document.getElementById('notif-panel');
  if (panel && panel.classList.contains('open')) {
    if (!e.target.closest('.topbar-btn') && !e.target.closest('.notification-panel')) {
      panel.classList.remove('open');
    }
  }
});

// ── Tabs ───────────────────────────────────────────────────
function switchTab(group, panelId, btn) {
  // Hide all panels in group
  document.querySelectorAll(`[id^="tab-${group}-"]`).forEach(p => p.classList.remove('active'));
  // Deactivate all tab buttons in same tabs container
  if (btn && btn.parentElement) {
    btn.parentElement.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
  // Show target panel
  const panel = document.getElementById(`tab-${group}-${panelId}`);
  if (panel) panel.classList.add('active');
  // Refresh charts if needed
  if (group === 'comp' && panelId === 'history') setTimeout(initComplianceChart, 100);
  if (group === 'adm'  && panelId === 'sap')     setTimeout(initSAPChart, 100);
}

// ── Worker view toggle ─────────────────────────────────────
function setWorkerView(mode) {
  const listView = document.getElementById('worker-list-view');
  const gridView = document.getElementById('worker-grid-view');
  const listBtn  = document.getElementById('view-list-btn');
  const gridBtn  = document.getElementById('view-grid-btn');
  if (mode === 'list') {
    listView.style.display = 'block';
    gridView.style.display = 'none';
    listBtn.className = 'btn btn-primary btn-sm btn-icon';
    gridBtn.className = 'btn btn-outline btn-sm btn-icon';
  } else {
    listView.style.display = 'none';
    gridView.style.display = 'block';
    listBtn.className = 'btn btn-outline btn-sm btn-icon';
    gridBtn.className = 'btn btn-primary btn-sm btn-icon';
  }
}

// ── Contractor Filter ──────────────────────────────────────
function filterContractors(status) {
  const rows = document.querySelectorAll('#contractor-tbody tr');
  rows.forEach(r => {
    if (status === 'all') { r.style.display = ''; return; }
    const badge = r.querySelector('td:last-child span.badge, td:nth-child(9) span.badge');
    if (!badge) { r.style.display = ''; return; }
    const text = badge.textContent.trim().toLowerCase();
    r.style.display = (status === 'active' && text === 'active') ||
                      (status === 'pending' && text === 'pending') ||
                      (status === 'blacklisted' && text === 'blacklisted') ? '' : 'none';
  });
}

// ── Report selector ────────────────────────────────────────
function selectReport(type) {
  document.querySelectorAll('#screen-reports .nav-item').forEach(n => n.classList.remove('active'));
  event.currentTarget.classList.add('active');
}

// ── Charts ─────────────────────────────────────────────────
function destroyChart(id) {
  if (chartInstances[id]) {
    chartInstances[id].destroy();
    delete chartInstances[id];
  }
}

function initCharts() {
  initAttendanceTrendChart();
  initTradeChart();
}

function initAttendanceTrendChart() {
  destroyChart('attendanceChart');
  const ctx = document.getElementById('attendanceChart');
  if (!ctx) return;
  chartInstances['attendanceChart'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['21 Mar','22 Mar','23 Mar','24 Mar','25 Mar','26 Mar','27 Mar'],
      datasets: [
        {
          label: 'Workers Inside',
          data: [4120, 3980, 4250, 4100, 4320, 4180, 4218],
          borderColor: '#1a3c6e',
          backgroundColor: 'rgba(26,60,110,0.08)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#1a3c6e',
          pointRadius: 4,
        },
        {
          label: 'Total Checked In',
          data: [4580, 4420, 4720, 4500, 4830, 4650, 4786],
          borderColor: '#00b894',
          backgroundColor: 'rgba(0,184,148,0.05)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#00b894',
          pointRadius: 4,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: {
        y: { beginAtZero: false, min: 3500, grid: { color: '#f0f2f5' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
      }
    }
  });
}

function initTradeChart() {
  destroyChart('tradeChart');
  const ctx = document.getElementById('tradeChart');
  if (!ctx) return;
  chartInstances['tradeChart'] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Welder','Fitter','Electrician','Rigger','Painter','Carpenter','Helper','Other'],
      datasets: [{
        data: [1420, 980, 650, 520, 380, 290, 1100, 552],
        backgroundColor: ['#1a3c6e','#00b894','#0984e3','#e17055','#fdcb6e','#6c5ce7','#fd79a8','#b2bec3'],
        borderWidth: 2, borderColor: '#fff'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right', labels: { boxWidth: 10, font: { size: 11 }, padding: 8 } }
      },
      cutout: '60%'
    }
  });
}

function initAttendanceScreenCharts() {
  destroyChart('hourlyChart');
  const ctx = document.getElementById('hourlyChart');
  if (!ctx) return;
  chartInstances['hourlyChart'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['6AM','7AM','8AM','9AM','10AM','11AM','12PM','1PM','2PM','3PM','4PM','5PM'],
      datasets: [{
        label: 'Workers Entered',
        data: [32, 280, 1820, 1240, 520, 180, 90, 65, 120, 95, 200, 145],
        backgroundColor: 'rgba(26,60,110,0.75)',
        borderRadius: 4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { font: { size: 10 } } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
      }
    }
  });
}

function initComplianceChart() {
  destroyChart('complianceChart');
  const ctx = document.getElementById('complianceChart');
  if (!ctx) return;
  chartInstances['complianceChart'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Oct 2024','Nov 2024','Dec 2024','Jan 2025','Feb 2025','Mar 2025'],
      datasets: [
        { label: 'Compliant', data: [380, 395, 402, 398, 405, 394], backgroundColor: '#00b894', borderRadius: 4 },
        { label: 'Pending',   data: [72, 58, 50, 60, 55, 68],       backgroundColor: '#e17055', borderRadius: 4 },
        { label: 'Non-Compliant', data: [33, 32, 33, 27, 25, 23],   backgroundColor: '#d63031', borderRadius: 4 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: {
        y: { beginAtZero: true, stacked: false, grid: { color: '#f0f2f5' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
      }
    }
  });
}

function initReportChart() {
  destroyChart('reportChart');
  const ctx = document.getElementById('reportChart');
  if (!ctx) return;
  chartInstances['reportChart'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Welder','Fitter','Electrician','Rigger','Painter','Carpenter','Helper'],
      datasets: [
        { label: 'This Month', data: [1420, 980, 650, 520, 380, 290, 1100], backgroundColor: '#1a3c6e', borderRadius: 4 },
        { label: 'Last Month', data: [1380, 960, 630, 510, 370, 280, 1080], backgroundColor: '#74b9ff', borderRadius: 4 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: {
        y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
      }
    }
  });
}

function initSAPChart() {
  destroyChart('sapSyncChart');
  const ctx = document.getElementById('sapSyncChart');
  if (!ctx) return;
  chartInstances['sapSyncChart'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['00:00','04:00','08:00','12:00','16:00','20:00','Now'],
      datasets: [{
        label: 'SAP Sync Records',
        data: [120, 95, 1840, 1220, 1560, 980, 450],
        borderColor: '#0984e3', backgroundColor: 'rgba(9,132,227,0.1)', fill: true, tension: 0.4, pointRadius: 4
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
      }
    }
  });
}

// ── Toast notification ─────────────────────────────────────
function showToast(msg, type = 'success') {
  const colors = { success: '#00b894', danger: '#d63031', warning: '#e17055', info: '#0984e3' };
  const icons  = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
  const toast  = document.createElement('div');
  toast.style.cssText = `
    position:fixed; bottom:24px; right:24px; z-index:9999;
    background:${colors[type]}; color:white;
    padding:12px 20px; border-radius:10px;
    box-shadow:0 4px 20px rgba(0,0,0,0.2);
    font-size:13px; font-weight:500;
    display:flex; align-items:center; gap:8px;
    animation:slideUp .3s ease;
    max-width:320px;
  `;
  toast.innerHTML = `${icons[type]} ${msg}`;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity .3s'; setTimeout(()=>toast.remove(),300); }, 3000);
}

// ── Keyboard shortcuts ─────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    const np = document.getElementById('notif-panel');
    if (np) np.classList.remove('open');
  }
});

// ── CSS animation for toast ────────────────────────────────
const style = document.createElement('style');
style.textContent = `
  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  .sidebar.collapsed .nav-badge { display: none; }
`;
document.head.appendChild(style);
