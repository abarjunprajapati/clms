// =============================================
// CONTRACTOR MODULE – APP CONTROLLER
// =============================================

window.AppState = window.AppState || {
  applicationId: null,
  currentAppId: null,
  currentScreen: null
};
window.AppState.currentAppId = window.AppState.currentAppId || localStorage.getItem('application_id') || localStorage.getItem('applicationId') || sessionStorage.getItem('currentAppId') || null;
window.AppState.applicationId = window.AppState.applicationId || window.AppState.currentAppId;
window.currentAppId = window.AppState.currentAppId;

// Global APP_DATA initialization (prevent undefined access)
window.APP_DATA = window.APP_DATA || {
  pendingApplications: [],
  gatePassApplications: [],
  trainingSessions: [],
  trainingResults: [],
  sapContractor: {},
  qualifiedPersonnel: [],
  workmen: [],
  personnel: []
};

let currentRole = 'contractor';
let currentScreen = 'dashboard';

// ---- AUTH ----
function selectRole(btn, role) {
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  currentRole = role;

  const userMap = {
    contractor: 'CONT-2024-001',
    welfare_admin: 'welfare1@example.com',
    welfare_user: 'test_welfare_user@example.com',
    authority: 'test_super_admin@example.com',
    safety: 'safety1@example.com',
    pass: 'test_pass_user@example.com',
  };

  const labelMap = {
    contractor: 'Contractor ID',
    welfare_admin: 'Welfare Admin Email',
    welfare_user: 'Welfare User Email',
    authority: 'Admin Email',
    safety: 'Safety User ID',
    pass: 'Pass Officer ID'
  };

  const placeholderMap = {
    contractor: 'Enter Contractor ID (e.g. CONT-2024-001)',
    welfare_admin: 'Enter Welfare Admin Email',
    welfare_user: 'Enter Welfare User Email',
    authority: 'Enter Admin Email',
    safety: 'Enter Safety User Email',
    pass: 'Enter Pass Officer Email'
  };

  document.getElementById('login-label').textContent = labelMap[role] || 'User ID';
  document.getElementById('login-user').placeholder = placeholderMap[role] || 'Enter ID / Email';
  document.getElementById('login-user').value = userMap[role] || '';
}

function getRoleLabel(role) {
  const roleLabels = {
    'contractor': 'Contractor',
    'welfare_admin': 'Welfare Admin',
    'welfare_user': 'Welfare User',
    'safety_user': 'Safety User',
    'front_line_user': 'Front Line User',
    'pass_user': 'Pass User',
    'super_admin': 'Super Admin'
  };
  return roleLabels[role] || role;
}

async function handleLogin(e) {
  e.preventDefault();

  const contractorId = document.getElementById('login-user').value.trim();
  const password = document.getElementById('login-pass').value.trim();
  const captcha = document.getElementById('login-captcha').value.trim();

  if (!contractorId || !password || !captcha) {
    showToast('⚠️', 'All fields including verification code are required');
    return;
  }

  try {
    showToast('⏳', 'Authenticating...');

    const result = await apiFetch("login.php", {
      method: 'POST',
      body: {
        contractor_id: contractorId,
        password: password,
        captcha: captcha
      }
    });

    console.log('LOGIN API RESPONSE:', result);

    if (result.success && result.data) {
      // Login API returns OTP flow: {status: 'otp_sent', user_id: ..., otp_demo: ...}
      if (result.data.status === 'otp_sent') {
        sessionStorage.setItem('pending_user_id', result.data.user_id);
        // Show OTP hint for dev/testing
        const otpHint = document.getElementById('login-otp-hint');
        if (otpHint && result.data.otp_demo) {
          otpHint.textContent = `Dev OTP: ${result.data.otp_demo}`;
        }
        showToast('✅', result.message || 'OTP sent successfully!');
        openModal('modal-otp');
        setTimeout(() => {
          const firstOtp = document.getElementById('otp0');
          if (firstOtp) firstOtp.focus();
        }, 300);
      } else if (result.data.redirect) {
        // Direct login (no OTP) - follow redirect
        showToast('✅', 'Login successful! Redirecting...');
        setTimeout(() => {
          window.location.href = result.data.redirect;
        }, 500);
      }
    } else {
      showToast('❌', result.message || 'Login failed');
      if (typeof refreshCaptcha === 'function') refreshCaptcha();
      const captchaInput = document.getElementById('login-captcha');
      if (captchaInput) captchaInput.value = '';
    }
  } catch (error) {
    console.error('Login error:', error);
    showToast('❌', error.message || 'Login failed. Check console.');
    if (typeof refreshCaptcha === 'function') refreshCaptcha();
  }
}

