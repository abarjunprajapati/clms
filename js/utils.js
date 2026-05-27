// =============================================
// GLOBAL API CONFIG & UTILITIES
// =============================================
// Dynamic BASE_URL with fallback support
const BASE_URL = (() => {
  if (window.CLMS_BASE_URL) {
    return window.CLMS_BASE_URL + 'api/';
  }
  const protocol = window.location.protocol;
  const host = window.location.hostname;
  const port = window.location.port ? ':' + window.location.port : '';
  const pathname = window.location.pathname;

  // Detect if we are in a /clms/ subdirectory (typical for local dev)
  const isLocalSubdir = pathname.includes('/clms/');
  const basePath = isLocalSubdir ? '/clms/api/' : '/api/';

  return `${protocol}//${host}${port}${basePath}`;
})();

// Fallback to localhost if live server API is down
let API_RETRY_LOCALHOST = false;

const AppState = window.AppState || {
  applicationId: null,
  currentAppId: null,
  currentScreen: null
};

AppState.currentAppId = AppState.currentAppId || localStorage.getItem('application_id') || localStorage.getItem('applicationId') || sessionStorage.getItem('currentAppId') || null;
AppState.applicationId = AppState.applicationId || AppState.currentAppId;
window.AppState = AppState;
window.currentAppId = AppState.currentAppId;
window.current_application_id = window.current_application_id || AppState.currentAppId;

function getAppId() {
  const stored = localStorage.getItem("application_id");
  // Return null if not set, empty, or invalid
  if (!stored || stored === 'undefined' || stored === 'null' || stored === '""') {
    return null;
  }
  return stored;
}

window.getAppId = getAppId;

function setAppId(id) {
  if (!id || id === 'undefined' || id === 'null') return null;
  const appId = String(id);
  AppState.currentAppId = appId;
  AppState.applicationId = appId;
  window.currentAppId = appId;
  window.current_application_id = appId;
  localStorage.setItem('application_id', appId);
  sessionStorage.setItem('application_id', appId);
  sessionStorage.setItem('currentAppId', appId);
  return appId;
}

window.setAppId = setAppId;

let isLoading = false;

async function safeLoad(fn) {
  if (isLoading) {
    console.log('[safeLoad] Already loading, skipping duplicate call');
    return;
  }

  isLoading = true;
  try {
    return await fn();
  } finally {
    isLoading = false;
  }
}

window.safeLoad = safeLoad;

/**
 * Normalize API response to always return an array
 */
function normalizeArray(data) {
  if (!data) return [];
  if (Array.isArray(data)) return data;
  if (data.applications && Array.isArray(data.applications)) return data.applications;
  if (data.data && Array.isArray(data.data)) return data.data;
  return [];
}
window.normalizeArray = normalizeArray;

/**
 * Safe Array Helper - prevents .map errors
 */
function safeArray(arr) {
  if (Array.isArray(arr)) return arr;
  if (arr && typeof arr === 'object' && Array.isArray(arr.data)) return arr.data;
  return [];
}
window.safeArray = safeArray;

/**
 * Universal API fetch wrapper
 */
