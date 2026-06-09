// =============================================
// CONTRACTOR MODULE – MOCK DATA & LOADERS
// =============================================


/* EMERGENCY SAFETY FALLBACK - Phase 1 Migration Fix */
window.APP_DATA = window.APP_DATA || {};
window.APP_DATA.pendingApplications = window.APP_DATA.pendingApplications || [];
window.APP_DATA.gatePassApplications = window.APP_DATA.gatePassApplications || [];
window.APP_DATA.trainingSessions = window.APP_DATA.trainingSessions || [];
window.APP_DATA.sapContractor = window.APP_DATA.sapContractor || {};
window.workmenData = window.workmenData || [];
window.dashboardStats = window.dashboardStats || {
  totalApplications: 0,
  pending: 0,
  approved: 0,
  rejected: 0
};

/**
 * Safe Helper - CRITICAL: Prevents undefined/undefined values
 * USE THIS FOR ALL DATA DISPLAY
 */
function safe(val) {
  return (val === null || val === undefined || val === '' || val === 'undefined' || val === 'null') ? 'N/A' : val;
}
function safeNum(val) {
  const n = parseInt(val, 10);
  return isNaN(n) ? 0 : n;
}
function escapeHtml(val) {
  if (!val) return '';
  return String(val).replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '<', '>': '>', '"': '"', "'": '&#039;'
  }[char]));
}
window.safe = safe;
window.safeNum = safeNum;
window.escapeHtml = escapeHtml;

/**
 * Safe SAP Data Fallback - Returns proper data with no undefined values
 */
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
    status: 'Inactive',
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
    safetyOfficer: 'N/A',
    bankName: 'N/A',
    bankAccount: 'N/A',
    ifsc: 'N/A',
    sapSync: 'N/A'
  };
}

/**
 * Load SAP Contractor Data from API
 */
async function loadSAPContractor(applicationId) {
  try {
    const appId = applicationId || getAppId();
    const res = await apiFetch('contractor/sap_fetch_contractor.php', {
      method: 'POST',
      body: { application_id: appId }
    });
    if (res.success && res.data) {
      window.APP_DATA.sapContractor = res.data;
      return res.data;
    }
    return getSafeSAPData();
  } catch (error) {
    console.error('[loadSAPContractor] failed:', error);
    return getSafeSAPData();
  }
}

/**
 * Force refresh SAP data from database
 */
async function refreshSAPContractor(applicationId) {
  const appId = applicationId || getAppId();
  try {
    const res = await apiFetch('contractor/sap_fetch_contractor.php', {
      method: 'POST',
      body: { application_id: appId, refresh: true }
    });
    if (res.success && res.data) {
      window.APP_DATA.sapContractor = res.data;
      return res.data;
    }
    return getSafeSAPData();
  } catch (error) {
    console.error('[refreshSAPContractor] failed:', error);
    return getSafeSAPData();
  }
}

// Dynamic data loaders (unified, debug-safe)
async function loadSafetyDashboardData() {
  try {
    const res = await apiFetch('get_training_sessions.php');
    APP_DATA.trainingSessions = normalizeArray(res);
  } catch (error) {
    console.error('[loadSafetyDashboardData] failed:', error);
    APP_DATA.trainingSessions = [];
  }
}

async function loadPassDashboardData() {
  try {
    const res = await apiFetch('get_pass_officer_data.php');
    APP_DATA.gatePassApplications = normalizeArray(res);
  } catch (error) {
    console.error('[loadPassDashboardData] failed:', error);
    APP_DATA.gatePassApplications = [];
  }
}

async function loadWelfareDashboardData() {
  try {
    const res = await fetch('api/welfare/get_welfare_applications.php');
    const json = await res.json();
    if (json.success && json.data) {
      window.APP_DATA.pendingApplications = json.data.applications || [];
      window.dashboardStats = json.data.counts || window.dashboardStats;
    }
  } catch (error) {
    console.error('[loadWelfareDashboardData] failed:', error);
  }
}


/**
 * Load dashboard statistics from database
 * Returns dynamic counts for Total, Pending, Approved, Rejected
 */
async function loadDashboardStats() {
  try {
    const res = await apiFetch('get_dashboard_stats.php');
    if (res.success && res.data) {
      window.dashboardStats = res.data;
    }
    console.log('[loadDashboardStats] Final stats:', window.dashboardStats);
    return window.dashboardStats;
  } catch (error) {
    console.error('[loadDashboardStats] failed:', error);
    return window.dashboardStats;
  }
}


/**
 * Get current workflow status for an application
 */
async function getApplicationStatus(applicationId) {
  try {
    const parsed = await apiFetch('get_application_status.php?id=' + encodeURIComponent(applicationId));
    if (parsed.success && parsed.data) {
      return parsed.data.workflow_status || parsed.data.status || 'submitted';
    }
    return 'submitted';
  } catch (error) {
    console.error('[getApplicationStatus] error:', error);
    return 'submitted';
  }
}

/**
 * Submit welfare approval action (approve/reject)
 */
async function submitWelfareAction(applicationId, action, remarks = '') {
  try {
    const parsed = await apiFetch('update_status.php', {
      method: 'POST',
      body: {
        application_id: applicationId,
        action: action,
        remarks: remarks
      }
    });
    console.log('[submitWelfareAction] Response:', parsed);
    if (parsed.success) {
      showToast('✅', action === 'approve' ? 'Application approved successfully' : 'Application rejected');
      // Reload dashboard data
      await loadDashboardStats();
      return true;
    } else {
      showToast('❌', parsed.error || 'Action failed');
      return false;
    }
  } catch (error) {
    console.error('[submitWelfareAction] error:', error);
    showToast('❌', 'Network error');
    return false;
  }
}