function quickLogin(role) {
  currentRole = role;
  doLogin(role);
}

function doLogin(role) {
  document.getElementById('login-screen').style.display = 'none';
  document.getElementById('app').style.display = 'flex';

  const cfg = ROLE_CONFIG[role];
  document.getElementById('sidebar-user-name').textContent = cfg.name;
  document.getElementById('sidebar-user-role').textContent = cfg.label;
  document.getElementById('user-avatar-initials').textContent = cfg.initials;
  document.getElementById('topbar-role-badge').textContent = cfg.badge;

  buildSidebarNav(role);
  navigate('dashboard');
  showToast('👋', `Welcome! Logged in as ${cfg.label}`);
}

async function logout() {
  try {
    await apiFetch("logout.php", {
      method: 'POST'
    });

    // Clear session storage
    sessionStorage.clear();

    // Hide app, show login
    document.getElementById('app').style.display = 'none';
    document.getElementById('login-screen').style.display = 'flex';

    showToast('👋', 'Logged out successfully');
  } catch (error) {
    console.error('Logout error:', error);
    // Still logout on client side even if API fails
    sessionStorage.clear();
    document.getElementById('app').style.display = 'none';
    document.getElementById('login-screen').style.display = 'flex';
    showToast('👋', 'Logged out (API error ignored)');
  }
}

// ---- SIDEBAR NAV ----
function buildSidebarNav(role) {
  const cfg = ROLE_CONFIG[role];
  const nav = document.getElementById('sidebar-nav');
  nav.innerHTML = cfg.nav.map(group => `
    <div class="nav-group-title">${group.group}</div>
    ${group.items.map(item => `
      <div class="nav-item" id="nav-${item.id}" onclick="navigate('${item.id}')">
        <div class="nav-icon"><i class="${item.icon}"></i></div>
        <span>${item.label}</span>
        ${item.badge ? `<span class="nav-badge">${item.badge}</span>` : ''}
      </div>
    `).join('')}
  `).join('');
}

// ---- NAVIGATION ----
function navigate(screenId, params = {}) {
  if (typeof params === 'string' || typeof params === 'number') {
    params = { applicationId: String(params) };
  }

  if (params.applicationId) {
    const appId = String(params.applicationId);
    AppState.currentAppId = appId;
    AppState.applicationId = appId;
    localStorage.setItem('application_id', appId);
    sessionStorage.setItem('currentAppId', appId);
  } else {
    AppState.currentAppId = getAppId() || "";
  }
  AppState.currentScreen = screenId;
  window.currentAppId = AppState.currentAppId;

  // Navigation guard: screens that require an appId
  const resolvedAppId = getAppId(params);
  if ((screenId === 'welfare-verify' || screenId === 'welfare-approval-detail' || screenId === 'gatepass-request') && !resolvedAppId) {
    console.warn(`[navigate] ${screenId} needs application_id but none is selected.`);
    showToast('⚠️', 'Please select an application first');
    return;
  }
  console.log('[navigate]', screenId, 'appId=', AppState.currentAppId);

  // Hide all screens
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));

  // Show target screen
  const target = document.getElementById(`screen-${screenId}`);
  if (target) {
    target.classList.add('active');
    currentScreen = screenId;
  }

  // Update nav active state
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const navItem = document.getElementById(`nav-${screenId}`);
  if (navItem) navItem.classList.add('active');

  // Update topbar with param
  const titles = {
    'dashboard': ['Dashboard', 'Overview & Quick Actions'],
    'welfare-approval': ['Approval Workflow', resolvedAppId ? `Application ${resolvedAppId}` : 'Welfare Authority Approvals'],
    'welfare-verify': ['Verification', resolvedAppId ? `App ${resolvedAppId}` : 'Document Verification'],
    // ... existing titles
  };

  const titleData = titles[screenId] || ['CMS Portal', ''];
  document.getElementById('topbar-title').textContent = titleData[0];
  document.getElementById('topbar-subtitle').textContent = titleData[1];

  // Render screen content  
  try {
    renderScreen(screenId, params);
  } catch (err) {
    console.error(`[navigate] Error rendering screen ${screenId}:`, err);
    showToast('❌', 'Error loading page content');
  }

  // Scroll to top
  const pageContent = document.querySelector('.page-content');
  if (pageContent) pageContent.scrollTop = 0;
}

