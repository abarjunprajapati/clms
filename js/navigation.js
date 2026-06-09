// ===== GLOBAL NAVIGATION & UTILITY =====

const PAGES = {
  login: 'index.php',
  dashboard: 'pages/contractor/dashboard.php',
  annexure2a: 'pages/contractor/annexure.php',
  annexure3a: 'pages/annexure-3a.php', // Assuming this wasn't moved, or update if it was
  welfare_verify: 'pages/welfare/verification.php',
  welfare_approve: 'pages/welfare/approval.php',
  enrolment: 'pages/contractor/add-worker.php',
  temp_id: 'pages/temp-id-card.php',
  safety_request: 'pages/contractor/training.php',
  safety_approval: 'pages/safety-training-approval.php',
  safety_result: 'pages/safety-training-result.php',
  payment: 'pages/payment.php',
  gatepass_6a: 'pages/contractor/gatepass.php',
  pass_officer: 'pages/pass-officer-verification.php',
  welfare_pass: 'pages/welfare/gatepass.php',
  acc_approval: 'pages/acc-approval.php',
  permanent_pass: 'pages/permanent-gatepass.php',
  notifications: 'pages/notifications.php',
  resubmission: 'pages/resubmission.php',
};

function navigateToPage(page) {
  if (PAGES[page]) navigateTo(PAGES[page]);
}

function goBack() { window.history.back(); }

// Show/hide modal
function showModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('show');
}
function hideModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('show');
}

// Format date
function formatDate(d = new Date()) {
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Add slide-up animation
const styleEl = document.createElement('style');
styleEl.textContent = `
  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
`;
document.head.appendChild(styleEl);