// Auto-load data on app init (deferred until DOM is ready)
if (typeof window.APP_DATA === 'undefined') {
  window.APP_DATA = {};
}
document.addEventListener('DOMContentLoaded', function() {
  loadSafetyDashboardData();
  loadPassDashboardData();
  loadWelfareDashboardData();
  loadDashboardStats();
});

// Role configurations (static badges removed)
const ROLE_CONFIG = {
  contractor: {
    label: "Contractor",
    name: "",
    initials: "SC",
    badge: "🏗️ Contractor",
    nav: [
      { group: "MAIN", items: [
        { id: "dashboard", icon: "fas fa-tachometer-alt", label: "Dashboard" },
        { id: "sap-details", icon: "fas fa-database", label: "SAP Details" },
      ]},
      { group: "REGISTRATION", items: [
        { id: "annexure2a", icon: "fas fa-file-alt", label: "Contractor Registration" },
        { id: "annexure3a", icon: "fas fa-file-contract", label: "Contractor Info" },
        { id: "payment", icon: "fas fa-credit-card", label: "Pay Application Fee" },
      ]},
      { group: "ENROLMENT", items: [
        { id: "enrolment", icon: "fas fa-users", label: "Enrol Workmen" },
        { id: "temp-id", icon: "fas fa-id-card", label: "Temporary ID Cards" },
      ]},
      { group: "SAFETY & GATE PASS", items: [
        { id: "safety-training", icon: "fas fa-hard-hat", label: "Safety Training" },
        { id: "training-result", icon: "fas fa-graduation-cap", label: "Training Results" },
        { id: "gatepass-request", icon: "fas fa-door-open", label: "Gate Pass Request" },
      ]},
      { group: "ALERTS", items: [
        { id: "notifications", icon: "fas fa-bell", label: "Notifications" },
      ]},
    ]
  },
  welfare: {
    label: "Welfare User",
    name: "Welfare Department",
    initials: "WU",
    badge: "👥 Welfare",
    nav: [
      { group: "MAIN", items: [
        { id: "dashboard", icon: "fas fa-tachometer-alt", label: "Dashboard" },
      ]},
      { group: "VERIFICATION", items: [
        { id: "welfare-verify", icon: "fas fa-search", label: "Verify Applications" },
        { id: "welfare-approval", icon: "fas fa-check-circle", label: "Approve / Reject" },
      ]},
      { group: "MANAGEMENT", items: [
        { id: "enrolment", icon: "fas fa-users", label: "Enrolment Review" },
        { id: "final-approval", icon: "fas fa-stamp", label: "Final Approval" },
      ]},
      { group: "GATE PASS", items: [
        { id: "pass-officer", icon: "fas fa-id-card-alt", label: "Gate Pass Review" },
        { id: "permanent-pass", icon: "fas fa-door-open", label: "Permanent Pass" },
      ]},
      { group: "ALERTS", items: [
        { id: "notifications", icon: "fas fa-bell", label: "Notifications" },
      ]},
    ]
  },
  authority: {
    label: "Welfare Authority",
    name: "Executing Officer",
    initials: "EO",
    badge: "🏛️ Authority",
    nav: [
      { group: "MAIN", items: [
        { id: "dashboard", icon: "fas fa-tachometer-alt", label: "Dashboard" },
      ]},
      { group: "APPROVALS", items: [
        { id: "welfare-approval", icon: "fas fa-check-circle", label: "Approve/Reject Apps" },
        { id: "safety-training", icon: "fas fa-hard-hat", label: "Training Approval" },
        { id: "final-approval", icon: "fas fa-stamp", label: "Final Approval (ACC)" },
      ]},
      { group: "REPORTS", items: [
        { id: "training-result", icon: "fas fa-chart-bar", label: "Training Results" },
        { id: "permanent-pass", icon: "fas fa-door-open", label: "Issued Gate Passes" },
      ]},
      { group: "ALERTS", items: [
        { id: "notifications", icon: "fas fa-bell", label: "Notifications" },
      ]},
    ]
  },
  safety: {
    label: "Safety Officer",
    name: "Safety Division",
    initials: "SO",
    badge: "🛡️ Safety",
    nav: [
      { group: "MAIN", items: [
        { id: "dashboard", icon: "fas fa-tachometer-alt", label: "Dashboard" },
      ]},
      { group: "SAFETY TRAINING", items: [
        { id: "safety-training", icon: "fas fa-hard-hat", label: "Training Management" },
        { id: "training-result", icon: "fas fa-graduation-cap", label: "Result Processing" },
      ]},
      { group: "GATE PASS", items: [
        { id: "pass-officer", icon: "fas fa-id-card-alt", label: "Verify Gate Pass" },
      ]},
      { group: "ALERTS", items: [
        { id: "notifications", icon: "fas fa-bell", label: "Notifications" },
      ]},
    ]
  },
  pass: {
    label: "Pass Issuing Officer",
    name: "Gate Pass Division",
    initials: "PI",
    badge: "🪪 Pass Officer",
    nav: [
      { group: "MAIN", items: [
        { id: "dashboard", icon: "fas fa-tachometer-alt", label: "Dashboard" },
      ]},
      { group: "GATE PASS", items: [
        { id: "pass-officer", icon: "fas fa-id-card-alt", label: "Verify & Validate" },
        { id: "final-approval", icon: "fas fa-stamp", label: "Welfare Approval" },
        { id: "permanent-pass", icon: "fas fa-door-open", label: "Issue Pass" },
      ]},
      { group: "ALERTS", items: [
        { id: "notifications", icon: "fas fa-bell", label: "Notifications" },
      ]},
    ]
  }
};