function renderScreen(screenId, params = {}) {
  console.log('[renderScreen] Loading:', screenId, 'with params:', params);

  try {
    switch (screenId) {
      case 'dashboard':
        renderDashboard(currentRole);
        break;
      case 'sap-details':
        renderSAPDetails();
        break;
      case 'annexure2a':
        renderAnnexure2A();
        break;
      case 'annexure3a':
        renderAnnexure3A();
        break;
      case 'welfare-verify':
        renderWelfareVerify(params);
        break;
      case 'welfare-approval':
        renderWelfareApproval();
        break;
      case 'enrolment':
        renderEnrolment();
        break;
      case 'temp-id':
        loadAllPersonsForTempID();
        break;
      case 'payment':
        renderPayment();
        break;
      case 'training-result':
        loadTrainingResults('TRN-001', renderTrainingResult);
        break;
      case 'gatepass-request':
        renderGatePassRequest();
        break;
      case 'pass-officer':
        renderPassOfficer();
        break;
      case 'final-approval':
        renderFinalApproval();
        break;
      case 'permanent-pass':
        renderPermanentPass();
        break;
      case 'safety-training':
        renderSafetyTraining(currentRole);
        break;
      case 'notifications':
        renderNotifications();
        break;
      case 'profile':
        renderProfile();
        break;
      default:
        console.warn('[renderScreen] Unknown screen:', screenId);
        showToast('⚠️', `Screen "${screenId}" not implemented yet`);
        break;
    }
  } catch (err) {
    console.error(`[renderScreen] Error rendering ${screenId}:`, err);
    showToast('❌', `Failed to load ${screenId}: ${err.message}`);
  }
}

// ---- SIDEBAR TOGGLE ----
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const main = document.getElementById('main-content');
  if (window.innerWidth <= 900) {
    sidebar.classList.toggle('mobile-open');
  } else {
    sidebar.classList.toggle('collapsed');
    main.style.marginLeft = sidebar.classList.contains('collapsed') ? '64px' : '260px';
  }
}

// ---- TABS ----
function switchTab(btn, panelId) {
  // Find sibling tab buttons
  const parent = btn.parentElement;
  parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Find panels (siblings of parent or within same container)
  const container = parent.nextElementSibling ? parent.parentElement : parent.closest('.card, .screen');
  const allPanels = container ? container.querySelectorAll('.tab-panel') : document.querySelectorAll('.tab-panel');
  allPanels.forEach(p => p.classList.remove('active'));
  const panel = document.getElementById(panelId);
  if (panel) panel.classList.add('active');
}

// ---- NOTIFICATION TAB (modal) ----
function switchNotifTab(btn, channel) {
  document.querySelectorAll('#modal-notification-preview .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  ['email', 'sms', 'push'].forEach(c => {
    const el = document.getElementById(`notif-tab-${c}`);
    if (el) el.style.display = c === channel ? 'block' : 'none';
  });
}

// ---- MODALS ----
async function showOTPModal() {
  const username = document.getElementById('login-user').value.trim();
  const password = document.getElementById('login-pass').value.trim();

  if (!username || !password) {
    showToast('⚠️', 'Please enter username and password first');
    return;
  }

  let response;
  try {
    showToast('⏳', 'Requesting OTP...');

    const result = await apiFetch("login.php", {
      body: { username, password }
    });

    if (result.status === 'otp_sent') {
      sessionStorage.setItem('pending_user_id', result.user_id);
      showToast(`OTP Sent Successfully: ${result.otp}`, "success");
      openModal('modal-otp');
      setTimeout(() => document.getElementById('otp0').focus(), 300);
    } else {
      showToast('❌', result.message || 'Login failed');
    }
  } catch (error) {
    console.error('OTP request error:', error);
    showToast('❌', error.message || 'Network error. Try again.');
  }
}
function showRejectModal() { openModal('modal-reject'); }
function showApproveModal() { openModal('modal-approve'); }
function showNotifPreview() { openModal('modal-notification-preview'); }