async function apiFetch(url, options = {}) {
  // 1. Robust Signature Handling: Support both apiFetch(url, {body:{...}}) and apiFetch(url, {...data})
  let data = options.body || options;
  let method = (options.method || 'POST').toUpperCase();

  // If options was just a data object (no method/body properties), use it as data
  if (options.body === undefined && options.method === undefined) {
    data = options;
  }

  // 2. Auto-inject application_id for convenience
  const appId = getAppId();
  if (appId && typeof data === 'object' && data !== null && !data.application_id && !data.id) {
    data.application_id = appId;
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const fetchOptions = {
    method: method,
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken || ''
    }
  };

  // 3. Handle payload
  if (method !== 'GET') {
    if (data instanceof FormData) {
      delete fetchOptions.headers['Content-Type'];
      fetchOptions.body = data;
    } else {
      fetchOptions.body = JSON.stringify(data);
    }
  } else if (appId && !url.includes('application_id')) {
    const sep = url.includes('?') ? '&' : '?';
    url += `${sep}application_id=${encodeURIComponent(appId)}`;
  }

  // 1. Path Normalization: Handle legacy relative paths (../../api/)
  let finalUrl = url;
  if (!url.startsWith('http')) {
    // If it's a legacy relative path, strip the traversal and prefix
    if (url.includes('../../api/')) {
      finalUrl = url.replace(/.*\/api\//, '');
    } else if (url.startsWith('/clms/api/')) {
      finalUrl = url.replace('/clms/api/', '');
    } else if (url.startsWith('/api/')) {
      finalUrl = url.replace('/api/', '');
    }
    finalUrl = BASE_URL + finalUrl;
  }

  const res = await fetch(finalUrl, fetchOptions);

  const text = await res.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch (e) {
    console.error("Non-JSON response from " + url, text);
    throw new Error("Server returned invalid JSON. Check console for details.");
  }

  console.log(`API [${method}] ${url} ->`, json); // 🔥 DEBUG

  // Some legacy APIs return success=true, some return status="success"
  if (json.success === false || json.status === "error") {
    if (options.silent) {
      return json;
    }
    throw new Error(json.message || json.error || "API Error");
  }

  return json;
}

window.apiFetch = apiFetch;

if (!window.__nativeAlert) {
  window.__nativeAlert = window.alert.bind(window);
}

window.notifyUser = function (message, type = 'info', title = '') {
  const safeMessage = message || '';
  const safeTitle = title || (
    type === 'success' ? 'Success' :
    type === 'error' ? 'Error' :
    type === 'warning' ? 'Warning' : 'Notice'
  );

  if (typeof Swal !== 'undefined' && Swal.fire) {
    return Swal.fire({
      title: safeTitle,
      text: safeMessage,
      icon: type,
      confirmButtonText: 'OK'
    });
  }

  if (typeof window.showToast === 'function') {
    window.showToast(safeMessage, type);
    return Promise.resolve();
  }

  window.__nativeAlert((safeTitle ? safeTitle + ': ' : '') + safeMessage);
  return Promise.resolve();
};

window.alert = function (message) {
  const text = message == null ? '' : String(message);
  if (typeof Swal !== 'undefined' && Swal.fire) {
    Swal.fire({
      title: 'Notice',
      text,
      icon: 'info',
      confirmButtonText: 'OK'
    });
    return;
  }
  window.__nativeAlert(text);
};


// Universal error handler for API calls
window.handleApiError = function (error, context = 'API') {
  console.error(`${context} error:`, error);
  if (typeof showToast === 'function') {
    showToast('❌', error.message || 'Network/API error');
  } else {
    alert('Error: ' + (error.message || 'Network/API error'));
  }
};

// Session expired handler (attached to window so app.js can override if needed)
window.handleSessionExpired = function () {
  if (typeof showToast === 'function') showToast('🔒', 'Session expired. Redirecting to login...');
  setTimeout(() => {
    window.location.href = './';
  }, 2000);
};

/**
 * Visual Progress Tracker Renderer
 * Returns HTML for the multi-step progress indicator based on workflow_status
 */
window.renderWorkflowProgress = function (status) {
  const steps = [
    { key: 'draft', label: 'Draft' },
    { key: 'submitted', label: 'Welfare Admin Approval' },
    { key: 'approved', label: 'Workmen Enrolment' },
    { key: 'enrolment_done', label: 'Safety Training' },
    { key: 'training_done', label: 'Gate Pass Apply' },
    { key: 'gatepass_requested', label: 'Welfare Verification' },
    { key: 'gatepass_verified', label: 'Pass Issuance' }
  ];

  const statusOrder = {
    'draft': 0,
    'submitted': 1,
    'approved': 2,
    'enrolment_done': 3,
    'training_done': 4,
    'gatepass_requested': 5,
    'gatepass_verified': 6,
    'temporary_pass_issued': 7,
    'acc_generated': 8,
    'permanent_pass_issued': 9,
    'rejected': -1
  };

  const currentIdx = statusOrder[status] ?? 0;

  let html = '<div class="workflow-stepper">';
  steps.forEach((step, idx) => {
    let stateClass = '';
    let icon = idx + 1;

    if (status === 'rejected') {
      stateClass = idx === 0 ? 'rejected' : 'disabled';
      if (idx === 0) icon = '<i class="fas fa-times"></i>';
    } else if (statusOrder[step.key] < currentIdx) {
      stateClass = 'completed';
      icon = '<i class="fas fa-check"></i>';
    } else if (statusOrder[step.key] === currentIdx) {
      stateClass = 'active';
    } else {
      stateClass = 'disabled';
    }

    html += `
      <div class="workflow-step ${stateClass}">
        <div class="workflow-icon">${icon}</div>
        <div class="workflow-label" style="font-size:10px">${step.label}</div>
      </div>
    `;
    if (idx < steps.length - 1) {
      const lineCompleted = statusOrder[steps[idx + 1].key] <= currentIdx;
      html += `<div class="workflow-line ${lineCompleted ? 'completed' : ''}"></div>`;
    }
  });
  html += '</div>';
  return html;
};

// =============================================
// GLOBAL FORMATTERS & DEBUGGERS
// =============================================

/**
 * Standard Date Formatter
 * Handles null, undefined, and invalid dates
 */
window.formatDate = function (dateValue) {
  if (!dateValue || dateValue === '0000-00-00' || dateValue === '0000-00-00 00:00:00' || dateValue === 'N/A') return "N/A";

  try {
    const d = new Date(dateValue);
    if (isNaN(d.getTime())) return "N/A";

    return d.toLocaleDateString('en-GB', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch (e) {
    console.warn('[formatDate] Error parsing date:', dateValue, e);
    return "N/A";
  }
};

/**
 * Safe Rendering Helper
 * Returns value or 'N/A' if null/undefined
 */
window.safe = function (val) {
  return (val === null || val === undefined || val === '') ? 'N/A' : val;
};


/**
 * Global Debug Logger
 */
window.debugLog = function (label, data) {
  console.log(`[DEBUG] ${label}:`, data);
};

// Remove redundant wrap

// Global dashboard stats loader
window.loadDashboardStats = async function () {
  const res = await window.apiFetch('get_dashboard_stats.php');
  if (res.success && res.data) {
    const stats = res.data;
    // Map stats to DOM elements if they exist
    Object.keys(stats).forEach(key => {
      const el = document.querySelector(`.stat-value[data-stat="${key}"]`);
      if (el) {
        el.textContent = stats[key];
        el.classList.add('pulse');
        setTimeout(() => el.classList.remove('pulse'), 500);
      }
    });
  }
};