function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('show'); }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('show'); }
}
// Close on overlay click
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }

  const reviewBtn = e.target.closest('.review-btn');
  if (reviewBtn) {
    const id = reviewBtn.dataset.id;
    if (!id) {
      console.error('Missing appId in review button');
      return;
    }
    setAppId(id);
    navigate('welfare-verify', { applicationId: id });
    return;
  }

  const refreshBtn = e.target.closest('.welfare-refresh-btn');
  if (refreshBtn) {
    loadWelfareVerification({ applicationId: refreshBtn.dataset.id || getAppId() });
    return;
  }

  const verifyDocBtn = e.target.closest('.verify-document-btn');
  if (verifyDocBtn) {
    verifyDocument({ applicationId: verifyDocBtn.dataset.id || getAppId(), documentId: verifyDocBtn.dataset.docId });
    return;
  }

  const approveBtn = e.target.closest('.welfare-approve-btn');
  if (approveBtn) {
    welfareApprove({ applicationId: approveBtn.dataset.id || getAppId() });
    return;
  }

  const rejectBtn = e.target.closest('.welfare-reject-btn');
  if (rejectBtn) {
    welfareReject({ applicationId: rejectBtn.dataset.id || getAppId() });
    return;
  }

  const remarksBtn = e.target.closest('.remarks-btn');
  if (remarksBtn) {
    showRemarksModal(remarksBtn.dataset.type || 'update');
    return;
  }

  const finalApproveBtn = e.target.closest('.final-approve-btn');
  if (finalApproveBtn) {
    finalApprove(finalApproveBtn.dataset.id);
    return;
  }

  const finalRejectBtn = e.target.closest('.final-reject-btn');
  if (finalRejectBtn) {
    finalReject(finalRejectBtn.dataset.id);
  }
});

async function verifyOTP() {

  let otp = "";

  for (let i = 0; i < 6; i++) {
    const field = document.getElementById("otp" + i);
    const val = field ? field.value.trim() : "";

    if (!val || !/^\d$/.test(val)) {
      showToast("⚠️", "Please enter complete 6-digit OTP");
      if (field) field.focus();
      return;
    }

    otp += val;
  }

  const userId = sessionStorage.getItem("pending_user_id");

  if (!userId) {
    showToast("❌", "Session expired. Try again.");
    closeModal("modal-otp");
    return;
  }

  try {
    showToast("⏳", "Verifying OTP...");

    const result = await apiFetch("verify_otp.php", {
      body: {
        user_id: parseInt(userId),
        otp: otp
      }
    });

    console.log("VERIFY RESPONSE:", result);

    if (result.success) {
      // Store user data if returned
      if (result.data && result.data.user) {
        const user = result.data.user;
        sessionStorage.setItem('user_id', user.id);
        sessionStorage.setItem('role', user.role);
        sessionStorage.setItem('name', user.name);
        if (user.contractor_id) {
          sessionStorage.setItem('contractor_id', user.contractor_id);
        }
        currentRole = user.role;
      } else {
        currentRole = result.role || "user";
      }

      if (result.application_id) {
        setAppId(result.application_id);
      }

      sessionStorage.removeItem("pending_user_id");
      closeModal("modal-otp");
      showToast("✅", "Login successful! Redirecting...");

      // Full-page redirect to the server-provided dashboard URL
      const redirect = result.redirect || 'pages/contractor/dashboard.php';
      setTimeout(() => {
        window.location.href = redirect;
      }, 500);
    } else {
      showToast("❌", result.message || "Invalid OTP");
      for (let i = 0; i < 6; i++) {
        const field = document.getElementById("otp" + i);
        if (field) field.value = "";
      }
      const first = document.getElementById("otp0");
      if (first) first.focus();
    }
  } catch (error) {
    console.error("OTP verify error:", error);
    showToast("❌", error.message || "Network error. Try again.");
  }
}

function confirmReject() {
  const reason = document.getElementById('reject-reason').value;
  if (!reason) { showToast('⚠️', 'Please select a rejection reason'); return; }
  closeModal('modal-reject');
  showToast('❌', 'Application rejected. Contractor notified via Email & SMS.');
}

function confirmApprove() {
  closeModal('modal-approve');
  showToast('✅', 'Application approved! Email, SMS & Push notifications sent.');
}

// ---- TOAST ----
function showToast(arg1, arg2, duration = 4000) {
  let toast = document.getElementById("toast");
  let msgEl = document.getElementById("toast-msg");
  let iconEl = document.getElementById("toast-icon");
  let innerEl = document.getElementById("toast-inner");

  // Create toast dynamically if not present in DOM to prevent runtime errors
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "toast";
    toast.style.cssText = "position: fixed; top: 24px; right: 24px; z-index: 9999; display: none; pointer-events: none; font-family: 'Inter', system-ui, -apple-system, sans-serif;";

    innerEl = document.createElement("div");
    innerEl.id = "toast-inner";
    innerEl.style.cssText = "display: flex; align-items: center; gap: 12px; padding: 14px 20px; background: rgba(30, 41, 59, 0.95); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: #f8fafc; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1), inset 0 1px 0 0 rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform: translateY(-10px); opacity: 0; pointer-events: auto; max-width: 380px;";

    iconEl = document.createElement("span");
    iconEl.id = "toast-icon";
    iconEl.style.cssText = "font-size: 1.25rem; display: flex; align-items: center; justify-content: center;";

    msgEl = document.createElement("span");
    msgEl.id = "toast-msg";
    msgEl.style.cssText = "font-size: 0.875rem; font-weight: 500; line-height: 1.5; color: #e2e8f0; word-break: break-word;";

    innerEl.appendChild(iconEl);
    innerEl.appendChild(msgEl);
    toast.appendChild(innerEl);
    document.body.appendChild(toast);
  }

  let iconText, msgText;

  if (arg2 === undefined) {
    // showToast(msg)
    msgText = arg1;
    iconText = "ℹ️";
  } else {
    // showToast(icon/msg, msg/type)
    const likelyIcon = arg1.length <= 3 || /\p{Extended_Pictographic}/u.test(arg1);
    if (likelyIcon) {
      // old style: showToast('📱', 'msg')
      iconText = arg1;
      msgText = arg2;
    } else {
      // new style: showToast('msg', 'success')
      msgText = arg1;
      const type = arg2;
      if (type === "success") iconText = "✅";
      else if (type === "error") iconText = "❌";
      else iconText = "ℹ️";
    }
  }

  iconEl.textContent = iconText;
  msgEl.textContent = msgText;

  // Modern background colors based on feedback type
  const typeColorMap = {
    '✅': 'rgba(30, 41, 59, 0.95)',
    '❌': 'rgba(239, 68, 68, 0.95)',
    '⚠️': 'rgba(245, 158, 11, 0.95)',
    '⏳': 'rgba(30, 41, 59, 0.95)',
    '👋': 'rgba(30, 41, 59, 0.95)'
  };
  innerEl.style.background = typeColorMap[iconText] || 'rgba(30, 41, 59, 0.95)';

  toast.style.display = "block";
  
  // Slide down & Fade in
  setTimeout(() => {
    innerEl.style.transform = "translateY(0)";
    innerEl.style.opacity = "1";
  }, 10);

  setTimeout(() => {
    // Slide up & Fade out
    innerEl.style.transform = "translateY(-10px)";
    innerEl.style.opacity = "0";
    setTimeout(() => {
      toast.style.display = "none";
    }, 300);
  }, duration);
}

// ---- SAP CONTRACTOR DATA FETCHER ----
async function loadSAPContractor(applicationId) {
  try {
    const appId = applicationId || getAppId() || window.currentAppId;
    console.log('[loadSAPContractor] Fetching for appId:', appId);

    // Try to get from API if no data
    const res = await apiFetch(`contractor/sap_fetch_contractor.php?application_id=${encodeURIComponent(appId)}`);
    console.log('[loadSAPContractor] API response:', res);

    if (res.success && res.data) {
      // Set global data
      window.APP_DATA = window.APP_DATA || {};
      window.APP_DATA.sapContractor = res.data;
      return res.data;
    }

    // Return safe default if API fails
    return getSafeSAPData();
  } catch (error) {
    console.error('[loadSAPContractor] Error:', error);
    return getSafeSAPData();
  }
}

// Safe fallback for SAP data - prevents undefined
function getSafeSAPData() {
  return {
    code: 'N/A',
    name: 'No Contractor Data',
    type: 'Contractor',
    pan: 'N/A',
    gstin: 'N/A',
    regNo: 'N/A',
    email: 'N/A',
    phone: 'N/A',
    status: 'Active',
    address: 'N/A',
    workOrder: 'N/A',
    workOrderDate: 'N/A',
    project: 'N/A',
    location: 'N/A',
    contractValue: 'N/A',
    startDate: 'N/A',
    endDate: 'N/A',
    licenseNo: 'N/A',
    licenseValidity: 'N/A',
    pf: 'N/A',
    esic: 'N/A',
    labourLicense: 'N/A',
    safetyOfficer: 'Assigned',
    bankName: 'N/A',
    bankAccount: 'N/A',
    ifsc: 'N/A',
    sapSync: new Date().toLocaleString()
  };
}

// ---- TRAINING DATA FETCHER ----
async function loadTrainingData(applicationId) {
  try {
    const appId = applicationId || getAppId() || window.currentAppId;
    console.log('[loadTrainingData] Fetching for appId:', appId);

    // First, try to get training sessions
    const sessionsRes = await apiFetch('get_training_sessions.php');
    console.log('[loadTrainingData] Sessions:', sessionsRes);

    // Then get training results if we have a session
    let results = [];
    if (sessionsRes.success && sessionsRes.data && sessionsRes.data.length > 0) {
      const sessionId = sessionsRes.data[0].id;
      const resultsRes = await apiFetch(`get_training_results.php?session_id=${encodeURIComponent(sessionId)}`);
      results = resultsRes.success ? (Array.isArray(resultsRes.data) ? resultsRes.data : []) : [];
    }

    // Set global data
    window.APP_DATA = window.APP_DATA || {};
    window.APP_DATA.trainingSessions = sessionsRes.success ? (Array.isArray(sessionsRes.data) ? sessionsRes.data : []) : [];
    window.APP_DATA.trainingResults = results;

    return {
      sessions: window.APP_DATA.trainingSessions,
      results: window.APP_DATA.trainingResults
    };
  } catch (error) {
    console.error('[loadTrainingData] Error:', error);
    window.APP_DATA = window.APP_DATA || {};
    window.APP_DATA.trainingSessions = [];
    window.APP_DATA.trainingResults = [];
    return { sessions: [], results: [] };
  }
}

// ---- GATE PASS ELIGIBLE WORKERS ----
async function loadGatePassEligible(applicationId) {
  try {
    const appId = applicationId || getAppId() || window.currentAppId;
    if (!appId) {
      console.warn('[loadGatePassEligible] No application ID');
      return [];
    }

    const res = await apiFetch(`get_gate_passes.php?application_id=${encodeURIComponent(appId)}`);
    console.log('[loadGatePassEligible] API response:', res);

    if (res.success && res.data) {
      window.APP_DATA = window.APP_DATA || {};
      window.APP_DATA.qualifiedPersonnel = res.data;
      return res.data;
    }

    return [];
  } catch (error) {
    console.error('[loadGatePassEligible] Error:', error);
    return [];
  }
}

// ---- HELPER FUNCTIONS ----
function submitAndToast(msg, nextScreen) {
  showToast('✅', msg, 3000);
  setTimeout(() => { navigate(nextScreen); }, 800);
}

function showEnrolForm() {
  const form = document.getElementById('enrol-form-section');
  if (form) { form.style.display = 'block'; form.scrollIntoView({ behavior: 'smooth' }); }
}
function hideEnrolForm() {
  const form = document.getElementById('enrol-form-section');
  if (form) form.style.display = 'none';
}

function checklistChange(cb) {
  showToast('📋', cb.checked ? 'Item marked as verified' : 'Item marked as pending');
}

function togglePwd() {
  const inp = document.getElementById('login-pass');
  const icon = document.getElementById('toggle-password');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    inp.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

function otpNext(input, index) {
  const nextIndex = index + 1;
  const prevIndex = index - 1;

  // Forward on digit entry
  if (input.value.length === 1 && index < 5) {
    const nextField = document.getElementById("otp" + nextIndex);
    if (nextField) nextField.focus();
    return;
  }

  // Backward on backspace/delete
  if (input.value.length === 0 && (index > 0 || event.key === 'Backspace' || event.key === 'Delete')) {
    const prevField = document.getElementById("otp" + prevIndex);
    if (prevField && prevIndex >= 0) prevField.focus();
    return;
  }
}

// OTP Paste Handler + Resend
document.addEventListener('DOMContentLoaded', function () {
  const otpFields = document.querySelectorAll('input[id^="otp"]');
  otpFields.forEach((input, index) => {
    input.addEventListener('paste', function (e) {
      e.preventDefault();
      const pasteData = e.clipboardData.getData('text/plain').trim();

      if (/^\d{6}$/.test(pasteData)) {
        otpFields.forEach((field, i) => {
          field.value = pasteData[i] || '';
        });
        if (index < 5) {
          document.getElementById('otp5').focus();
        }

      } else {
        showToast('Please paste a valid 6-digit OTP', "error");
      }
    });

    // Allow tab navigation
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Tab') return;
      if (e.key === 'Enter') {
        verifyOTP();
        e.preventDefault();
      }
    });
  });

  // Resend OTP link handler
  const resendLink = document.querySelector('#modal-otp a[href="#"]');
  if (resendLink) {
    resendLink.addEventListener('click', function (e) {
      e.preventDefault();
      resendOTP();
    });
  }
});

// Resend OTP function with debounce
let resendCooldown = 0;
async function resendOTP() {
  if (Date.now() < resendCooldown) {
    showToast('Please wait before resending', 'error');
    return;
  }

  const userId = sessionStorage.getItem('pending_user_id');
  if (!userId) {
    showToast('No active OTP session', 'error');
    return;
  }

  try {
    showToast('Sending new OTP...', 'info');

    const result = await apiFetch("resend_otp.php", {
      body: { user_id: parseInt(userId) }
    });

    if (result.status === 'otp_sent') {
      showToast(`New OTP Sent Successfully: ${result.otp}`, "success");
      resendCooldown = Date.now() + 10000; // 10s cooldown
      setTimeout(() => {
        const link = document.querySelector('#modal-otp a[href="#"]');
        if (link) link.style.opacity = '1';
      }, 10000);

      // Disable link temporarily
      const link = document.querySelector('#modal-otp a[href="#"]');
      if (link) {
        link.style.opacity = '0.5';
        link.innerHTML = 'Resend OTP (10s)';
      }
    } else {
      showToast(result.message || 'Resend failed', 'error');
    }
  } catch (error) {
    showToast(error.message || 'Network error', 'error');
    console.error('Resend error:', error);
  }
}

// Initialize app with session check
window.addEventListener('load', async function () {
  const loginScreen = document.getElementById('login-screen');
  const appShell = document.getElementById('app');

  // Check if already logged in
  try {
    const result = await apiFetch("check_session.php", { method: 'GET', silent: true });

    if (result.success && result.data && result.data.user_id) {
      const userData = result.data;
      currentRole = userData.role;

      const redirectMap = {
        'super_admin': 'pages/admin/dashboard.php',
        'welfare_admin': 'pages/welfare/admin_dashboard.php',
        'welfare_user': 'pages/welfare/dashboard.php',
        'contractor': 'pages/contractor/dashboard.php',
        'front_line_user': 'pages/frontline/dashboard.php',
        'pass_user': 'pages/welfare/pass_issuer_dashboard.php',
        'safety_user': 'pages/safety/dashboard.php',
        'execution_officer': 'pages/execution/dashboard.php',
        'customer': 'pages/customer/dashboard.php'
      };

      const redirect = redirectMap[userData.role] || 'pages/contractor/dashboard.php';
      window.location.href = redirect;
      return;
    }
  } catch (e) {
    console.log('No session - show login', e.message);
  }

  // Show login screen
  if (loginScreen) loginScreen.style.display = 'flex';
  if (appShell) appShell.style.display = 'none';
});

// BFCache prevention: reload page if loaded from history (back/forward)
window.addEventListener('pageshow', function(event) {
  if (event.persisted) {
    window.location.reload();
  }
});

