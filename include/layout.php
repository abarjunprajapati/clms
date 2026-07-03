<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/onboarding_status.php';
/**
 * Unified Layout for CLMS
 * Handles role-based sidebars and topbar.
 */
function renderLayout($page_title, $content_callback, $role, $name) {
    global $conn;
    $roleLabel = ucwords(str_replace('_', ' ', $role));
    $userCode = '';
    if ($role === 'contractor') {
        $userCode = $_SESSION['contractor_id'] ?? $_SESSION['vendor_code'] ?? '';
    } elseif ($role === 'customer') {
        $userCode = $_SESSION['customer_code'] ?? '';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= get_csrf_token() ?>">
  <script>
    window.CLMS_BASE_URL = "<?= BASE_URL ?>";
    window.CLMS_CSRF_TOKEN = "<?= get_csrf_token() ?>";
    (function() {
      if (!window.fetch || window.__clmsFetchSecured) return;
      const nativeFetch = window.fetch.bind(window);
      window.fetch = function(input, init) {
        init = init || {};
        const method = String(init.method || 'GET').toUpperCase();
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        const sameOrigin = !/^https?:\/\//i.test(url) || url.indexOf(window.location.origin) === 0;
        if (sameOrigin && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
          const headers = new Headers(init.headers || {});
          if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', window.CLMS_CSRF_TOKEN || '');
          init.headers = headers;
          init.credentials = init.credentials || 'same-origin';
        }
        return nativeFetch(input, init);
      };
      window.__clmsFetchSecured = true;
    })();
  </script>
  <title><?= $page_title ?> – CLMS</title>
  <style>input[type='text']:not([id*='pass']):not([name*='pass']), input[type='search'], textarea { text-transform: uppercase; }</style>
  <script>
  document.addEventListener('input', function(e) {
    if (e.target.type === 'password' || e.target.id.toLowerCase().includes('pass') || (e.target.name && e.target.name.toLowerCase().includes('pass'))) return;
    if ((e.target.tagName === 'INPUT' && (e.target.type === 'text' || e.target.type === 'search')) || e.target.tagName === 'TEXTAREA') {
      let start = e.target.selectionStart;
      let end = e.target.selectionEnd;
      e.target.value = e.target.value.toUpperCase();
      e.target.setSelectionRange(start, end);
    }
  });
  </script>
  <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css?v=<?= filemtime(dirname(__DIR__) . '/css/style.css') ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Global alert override using SweetAlert
    window.originalAlert = window.alert;
    window.alert = function(msg) {
      if (typeof Swal !== 'undefined') {
        let icon = 'info';
        let title = 'Message';
        let msgStr = String(msg).toLowerCase();
        if (msgStr.includes('error') || msgStr.includes('fail') || msgStr.includes('required')) {
          icon = 'error';
          title = 'Error';
        } else if (msgStr.includes('success') || msgStr.includes('successfully')) {
          icon = 'success';
          title = 'Success';
        } else if (msgStr.includes('warning') || msgStr.includes('exceed')) {
          icon = 'warning';
          title = 'Warning';
        }
        Swal.fire({
          icon: icon,
          title: title,
          text: msg,
          confirmButtonColor: '#1e3a8a'
        });
      } else {
        window.originalAlert(msg);
      }
    };
  </script>
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Smooth transition for sidebar */
    .sidebar-item { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .sidebar-item:hover { transform: translateX(5px); }
    
    /* Global DataTable Styling to match Glass Theme */
    .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; margin-left: 8px; outline: none; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: #6366f1; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 6px !important; padding: 5px 12px !important; margin: 0 2px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #6366f1 !important; color: white !important; border: none !important; }
    .dataTables_wrapper .dataTables_info { font-size: 13px; color: #64748b; }
    table.dataTable thead th, table.dataTable thead td { border-bottom: 1px solid #e2e8f0; }
    table.dataTable.no-footer { border-bottom: 1px solid #e2e8f0; }
    .topbar-right { flex-shrink: 0; gap: 12px; min-width: 0; }
    .topbar-user-card {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 260px;
      max-width: 360px;
      padding: 7px 10px;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    }
    .topbar-user-card .user-avatar { flex: 0 0 38px; }
    .topbar-user-meta { min-width: 0; flex: 1; line-height: 1.2; }
    .topbar-user-name {
      font-size: 13px;
      font-weight: 800;
      color: #1f2937;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    .topbar-user-subline {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 4px;
      align-items: center;
    }
    .topbar-user-chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 7px;
      border-radius: 999px;
      background: #f1f5f9;
      color: #475569;
      font-size: 10.5px;
      font-weight: 800;
      white-space: nowrap;
    }
    .topbar-user-chip.code {
      background: #eaf3ff;
      color: #1e3a5f;
      border: 1px solid #bfdbfe;
    }
    .topbar-logout-btn {
      background: #ef4444;
      color: white;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 800;
      text-decoration: none;
      transition: all 0.2s ease;
      cursor: pointer;
      box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
      white-space: nowrap;
    }
    .topbar-logout-btn:hover { background:#dc2626; color:#fff; transform:translateY(-1px); }
    @media (max-width: 900px) {
      .topbar { padding: 0 14px; }
      .topbar-title { font-size: 14px; }
      .topbar-user-card { min-width: 190px; max-width: 240px; }
      .topbar-logout-btn span { display: none; }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-brand">
    <div class="mobile-menu-toggle" id="mobileMenuBtn">
      <i class="fas fa-bars"></i>
    </div>
    <div class="topbar-logo"><img src="<?= BASE_URL ?>uploads/logo/logo.png" alt="Logo" style="width: 35px; height: 35px; object-fit: contain;"></div>
    <div>
      <div class="topbar-title">Contractor Labour Management System</div>
      <div class="topbar-subtitle"><?= ucfirst(str_replace('_', ' ', $role)) ?> Portal</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-notif">
      <i class="far fa-bell" style="font-size: 20px;"></i>
      <span class="notif-badge">3</span>
    </div>
    <div class="topbar-user topbar-user-card">
      <div class="user-avatar" style="background: var(--gray-50); border: 1.5px solid var(--primary-bright); color: var(--primary-bright); box-shadow: var(--shadow-sm); font-size: 14px; font-weight: 800; border-radius: 12px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
        <?= strtoupper(substr($name, 0, 2)) ?>
      </div>
      <div class="user-info topbar-user-meta">
        <div class="topbar-user-name" title="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></div>
        <div class="topbar-user-subline">
          <span class="topbar-user-chip"><i class="fas fa-user-tag"></i> Role: <?= htmlspecialchars($roleLabel) ?></span>
          <?php if (!empty($userCode)): ?>
            <span class="topbar-user-chip code"><i class="fas fa-barcode"></i> Code: <?= htmlspecialchars($userCode) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>api/logout.php" class="topbar-logout-btn">
      <i class="fas fa-power-off"></i> <span>Logout</span>
    </a>
  </div>
</div>

<div class="layout-wrapper">
  <!-- SIDEBAR OVERLAY -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- DYNAMIC SIDEBAR -->
  <div class="sidebar">
    <?php renderSidebar($role); ?>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <?php 
    $success_msg = $_GET['success'] ?? $_SESSION['success'] ?? null;
    $error_msg = $_GET['error'] ?? $_SESSION['error'] ?? null;
    unset($_SESSION['success'], $_SESSION['error']);
    ?>

    <?php if($success_msg): ?>
      <script>
        window.addEventListener('DOMContentLoaded', () => {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: <?= json_encode($success_msg) ?>,
            confirmButtonColor: '#1e3a8a'
          });
        });
      </script>
    <?php endif; ?>

    <?php if($error_msg): ?>
      <script>
        window.addEventListener('DOMContentLoaded', () => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?= json_encode($error_msg) ?>,
            confirmButtonColor: '#ef4444'
          });
        });
      </script>
    <?php endif; ?>

    <?php $content_callback(); ?>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="<?= BASE_URL ?>js/utils.js"></script>
<script>
  // Active link highlighting
  document.querySelectorAll('.sidebar-item').forEach(item => {
    if (item.href === window.location.href) {
      item.classList.add('active');
    }
  });

  // Mobile sidebar toggle
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  
  if (mobileBtn && sidebar && overlay) {
    function toggleSidebar() {
      sidebar.classList.toggle('mobile-open');
      overlay.classList.toggle('active');
      document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
    }
    mobileBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
  }

  // Global DataTables Initialization with Auto S.No
  $(document).ready(function() {
      function getDataTableColumnCount(table) {
          var count = 0;
          table.find('thead tr:last th, thead tr:last td').each(function() {
              count += parseInt($(this).attr('colspan') || '1', 10);
          });
          return count;
      }

      function prepareTableForDataTables(table) {
          var columnCount = getDataTableColumnCount(table);
          if (!columnCount) return {};

          var options = {};
          table.find('tbody tr').each(function() {
              var row = $(this);
              var cells = row.children('td, th');
              if (cells.length === columnCount) return;

              if (cells.length === 1 && cells.first().is('[colspan]')) {
                  var text = $.trim(cells.first().text());
                  if (text) {
                      options.emptyTable = text;
                      options.zeroRecords = text;
                  }
                  row.remove();
                  return;
              }

              if (cells.length < columnCount) {
                  for (var i = cells.length; i < columnCount; i++) {
                      row.append('<td></td>');
                  }
                  return;
              }

              cells.slice(columnCount).remove();
          });

          return options;
      }

      $('.data-table').each(function() {
          var table = $(this);
          if ($.fn.DataTable.isDataTable(table)) return;

          var tableMessages = prepareTableForDataTables(table);
          var t = table.DataTable({
              "pageLength": 10,
              "ordering": false,
              "language": {
                  "search": "<i class='fas fa-search'></i> Filter:",
                  "lengthMenu": "Show _MENU_ entries",
                  "emptyTable": tableMessages.emptyTable || "No data available in table",
                  "zeroRecords": tableMessages.zeroRecords || "No matching records found"
              }
          });

          // DataTable initialized
      });
  });

  // Auto-hide alerts after 5 seconds
  setTimeout(() => {
    const alert = document.getElementById('auto-hide-alert');
    if (alert) {
      alert.style.transition = 'opacity 0.5s ease-out';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }
  }, 5000);
</script>
</body>
</html>
<?php
}

function renderSidebar($role) {
    global $conn;
    echo '<div class="sidebar-section">';
    echo '<div class="sidebar-section-label">Main</div>';
    $dashboards = [
        'super_admin'   => BASE_URL . 'pages/admin/dashboard.php',
        'admin'         => BASE_URL . 'pages/admin/dashboard.php',
        'welfare_admin' => BASE_URL . 'pages/welfare/admin_dashboard.php',
        'welfare_user'  => BASE_URL . 'pages/welfare/dashboard.php',
        'welfare'       => BASE_URL . 'pages/welfare/dashboard.php',
        'contractor'    => BASE_URL . 'pages/contractor/dashboard.php',
        'safety_user'   => BASE_URL . 'pages/safety/dashboard.php',
        'safety'        => BASE_URL . 'pages/safety/dashboard.php',
        'front_line_user' => BASE_URL . 'pages/frontline/dashboard.php',
        'frontline'     => BASE_URL . 'pages/frontline/dashboard.php',
        'customer'      => BASE_URL . 'pages/customer/dashboard.php',
        'pass_user'     => BASE_URL . 'pages/welfare/pass_issuer_dashboard.php',
        'pass_issuer'   => BASE_URL . 'pages/welfare/pass_issuer_dashboard.php',
        'execution'     => BASE_URL . 'pages/execution/dashboard.php',
        'execution_officer' => BASE_URL . 'pages/execution/dashboard.php'
    ];
    $dash_link = $dashboards[$role] ?? (BASE_URL . 'pages/contractor/dashboard.php');

    echo '<a href="' . $dash_link . '" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
    
    switch ($role) {
        case 'super_admin':
        case 'admin':
            $ab = BASE_URL . 'pages/admin/';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">System Control</div>';
            echo '<a href="'.$ab.'dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="'.$ab.'users.php" class="sidebar-item"><i class="fas fa-users-cog"></i> User Management</a>';
            echo '<a href="'.$ab.'roles.php" class="sidebar-item"><i class="fas fa-user-shield"></i> Role Control</a>';
            echo '<a href="'.$ab.'permissions.php" class="sidebar-item"><i class="fas fa-key"></i> Permissions</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Workflow Governance</div>';
            echo '<a href="'.$ab.'workflow_monitor.php" class="sidebar-item"><i class="fas fa-eye"></i> Workflow Monitoring</a>';
            echo '<a href="'.$ab.'workflow_control.php" class="sidebar-item"><i class="fas fa-gamepad"></i> Workflow Control</a>';
            echo '<a href="'.$ab.'documents_monitor.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Document Flow</a>';
            echo '<a href="'.$ab.'training_monitor.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Training Flow</a>';
            echo '<a href="'.$ab.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card"></i> Gate Pass Flow</a>';
            echo '<a href="'.$ab.'sap_sync_logs.php" class="sidebar-item"><i class="fas fa-sync"></i> SAP Sync Logs</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Operations Oversight</div>';
            echo '<a href="'.$ab.'worker_management.php" class="sidebar-item"><i class="fas fa-user-clock"></i> Worker Lifecycle</a>';
            echo '<a href="'.$ab.'contractor_control.php" class="sidebar-item"><i class="fas fa-building-circle-exclamation"></i> Contractor Control</a>';
            echo '<a href="'.$ab.'execution_management.php" class="sidebar-item"><i class="fas fa-link"></i> Execution Mapping</a>';
            echo '<a href="'.$ab.'compliance_dashboard.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Dashboard</a>';
            echo '<a href="'.$ab.'attendance_dashboard.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance Dashboard</a>';
            echo '<a href="'.$ab.'biometric_dashboard.php" class="sidebar-item"><i class="fas fa-fingerprint"></i> Biometric Governance</a>';
            echo '<a href="'.$ab.'pass_limits.php" class="sidebar-item"><i class="fas fa-sliders-h"></i> Pass Category Limit</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/certified_wages.php" class="sidebar-item"><i class="fas fa-indian-rupee-sign"></i> Certified Wage Rate</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/labour_license_threshold.php" class="sidebar-item"><i class="fas fa-scale-balanced"></i> Labour License Threshold</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/temporary_pass_validity.php" class="sidebar-item"><i class="fas fa-calendar-day"></i> Temporary Pass Validity</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/age_range_mapping.php" class="sidebar-item"><i class="fas fa-user-clock"></i> Age Range Mapping</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/gate_pass_document_master.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Gate Pass Document Master</a>';
// echo '<a href="'.BASE_URL.'pages/welfare/payment_gateway.php" class="sidebar-item"><i class="fas fa-credit-card"></i> Payment Gateway / QR</a>';
            echo '<a href="'.$ab.'master_data.php" class="sidebar-item"><i class="fas fa-database"></i> Master Data</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Statutory Compliance</div>';
            echo '<a href="'.BASE_URL.'pages/welfare/muster_roll_monitor.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Muster Roll Verification</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/check_esi_compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Check ESI Compliance</a>';
            echo '<a href="'.BASE_URL.'pages/welfare/check_epf_compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Check EPF Compliance</a>';

            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Enterprise Governance</div>';
            echo '<a href="'.BASE_URL.'pages/amc/dashboard.php" class="sidebar-item"><i class="fas fa-handshake"></i> AMC & SLA</a>';
            echo '<a href="'.BASE_URL.'pages/payments/invoices.php" class="sidebar-item"><i class="fas fa-file-invoice-dollar"></i> Payment Governance</a>';
            echo '<a href="'.BASE_URL.'pages/temporary/request.php" class="sidebar-item"><i class="fas fa-user-clock"></i> Temp Workforce Pass</a>';
            echo '<a href="'.$ab.'policy_monitor.php" class="sidebar-item"><i class="fas fa-microchip"></i> Policy Engine</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">System Health & Logs</div>';
            echo '<a href="'.$ab.'notifications_logs.php" class="sidebar-item"><i class="fas fa-bell-slash"></i> Notifications Logs</a>';
            echo '<a href="'.$ab.'alerts_dashboard.php" class="sidebar-item"><i class="fas fa-exclamation-triangle"></i> Alerts Dashboard</a>';
            echo '<a href="'.$ab.'system_health.php" class="sidebar-item"><i class="fas fa-heartbeat"></i> System Health</a>';
            echo '<a href="'.$ab.'audit_logs.php" class="sidebar-item"><i class="fas fa-history"></i> Audit Logs</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Reports & Data</div>';
            echo '<a href="'.$ab.'reports.php" class="sidebar-item"><i class="fas fa-file-medical-alt"></i> Reports</a>';
            echo '<a href="'.$ab.'data_export.php" class="sidebar-item"><i class="fas fa-download"></i> Data Export</a>';
            echo '<a href="'.$ab.'settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Settings</a>';
            break;

        case 'welfare_admin':
            $wb = BASE_URL . 'pages/welfare/';
            $ab = BASE_URL . 'pages/admin/';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">User & System Control</div>';
            echo '<a href="'.$wb.'admin_dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Admin Home</a>';
            echo '<a href="'.$ab.'users.php" class="sidebar-item"><i class="fas fa-users-cog"></i> User Management</a>';
            echo '<a href="'.$ab.'create_user.php" class="sidebar-item"><i class="fas fa-user-plus"></i> Create User</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Master Data Management</div>';
            echo '<a href="'.$wb.'pass_limits.php" class="sidebar-item"><i class="fas fa-sliders-h"></i> Pass Category Limit</a>';
            echo '<a href="'.$wb.'certified_wages.php" class="sidebar-item"><i class="fas fa-indian-rupee-sign"></i> Certified Wage Rate</a>';
            echo '<a href="'.$wb.'labour_license_threshold.php" class="sidebar-item"><i class="fas fa-scale-balanced"></i> Labour License Threshold</a>';
            echo '<a href="'.$wb.'temporary_pass_validity.php" class="sidebar-item"><i class="fas fa-calendar-day"></i> Temporary Pass Validity</a>';
            echo '<a href="'.$wb.'education_correction.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Education Job Profile</a>';
            echo '<a href="'.$wb.'nationality_master.php" class="sidebar-item"><i class="fas fa-globe"></i> Nationality Masters</a>';
            echo '<a href="'.$wb.'age_range_mapping.php" class="sidebar-item"><i class="fas fa-user-clock"></i> Age Range Mapping</a>';
            echo '<a href="'.$wb.'gate_pass_document_master.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Gate Pass Document Master</a>';
            echo '<a href="'.$wb.'training_type_master.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Training Type Master</a>';
            echo '<a href="'.$wb.'training_venue_master.php" class="sidebar-item"><i class="fas fa-location-dot"></i> Training Venue Master</a>';
//             echo '<a href="'.$wb.'payment_gateway.php" class="sidebar-item"><i class="fas fa-credit-card"></i> Payment Gateway / QR</a>';
            echo '<a href="'.$wb.'temp_pass_control.php" class="sidebar-item"><i class="fas fa-clock"></i> Temp Pass Control</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Monitoring & Oversight</div>';
            echo '<a href="'.$wb.'entity_directory.php" class="sidebar-item"><i class="fas fa-address-book"></i> Contractor / Customer Data</a>';
            echo '<a href="'.$wb.'approve_3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info Verification </a>';
            echo '<a href="'.$wb.'enrollment_monitor.php" class="sidebar-item"><i class="fas fa-users-viewfinder"></i> Worker Monitor</a>';
            echo '<a href="'.$wb.'training_monitor.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Training Monitor</a>';
            echo '<a href="'.$wb.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card-clip"></i> Gate Pass Monitor</a>';
            echo '<a href="'.$wb.'acc_tracking.php" class="sidebar-item"><i class="fas fa-fingerprint"></i> ACC Monitor</a>';
            echo '<a href="'.$wb.'productivity_dashboard.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Productivity Dashboard</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Statutory Compliance</div>';
            echo '<a href="'.$wb.'muster_roll_monitor.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Muster Roll Verification</a>';
            echo '<a href="'.$wb.'esi_contribution_monitor.php" class="sidebar-item"><i class="fas fa-hand-holding-dollar"></i> ESI Contribution Monitor</a>';
            echo '<a href="'.$wb.'check_esi_compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Check ESI Compliance</a>';
            echo '<a href="'.$wb.'check_epf_compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Check EPF Compliance</a>';
            echo '<a href="'.$wb.'lwf_compliance_checking.php" class="sidebar-item"><i class="fas fa-clipboard-check"></i> LWF Compliance Checking</a>';
            echo '<a href="'.$wb.'lwf_master.php" class="sidebar-item"><i class="fas fa-sliders-h"></i> LWF Rate Master</a>';

            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Compliance & Lifecycle</div>';
            echo '<a href="'.$wb.'compliance_monitor.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Monitor</a>';
            echo '<a href="'.$wb.'blocking_control.php" class="sidebar-item"><i class="fas fa-building-circle-exclamation"></i> Contractor Control</a>';
            // echo '<a href="'.$wb.'worker_block.php" class="sidebar-item"><i class="fas fa-user-slash"></i> Worker Blocking</a>';
            echo '<a href="'.$wb.'noc_approvals.php" class="sidebar-item"><i class="fas fa-exchange-alt"></i> NOC Approvals</a>';
            echo '<a href="'.$wb.'verification_history.php" class="sidebar-item"><i class="fas fa-history"></i> Contractor History</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Reports & System</div>';
            echo '<a href="'.$wb.'reports.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Reports</a>';
            echo '<a href="'.$ab.'audit_logs.php" class="sidebar-item"><i class="fas fa-history"></i> Audit Logs</a>';
            echo '<a href="'.$wb.'sap_logs.php" class="sidebar-item"><i class="fas fa-sync"></i> SAP Integration</a>';
            break;

        case 'welfare_user':
        case 'welfare':
            $wb = BASE_URL . 'pages/welfare/';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Verification Desk</div>';
            echo '<a href="'.$wb.'approve_contractors.php" class="sidebar-item"><i class="fas fa-building-circle-check"></i> Pending Customer Approval</a>';
            echo '<a href="'.$wb.'entity_directory.php" class="sidebar-item"><i class="fas fa-address-book"></i> Contractor / Customer Data</a>';
            echo '<a href="'.$wb.'approve_3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info Verification (3A)</a>';
            echo '<a href="'.$wb.'enrollment_monitor.php" class="sidebar-item"><i class="fas fa-users-viewfinder"></i> Enrollment Verification</a>';
            echo '<a href="'.$wb.'verify_documents.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Document Verification</a>';
            echo '<a href="'.$wb.'training_monitor.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Safety Training</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Pass Issuance</div>';
            echo '<a href="'.$wb.'gatepass-6a.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Gate Pass Request</a>';
            echo '<a href="'.$wb.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card-clip"></i> Gate Pass Approval</a>';
            echo '<a href="'.$wb.'temp_pass_approvals.php" class="sidebar-item"><i class="fas fa-clipboard-check"></i> Temporary Pass Approval</a>';
            echo '<a href="'.$wb.'issue_temp_pass.php" class="sidebar-item"><i class="fas fa-clock"></i> Temporary Pass Issue</a>';
            echo '<a href="'.$wb.'acc_generation.php" class="sidebar-item"><i class="fas fa-fingerprint"></i> Permanent ACC Approval</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Worker Lifecycle</div>';
            echo '<a href="'.$wb.'blocking_control.php" class="sidebar-item"><i class="fas fa-building-circle-exclamation"></i> Contractor Control</a>';
            // echo '<a href="'.$wb.'worker_block.php" class="sidebar-item"><i class="fas fa-user-slash"></i> Worker Blocking</a>';
            echo '<a href="'.$wb.'noc_approvals.php" class="sidebar-item"><i class="fas fa-exchange-alt"></i> NOC Approvals</a>';
            echo '<a href="'.$wb.'acc_return_queue.php" class="sidebar-item"><i class="fas fa-undo"></i> Relieving Management</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Statutory Compliance</div>';
            echo '<a href="'.$wb.'muster_roll_monitor.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Muster Roll Verification</a>';
            echo '<a href="'.$wb.'esi_contribution_monitor.php" class="sidebar-item"><i class="fas fa-hand-holding-dollar"></i> ESI Contribution Monitor</a>';
            echo '<a href="'.$wb.'check_esi_compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Check ESI Compliance</a>';
            echo '<a href="'.$wb.'check_epf_compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Check EPF Compliance</a>';
            echo '<a href="'.$wb.'lwf_compliance_checking.php" class="sidebar-item"><i class="fas fa-clipboard-check"></i> LWF Compliance Checking</a>';
            echo '<a href="'.$wb.'monthly_compliance_approvals.php" class="sidebar-item"><i class="fas fa-certificate text-success"></i> Monthly Compliance Approvals</a>';
            echo '<a href="'.$wb.'esic_wage_limit.php" class="sidebar-item"><i class="fas fa-cog"></i> ESIC Wage Limit</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Compliance & Monitor</div>';
            echo '<a href="'.$wb.'compliance_monitor.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Verification</a>';
            echo '<a href="'.$wb.'certified_wages.php" class="sidebar-item"><i class="fas fa-indian-rupee-sign"></i> Certified Wage Rate</a>';
            echo '<a href="'.$wb.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card"></i> Gate Pass Monitoring</a>';
            echo '<a href="'.$wb.'attendance_monitor.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance Monitor</a>';
            echo '<a href="'.$wb.'verification_history.php" class="sidebar-item"><i class="fas fa-history"></i> Contractor History</a>';
            echo '<a href="'.$wb.'reports.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Reports</a>';
            echo '<a href="'.$wb.'sap_logs.php" class="sidebar-item"><i class="fas fa-sync"></i> SAP Integration</a>';
            break;

        case 'contractor':
            $user_id = $_SESSION['user_id'] ?? 0;
            $contractor = db_single($conn, "SELECT status, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
            $sap_code = $contractor['vendor_code'] ?? ($_SESSION['contractor_id'] ?? $_SESSION['vendor_code'] ?? '');
            $onboardingComplete = clms_onboarding_is_complete($conn, 'contractor', $sap_code, $user_id);

            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Contractor Lifecycle</div>';
            echo '<a href="annexure-2a.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> CLMS Enrolment Request</a>';

            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Enrolment for Entry Pass</div>';
            echo '<a href="enrolment-4a.php?type=contractor" class="sidebar-item"><i class="fas fa-user-check"></i> Contractor</a>';
            echo '<a href="enrolment-4a.php?type=representative" class="sidebar-item"><i class="fas fa-user-tie"></i> Representative</a>';
            echo '<a href="enrolment-4a.php?type=supervisor" class="sidebar-item"><i class="fas fa-user-shield"></i> Supervisor</a>';
            echo '<a href="enrolment-4a.php?type=workmen" class="sidebar-item"><i class="fas fa-users"></i> Workmen</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Overview & History</div>';
            echo '<a href="welfare-actions.php" class="sidebar-item"><i class="fas fa-clock-rotate-left"></i> Welfare Action History</a>';
            
            if ($onboardingComplete) {
                // echo '<a href="dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
                echo '<a href="profile.php" class="sidebar-item"><i class="fas fa-id-card"></i> Basic Details</a>';
                
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label">SAFETY</div>';
                echo '<a href="training_request.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Safety Training Request</a>';
                echo '<a href="payment.php" class="sidebar-item"><i class="fas fa-credit-card"></i> Pending Fee Payment</a>';
                echo '<a href="book_safety_training.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Book Safety Training</a>';
                
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Workforce Management</div>';
                echo '<a href="gatepass-6a.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Gate Pass</a>';
                echo '<a href="gatepass-reupload.php" class="sidebar-item"><i class="fas fa-file-circle-exclamation"></i> Re-upload Gate Pass Docs</a>';
                echo '<a href="pass_status.php" class="sidebar-item"><i class="fas fa-id-card"></i> ACC Card</a>';
                echo '<a href="../worker/block_unblock.php" class="sidebar-item"><i class="fas fa-user-slash"></i> Worker Blocking</a>';
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Statutory Compliance</div>';
                echo '<a href="muster_roll.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Muster Roll</a>';
                echo '<a href="esi_contribution.php" class="sidebar-item"><i class="fas fa-hand-holding-dollar"></i> ESI Contribution</a>';
                echo '<a href="esi_ecr_pf_upload.php" class="sidebar-item"><i class="fas fa-file-upload"></i> ESI ECR & PF Contribution Upload</a>';
                echo '<a href="esi_compliance_check.php" class="sidebar-item"><i class="fas fa-shield-check"></i> ESI Compliance Status</a>';
                echo '<a href="epf_compliance.php" class="sidebar-item"><i class="fas fa-file-invoice-dollar"></i> EPF Compliance</a>';
                echo '<a href="lwf_compliance.php" class="sidebar-item"><i class="fas fa-balance-scale"></i> LWF Compliance</a>';
                echo '<a href="monthly_compliance_certificate.php" class="sidebar-item"><i class="fas fa-certificate text-success"></i> Monthly Compliance Cert</a>';
                echo '<a href="lwf_rate_view.php" class="sidebar-item"><i class="fas fa-indian-rupee-sign"></i> LWF Rate & Status</a>';
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Operations & Compliance</div>';
                echo '<a href="attendance.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance</a>';
                echo '<a href="noc_management.php" class="sidebar-item"><i class="fas fa-exchange-alt text-warning"></i> NOC Management</a>';
                echo '<a href="compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Monitor</a>';
                echo '<a href="documents.php" class="sidebar-item"><i class="fas fa-folder-open"></i> Documents</a>';
                echo '<a href="reports.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Reports</a>';
            } else {
                echo '<div class="sidebar-item text-muted" style="font-size:12px; padding:10px 15px; background:rgba(0,0,0,0.03); margin-top:10px; border-radius:8px;">';
                echo '<i class="fas fa-lock me-2"></i> Once Contractor Registration is approved, the corresponding dashboard and system modules will be unlocked and accessible.';
                echo '</div>';
            }
            break;

        case 'front_line_user':
        case 'frontline':
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Execution Desk</div>';
            echo '<a href="../frontline/dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="../frontline/entry_validation.php" class="sidebar-item"><i class="fas fa-sign-in-alt text-success"></i> Gate Entry Validation</a>';
            echo '<a href="../frontline/exit_validation.php" class="sidebar-item"><i class="fas fa-sign-out-alt text-danger"></i> Gate Exit Validation</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Monitoring</div>';
            echo '<a href="../frontline/active_pass.php" class="sidebar-item"><i class="fas fa-id-badge text-info"></i> Active Pass List</a>';
            echo '<a href="../frontline/blocked_workers.php" class="sidebar-item"><i class="fas fa-user-slash text-danger"></i> Blocked Workers</a>';
            echo '<a href="../frontline/expired_pass.php" class="sidebar-item"><i class="fas fa-exclamation-triangle text-warning"></i> Expired Pass Alerts</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Records</div>';
            echo '<a href="../frontline/logs.php" class="sidebar-item"><i class="fas fa-history"></i> Entry/Exit Logs</a>';
            echo '<a href="../frontline/manual_override.php" class="sidebar-item"><i class="fas fa-unlock-alt text-warning"></i> Manual Override</a>';
            echo '<a href="../frontline/reports.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Reports</a>';
            break;

        case 'pass_user':
        case 'pass_issuer':
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Pass Issuance Desk</div>';
            echo '<a href="pass_issuer_dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="gatepass-6a.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Gate Pass Request</a>';
            echo '<a href="verify_documents.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Verify Documents</a>';
            echo '<a href="pending_requests.php" class="sidebar-item"><i class="fas fa-list-ul"></i> Pending Pass Requests</a>';
            echo '<a href="issue_temp_pass.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Temporary Pass Issue</a>';
            echo '<a href="acc_generation.php" class="sidebar-item"><i class="fas fa-microchip"></i> ACC Number Generation</a>';
            // echo '<a href="issue_acc_pass.php" class="sidebar-item"><i class="fas fa-id-card-clip"></i> Permanent Pass (ACC)</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Management</div>';
            echo '<a href="pass_status.php" class="sidebar-item"><i class="fas fa-satellite-dish"></i> Pass Status Tracking</a>';
            echo '<a href="reupload_cases.php" class="sidebar-item"><i class="fas fa-upload"></i> Rejected / Re-upload</a>';
            echo '<a href="pass_validity.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Pass Validity Management</a>';
            echo '<a href="reports.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Reports</a>';
            break;

        case 'customer':
            $cb = BASE_URL . 'pages/customer/';
            $cp = BASE_URL . 'pages/contractor/';
            $customerCode = $_SESSION['customer_code'] ?? $_SESSION['contractor_id'] ?? '';
            $onboardingComplete = clms_onboarding_is_complete($conn, 'customer', $customerCode, $_SESSION['user_id'] ?? 0);
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Customer Portal</div>';
            echo '<a href="'.$cb.'annexure-3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info</a>';
            echo '<a href="'.$cb.'welfare-actions.php" class="sidebar-item"><i class="fas fa-clock-rotate-left"></i> Welfare Action History</a>';

            if (!$onboardingComplete) {
                echo '<div class="sidebar-item text-muted" style="font-size:12px; padding:10px 15px; background:rgba(0,0,0,0.03); margin-top:10px; border-radius:8px;">';
                echo '<i class="fas fa-lock me-2"></i> Dashboard and additional modules will unlock once Contractor Info is approved.';
                echo '</div>';
                break;
            }

            echo '<a href="'.$cb.'dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="'.$cb.'profile.php" class="sidebar-item"><i class="fas fa-id-card"></i> Basic Details</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Workforce Monitoring</div>';
            echo '<a href="'.$cp.'enrolment-4a.php?type=workmen" class="sidebar-item"><i class="fas fa-users"></i> Enrolment for Entry Pass</a>';
            echo '<a href="'.$cp.'enrolment-4a.php?type=retraining" class="sidebar-item"><i class="fas fa-arrows-rotate"></i> Re-Training Workmen</a>';
            echo '<a href="'.$cp.'training_request.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Safety Training</a>';
            echo '<a href="'.$cp.'book_safety_training.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Book Safety Training</a>';
            echo '<a href="'.$cp.'payment.php" class="sidebar-item"><i class="fas fa-credit-card"></i> Payment</a>';
            echo '<a href="'.$cp.'gatepass-6a.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Gate Pass</a>';
            echo '<a href="'.$cp.'gatepass-reupload.php" class="sidebar-item"><i class="fas fa-file-circle-exclamation"></i> Re-upload Gate Pass Docs</a>';
            echo '<a href="'.$cp.'pass_status.php" class="sidebar-item"><i class="fas fa-fingerprint"></i> ACC Card</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Operations & Compliance</div>';
            echo '<a href="'.$cp.'attendance.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance</a>';
            echo '<a href="'.$cp.'compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Monitor</a>';
            echo '<a href="'.$cp.'documents.php" class="sidebar-item"><i class="fas fa-folder-open"></i> Documents</a>';
            echo '<a href="'.$cp.'reports.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Reports</a>';
            break;
        case 'safety_user':
        case 'safety':
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Training Engine</div>';
            echo '<a href="dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="enrollment_approval.php" class="sidebar-item"><i class="fas fa-user-check"></i> Enrollment Approval Inbox</a>';
            echo '<a href="training_class_master.php" class="sidebar-item"><i class="fas fa-calendar-plus"></i>Training Batch Creation</a>';
            // echo '<a href="training_requests.php" class="sidebar-item"><i class="fas fa-envelope-open-text"></i> Training Requests</a>';
            echo '<a href="training_schedule.php" class="sidebar-item"><i class="fas fa-calendar-alt"></i> Training Schedule</a>';
            echo '<a href="upcoming_sessions.php" class="sidebar-item"><i class="fas fa-clock"></i> Upcoming Sessions</a>';
            // echo '<a href="conduct_results.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Conduct & Results</a>';
            echo '<a href="training_status.php" class="sidebar-item"><i class="fas fa-user-check"></i> Training Status</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Safety Masters</div>';
            echo '<a href="training_location_master.php" class="sidebar-item"><i class="fas fa-location-dot"></i> Location Master</a>';
            echo '<a href="instructor_master.php" class="sidebar-item"><i class="fas fa-person-chalkboard"></i> Instructor Master</a>';
            echo '<a href="safety_training_type_master.php" class="sidebar-item"><i class="fas fa-list-check"></i> Training Type Master</a>';
            echo '<a href="training_fee_master.php" class="sidebar-item"><i class="fas fa-indian-rupee-sign"></i> Training Fee Master</a>';
            echo '<a href="training_language_master.php" class="sidebar-item"><i class="fas fa-language"></i> Language Master</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Monitoring</div>';
            echo '<a href="pending_training.php" class="sidebar-item"><i class="fas fa-hourglass-half"></i> Pending Workers</a>';
            echo '<a href="retraining.php" class="sidebar-item"><i class="fas fa-redo"></i> Re-Training Eligibility</a>';
            echo '<a href="retraining_requests.php" class="sidebar-item"><i class="fas fa-tasks"></i> Re-Training Inbox</a>';
            echo '<a href="training_batch_report.php" class="sidebar-item"><i class="fas fa-file-lines"></i> Batch Reports</a>';
            echo '<a href="reports.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Training Details</a>';
            break;
        case 'execution_officer':
        case 'execution':
            $eb = BASE_URL . 'pages/execution/';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Supervision Command</div>';
            echo '<a href="'.$eb.'dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Command Center</a>';
            echo '<a href="'.$eb.'training_attendance.php" class="sidebar-item"><i class="fas fa-file-signature"></i> Workmen Enrollment Approval</a>';
            echo '<a href="'.$eb.'contractors.php" class="sidebar-item"><i class="fas fa-building"></i> Assigned Contractors</a>';
            echo '<a href="'.$eb.'work_orders.php" class="sidebar-item"><i class="fas fa-handshake"></i> Work Order Tracking</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Monitoring Desk</div>';
            echo '<a href="'.$eb.'deployments.php" class="sidebar-item"><i class="fas fa-users-viewfinder"></i> Deployment Monitoring</a>';
            echo '<a href="'.$eb.'attendance.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance Monitoring</a>';
            echo '<a href="'.$eb.'attendance_exceptions.php" class="sidebar-item"><i class="fas fa-triangle-exclamation"></i> System Exceptions</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Field Actions</div>';
            echo '<a href="'.$eb.'observations.php" class="sidebar-item"><i class="fas fa-edit"></i> Field Observations</a>';
            echo '<a href="'.$eb.'escalations.php" class="sidebar-item"><i class="fas fa-bullhorn"></i> Escalation Management</a>';
            echo '<a href="'.$eb.'productivity.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Productivity Center</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Operational Intelligence</div>';
            echo '<a href="'.$eb.'reports.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Reports & Exports</a>';
            break;
    }
    
    // ----- DYNAMIC SPECIAL ACCESS LINKS -----
    if ($role !== 'super_admin' && $role !== 'admin' && function_exists('get_user_accessible_modules')) {
        $accessible = get_user_accessible_modules();
        if (!empty($accessible) && is_array($accessible) && !in_array('*', $accessible)) {
                                                            $dynamicModules = [
                'dashboard' => ['link' => BASE_URL . 'pages/admin/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'SuperAdmin Dashboard'],
                'users' => ['link' => BASE_URL . 'pages/admin/users.php', 'icon' => 'fa-users', 'label' => 'User Management'],
                'contractors' => ['link' => BASE_URL . 'pages/admin/contractor_control.php', 'icon' => 'fa-building', 'label' => 'Contractor Management'],
                'workmen' => ['link' => BASE_URL . 'pages/admin/worker_management.php', 'icon' => 'fa-hard-hat', 'label' => 'Workmen Management'],
                'documents' => ['link' => BASE_URL . 'pages/admin/documents_monitor.php', 'icon' => 'fa-folder-open', 'label' => 'Document Monitor'],
                'training' => ['link' => BASE_URL . 'pages/admin/training_monitor.php', 'icon' => 'fa-graduation-cap', 'label' => 'Training Monitor'],
                'gate_pass' => ['link' => BASE_URL . 'pages/admin/gatepass_monitor.php', 'icon' => 'fa-id-card', 'label' => 'Gate Pass Monitor'],
                'safety' => ['link' => BASE_URL . 'pages/safety/dashboard.php', 'icon' => 'fa-shield-check', 'label' => 'Safety Desk'],
                'compliance' => ['link' => BASE_URL . 'pages/admin/compliance_dashboard.php', 'icon' => 'fa-balance-scale', 'label' => 'Compliance Dashboard'],
                'attendance' => ['link' => BASE_URL . 'pages/admin/attendance_dashboard.php', 'icon' => 'fa-calendar-check', 'label' => 'Attendance Dashboard'],
                'reports' => ['link' => BASE_URL . 'pages/admin/reports.php', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
                'noc' => ['link' => BASE_URL . 'pages/welfare/noc_approvals.php', 'icon' => 'fa-exchange-alt', 'label' => 'NOC Approvals'],
                'sap' => ['link' => BASE_URL . 'pages/admin/sap_sync_logs.php', 'icon' => 'fa-sync', 'label' => 'SAP Logs'],
                'settings' => ['link' => BASE_URL . 'pages/admin/settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
                'blocking' => ['link' => BASE_URL . 'pages/welfare/blocking_control.php', 'icon' => 'fa-ban', 'label' => 'Blocking Control'],
                'audit_logs' => ['link' => BASE_URL . 'pages/admin/audit_logs.php', 'icon' => 'fa-history', 'label' => 'Audit Logs'],
                'adm___dash_link_' => ['link' => BASE_URL . 'pages/admin/.$dash_link.', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'adm_dashboard' => ['link' => BASE_URL . 'pages/admin/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'adm_users' => ['link' => BASE_URL . 'pages/admin/users.php', 'icon' => 'fa-users-cog', 'label' => 'User Management'],
                'adm_roles' => ['link' => BASE_URL . 'pages/admin/roles.php', 'icon' => 'fa-user-shield', 'label' => 'Role Control'],
                'adm_permissions' => ['link' => BASE_URL . 'pages/admin/permissions.php', 'icon' => 'fa-key', 'label' => 'Permissions'],
                'adm_workflow_monitor' => ['link' => BASE_URL . 'pages/admin/workflow_monitor.php', 'icon' => 'fa-eye', 'label' => 'Workflow Monitoring'],
                'adm_workflow_control' => ['link' => BASE_URL . 'pages/admin/workflow_control.php', 'icon' => 'fa-gamepad', 'label' => 'Workflow Control'],
                'adm_documents_monitor' => ['link' => BASE_URL . 'pages/admin/documents_monitor.php', 'icon' => 'fa-file-invoice', 'label' => 'Document Flow'],
                'adm_training_monitor' => ['link' => BASE_URL . 'pages/admin/training_monitor.php', 'icon' => 'fa-graduation-cap', 'label' => 'Training Flow'],
                'adm_gatepass_monitor' => ['link' => BASE_URL . 'pages/admin/gatepass_monitor.php', 'icon' => 'fa-id-card', 'label' => 'Gate Pass Flow'],
                'adm_sap_sync_logs' => ['link' => BASE_URL . 'pages/admin/sap_sync_logs.php', 'icon' => 'fa-sync', 'label' => 'SAP Sync Logs'],
                'wor_worker_management' => ['link' => BASE_URL . 'pages/admin/worker_management.php', 'icon' => 'fa-user-clock', 'label' => 'Worker Lifecycle'],
                'con_contractor_control' => ['link' => BASE_URL . 'pages/admin/contractor_control.php', 'icon' => 'fa-building-circle-exclamation', 'label' => 'Contractor Control'],
                'exe_execution_management' => ['link' => BASE_URL . 'pages/admin/execution_management.php', 'icon' => 'fa-link', 'label' => 'Execution Mapping'],
                'adm_compliance_dashboard' => ['link' => BASE_URL . 'pages/admin/compliance_dashboard.php', 'icon' => 'fa-shield-check', 'label' => 'Compliance Dashboard'],
                'adm_attendance_dashboard' => ['link' => BASE_URL . 'pages/admin/attendance_dashboard.php', 'icon' => 'fa-calendar-check', 'label' => 'Attendance Dashboard'],
                'adm_biometric_dashboard' => ['link' => BASE_URL . 'pages/admin/biometric_dashboard.php', 'icon' => 'fa-fingerprint', 'label' => 'Biometric Governance'],
                'adm_pass_limits' => ['link' => BASE_URL . 'pages/admin/pass_limits.php', 'icon' => 'fa-sliders-h', 'label' => 'Pass Category Limit'],
                'wel_certified_wages' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/certified_wages.php', 'icon' => 'fa-indian-rupee-sign', 'label' => 'Certified Wage Rate'],
                'wel_labour_license_threshold' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/labour_license_threshold.php', 'icon' => 'fa-scale-balanced', 'label' => 'Labour License Threshold'],
                'wel_temporary_pass_validity' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/temporary_pass_validity.php', 'icon' => 'fa-calendar-day', 'label' => 'Temporary Pass Validity'],
                'wel_age_range_mapping' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/age_range_mapping.php', 'icon' => 'fa-user-clock', 'label' => 'Age Range Mapping'],
                'wel_gate_pass_document_master' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/gate_pass_document_master.php', 'icon' => 'fa-file-shield', 'label' => 'Gate Pass Document Master'],
                'wel_payment_gateway' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/payment_gateway.php', 'icon' => 'fa-credit-card', 'label' => 'Payment Gateway / QR'],
                'adm_master_data' => ['link' => BASE_URL . 'pages/admin/master_data.php', 'icon' => 'fa-database', 'label' => 'Master Data'],
                'wel_muster_roll_monitor' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/muster_roll_monitor.php', 'icon' => 'fa-file-invoice', 'label' => 'Muster Roll Verification'],
                'wel_check_esi_compliance' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/check_esi_compliance.php', 'icon' => 'fa-shield-check', 'label' => 'Check ESI Compliance'],
                'wel_check_epf_compliance' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/welfare/check_epf_compliance.php', 'icon' => 'fa-shield-check', 'label' => 'Check EPF Compliance'],
                'adm_invoices' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/payments/invoices.php', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Payment Governance'],
                'adm_request' => ['link' => BASE_URL . 'pages/admin/.BASE_URL.pages/temporary/request.php', 'icon' => 'fa-user-clock', 'label' => 'Temp Workforce Pass'],
                'adm_policy_monitor' => ['link' => BASE_URL . 'pages/admin/policy_monitor.php', 'icon' => 'fa-microchip', 'label' => 'Policy Engine'],
                'adm_notifications_logs' => ['link' => BASE_URL . 'pages/admin/notifications_logs.php', 'icon' => 'fa-bell-slash', 'label' => 'Notifications Logs'],
                'adm_alerts_dashboard' => ['link' => BASE_URL . 'pages/admin/alerts_dashboard.php', 'icon' => 'fa-exclamation-triangle', 'label' => 'Alerts Dashboard'],
                'adm_system_health' => ['link' => BASE_URL . 'pages/admin/system_health.php', 'icon' => 'fa-heartbeat', 'label' => 'System Health'],
                'adm_audit_logs' => ['link' => BASE_URL . 'pages/admin/audit_logs.php', 'icon' => 'fa-history', 'label' => 'Audit Logs'],
                'adm_reports' => ['link' => BASE_URL . 'pages/admin/reports.php', 'icon' => 'fa-file-medical-alt', 'label' => 'Reports'],
                'adm_data_export' => ['link' => BASE_URL . 'pages/admin/data_export.php', 'icon' => 'fa-download', 'label' => 'Data Export'],
                'adm_settings' => ['link' => BASE_URL . 'pages/admin/settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
                'wel_admin_dashboard' => ['link' => BASE_URL . 'pages/welfare/admin_dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Admin Home'],
                'adm_create_user' => ['link' => BASE_URL . 'pages/admin/create_user.php', 'icon' => 'fa-user-plus', 'label' => 'Create User'],
                'wel_pass_limits' => ['link' => BASE_URL . 'pages/welfare/pass_limits.php', 'icon' => 'fa-sliders-h', 'label' => 'Pass Category Limit'],
                'wel_education_correction' => ['link' => BASE_URL . 'pages/welfare/education_correction.php', 'icon' => 'fa-graduation-cap', 'label' => 'Education Job Profile'],
                'wel_nationality_master' => ['link' => BASE_URL . 'pages/welfare/nationality_master.php', 'icon' => 'fa-globe', 'label' => 'Nationality Masters'],
                'wel_training_type_master' => ['link' => BASE_URL . 'pages/welfare/training_type_master.php', 'icon' => 'fa-graduation-cap', 'label' => 'Training Type Master'],
                'wel_training_venue_master' => ['link' => BASE_URL . 'pages/welfare/training_venue_master.php', 'icon' => 'fa-location-dot', 'label' => 'Training Venue Master'],
                'wel_temp_pass_control' => ['link' => BASE_URL . 'pages/welfare/temp_pass_control.php', 'icon' => 'fa-clock', 'label' => 'Temp Pass Control'],
                'wel_entity_directory' => ['link' => BASE_URL . 'pages/welfare/entity_directory.php', 'icon' => 'fa-address-book', 'label' => 'Contractor / Customer Data'],
                'wel_approve_3a' => ['link' => BASE_URL . 'pages/welfare/approve_3a.php', 'icon' => 'fa-file-contract', 'label' => 'Contractor Info Verification'],
                'wel_enrollment_monitor' => ['link' => BASE_URL . 'pages/welfare/enrollment_monitor.php', 'icon' => 'fa-users-viewfinder', 'label' => 'Worker Monitor'],
                'wel_training_monitor' => ['link' => BASE_URL . 'pages/welfare/training_monitor.php', 'icon' => 'fa-graduation-cap', 'label' => 'Training Monitor'],
                'wel_gatepass_monitor' => ['link' => BASE_URL . 'pages/welfare/gatepass_monitor.php', 'icon' => 'fa-id-card-clip', 'label' => 'Gate Pass Monitor'],
                'wel_acc_tracking' => ['link' => BASE_URL . 'pages/welfare/acc_tracking.php', 'icon' => 'fa-fingerprint', 'label' => 'ACC Monitor'],
                'wel_productivity_dashboard' => ['link' => BASE_URL . 'pages/welfare/productivity_dashboard.php', 'icon' => 'fa-chart-line', 'label' => 'Productivity Dashboard'],
                'wel_esi_contribution_monitor' => ['link' => BASE_URL . 'pages/welfare/esi_contribution_monitor.php', 'icon' => 'fa-hand-holding-dollar', 'label' => 'ESI Contribution Monitor'],
                'wel_lwf_compliance_checking' => ['link' => BASE_URL . 'pages/welfare/lwf_compliance_checking.php', 'icon' => 'fa-clipboard-check', 'label' => 'LWF Compliance Checking'],
                'wel_lwf_master' => ['link' => BASE_URL . 'pages/welfare/lwf_master.php', 'icon' => 'fa-sliders-h', 'label' => 'LWF Rate Master'],
                'wel_compliance_monitor' => ['link' => BASE_URL . 'pages/welfare/compliance_monitor.php', 'icon' => 'fa-shield-check', 'label' => 'Compliance Monitor'],
                'wel_blocking_control' => ['link' => BASE_URL . 'pages/welfare/blocking_control.php', 'icon' => 'fa-building-circle-exclamation', 'label' => 'Contractor Control'],
                'wor_worker_block' => ['link' => BASE_URL . 'pages/welfare/worker_block.php', 'icon' => 'fa-user-slash', 'label' => 'Worker Blocking'],
                'wel_noc_approvals' => ['link' => BASE_URL . 'pages/welfare/noc_approvals.php', 'icon' => 'fa-exchange-alt', 'label' => 'NOC Approvals'],
                'wel_verification_history' => ['link' => BASE_URL . 'pages/welfare/verification_history.php', 'icon' => 'fa-history', 'label' => 'Contractor History'],
                'wel_reports' => ['link' => BASE_URL . 'pages/welfare/reports.php', 'icon' => 'fa-file-invoice', 'label' => 'Reports'],
                'wel_sap_logs' => ['link' => BASE_URL . 'pages/welfare/sap_logs.php', 'icon' => 'fa-sync', 'label' => 'SAP Integration'],
                'con_approve_contractors' => ['link' => BASE_URL . 'pages/welfare/approve_contractors.php', 'icon' => 'fa-building-circle-check', 'label' => 'Pending Customer Approval'],
                'wel_verify_documents' => ['link' => BASE_URL . 'pages/welfare/verify_documents.php', 'icon' => 'fa-file-shield', 'label' => 'Document Verification'],
                'wel_issue_temp_pass' => ['link' => BASE_URL . 'pages/welfare/issue_temp_pass.php', 'icon' => 'fa-clock', 'label' => 'Temporary Pass Issue'],
                'wel_acc_generation' => ['link' => BASE_URL . 'pages/welfare/acc_generation.php', 'icon' => 'fa-fingerprint', 'label' => 'Permanent ACC Approval'],
                'wel_acc_return_queue' => ['link' => BASE_URL . 'pages/welfare/acc_return_queue.php', 'icon' => 'fa-undo', 'label' => 'Relieving Management'],
                'wel_monthly_compliance_approvals' => ['link' => BASE_URL . 'pages/welfare/monthly_compliance_approvals.php', 'icon' => 'fa-certificate', 'label' => 'Monthly Compliance Approvals'],
                'wel_esic_wage_limit' => ['link' => BASE_URL . 'pages/welfare/esic_wage_limit.php', 'icon' => 'fa-cog', 'label' => 'ESIC Wage Limit'],
                'wel_attendance_monitor' => ['link' => BASE_URL . 'pages/welfare/attendance_monitor.php', 'icon' => 'fa-calendar-check', 'label' => 'Attendance Monitor'],
                'con_annexure_2a' => ['link' => BASE_URL . 'pages/contractor/annexure-2a.php', 'icon' => 'fa-file-invoice', 'label' => 'CLMS Enrolment Request'],
                'con_enrolment_4a_type_contractor' => ['link' => BASE_URL . 'pages/contractor/enrolment-4a.php?type=contractor', 'icon' => 'fa-user-check', 'label' => 'Contractor'],
                'con_enrolment_4a_type_representative' => ['link' => BASE_URL . 'pages/contractor/enrolment-4a.php?type=representative', 'icon' => 'fa-user-tie', 'label' => 'Representative'],
                'con_enrolment_4a_type_supervisor' => ['link' => BASE_URL . 'pages/contractor/enrolment-4a.php?type=supervisor', 'icon' => 'fa-user-shield', 'label' => 'Supervisor'],
                'con_enrolment_4a_type_workmen' => ['link' => BASE_URL . 'pages/contractor/enrolment-4a.php?type=workmen', 'icon' => 'fa-users', 'label' => 'Workmen'],
                'con_welfare_actions' => ['link' => BASE_URL . 'pages/contractor/welfare-actions.php', 'icon' => 'fa-clock-rotate-left', 'label' => 'Welfare Action History'],
                'con_dashboard' => ['link' => BASE_URL . 'pages/contractor/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'con_profile' => ['link' => BASE_URL . 'pages/contractor/profile.php', 'icon' => 'fa-id-card', 'label' => 'Basic Details'],
                'con_training_request' => ['link' => BASE_URL . 'pages/contractor/training_request.php', 'icon' => 'fa-graduation-cap', 'label' => 'Safety Training Request'],
                'con_payment' => ['link' => BASE_URL . 'pages/contractor/payment.php', 'icon' => 'fa-credit-card', 'label' => 'Pending Fee Payment'],
                'saf_book_safety_training' => ['link' => BASE_URL . 'pages/contractor/book_safety_training.php', 'icon' => 'fa-calendar-check', 'label' => 'Book Safety Training'],
                'con_gatepass_6a' => ['link' => BASE_URL . 'pages/contractor/gatepass-6a.php', 'icon' => 'fa-id-badge', 'label' => 'Gate Pass'],
                'con_gatepass_reupload' => ['link' => BASE_URL . 'pages/contractor/gatepass-reupload.php', 'icon' => 'fa-file-circle-exclamation', 'label' => 'Re-upload Gate Pass Docs'],
                'con_pass_status' => ['link' => BASE_URL . 'pages/contractor/pass_status.php', 'icon' => 'fa-id-card', 'label' => 'ACC Card'],
                'wor_block_unblock' => ['link' => BASE_URL . 'pages/worker/block_unblock.php', 'icon' => 'fa-user-slash', 'label' => 'Worker Blocking'],
                'con_muster_roll' => ['link' => BASE_URL . 'pages/contractor/muster_roll.php', 'icon' => 'fa-file-invoice', 'label' => 'Muster Roll'],
                'con_esi_contribution' => ['link' => BASE_URL . 'pages/contractor/esi_contribution.php', 'icon' => 'fa-hand-holding-dollar', 'label' => 'ESI Contribution'],
                'con_esi_ecr_pf_upload' => ['link' => BASE_URL . 'pages/contractor/esi_ecr_pf_upload.php', 'icon' => 'fa-file-upload', 'label' => 'ESI ECR & PF Contribution Upload'],
                'con_esi_compliance_check' => ['link' => BASE_URL . 'pages/contractor/esi_compliance_check.php', 'icon' => 'fa-shield-check', 'label' => 'ESI Compliance Status'],
                'con_epf_compliance' => ['link' => BASE_URL . 'pages/contractor/epf_compliance.php', 'icon' => 'fa-file-invoice-dollar', 'label' => 'EPF Compliance'],
                'con_lwf_compliance' => ['link' => BASE_URL . 'pages/contractor/lwf_compliance.php', 'icon' => 'fa-balance-scale', 'label' => 'LWF Compliance'],
                'con_monthly_compliance_certificate' => ['link' => BASE_URL . 'pages/contractor/monthly_compliance_certificate.php', 'icon' => 'fa-certificate', 'label' => 'Monthly Compliance Cert'],
                'con_lwf_rate_view' => ['link' => BASE_URL . 'pages/contractor/lwf_rate_view.php', 'icon' => 'fa-indian-rupee-sign', 'label' => 'LWF Rate & Status'],
                'con_attendance' => ['link' => BASE_URL . 'pages/contractor/attendance.php', 'icon' => 'fa-calendar-check', 'label' => 'Attendance'],
                'con_noc_management' => ['link' => BASE_URL . 'pages/contractor/noc_management.php', 'icon' => 'fa-exchange-alt', 'label' => 'NOC Management'],
                'con_compliance' => ['link' => BASE_URL . 'pages/contractor/compliance.php', 'icon' => 'fa-shield-check', 'label' => 'Compliance Monitor'],
                'con_documents' => ['link' => BASE_URL . 'pages/contractor/documents.php', 'icon' => 'fa-folder-open', 'label' => 'Documents'],
                'con_reports' => ['link' => BASE_URL . 'pages/contractor/reports.php', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
                'fro_dashboard' => ['link' => BASE_URL . 'pages/frontline/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'fro_entry_validation' => ['link' => BASE_URL . 'pages/frontline/entry_validation.php', 'icon' => 'fa-sign-in-alt', 'label' => 'Gate Entry Validation'],
                'fro_exit_validation' => ['link' => BASE_URL . 'pages/frontline/exit_validation.php', 'icon' => 'fa-sign-out-alt', 'label' => 'Gate Exit Validation'],
                'fro_active_pass' => ['link' => BASE_URL . 'pages/frontline/active_pass.php', 'icon' => 'fa-id-badge', 'label' => 'Active Pass List'],
                'wor_blocked_workers' => ['link' => BASE_URL . 'pages/frontline/blocked_workers.php', 'icon' => 'fa-user-slash', 'label' => 'Blocked Workers'],
                'fro_expired_pass' => ['link' => BASE_URL . 'pages/frontline/expired_pass.php', 'icon' => 'fa-exclamation-triangle', 'label' => 'Expired Pass Alerts'],
                'fro_logs' => ['link' => BASE_URL . 'pages/frontline/logs.php', 'icon' => 'fa-history', 'label' => 'Entry/Exit Logs'],
                'fro_manual_override' => ['link' => BASE_URL . 'pages/frontline/manual_override.php', 'icon' => 'fa-unlock-alt', 'label' => 'Manual Override'],
                'fro_reports' => ['link' => BASE_URL . 'pages/frontline/reports.php', 'icon' => 'fa-chart-line', 'label' => 'Reports'],
                'adm_pass_issuer_dashboard' => ['link' => BASE_URL . 'pages/admin/pass_issuer_dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'adm_verify_documents' => ['link' => BASE_URL . 'pages/admin/verify_documents.php', 'icon' => 'fa-file-shield', 'label' => 'Verify Documents'],
                'adm_pending_requests' => ['link' => BASE_URL . 'pages/admin/pending_requests.php', 'icon' => 'fa-list-ul', 'label' => 'Pending Pass Requests'],
                'adm_issue_temp_pass' => ['link' => BASE_URL . 'pages/admin/issue_temp_pass.php', 'icon' => 'fa-id-badge', 'label' => 'Temporary Pass Issue'],
                'adm_acc_generation' => ['link' => BASE_URL . 'pages/admin/acc_generation.php', 'icon' => 'fa-microchip', 'label' => 'ACC Number Generation'],
                'adm_issue_acc_pass' => ['link' => BASE_URL . 'pages/admin/issue_acc_pass.php', 'icon' => 'fa-id-card-clip', 'label' => 'Permanent Pass (ACC)'],
                'adm_pass_status' => ['link' => BASE_URL . 'pages/admin/pass_status.php', 'icon' => 'fa-satellite-dish', 'label' => 'Pass Status Tracking'],
                'adm_reupload_cases' => ['link' => BASE_URL . 'pages/admin/reupload_cases.php', 'icon' => 'fa-upload', 'label' => 'Rejected / Re-upload'],
                'adm_pass_validity' => ['link' => BASE_URL . 'pages/admin/pass_validity.php', 'icon' => 'fa-calendar-check', 'label' => 'Pass Validity Management'],
                'cus_annexure_3a' => ['link' => BASE_URL . 'pages/customer/annexure-3a.php', 'icon' => 'fa-file-contract', 'label' => 'Contractor Info'],
                'cus_welfare_actions' => ['link' => BASE_URL . 'pages/customer/welfare-actions.php', 'icon' => 'fa-clock-rotate-left', 'label' => 'Welfare Action History'],
                'cus_dashboard' => ['link' => BASE_URL . 'pages/customer/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'cus_profile' => ['link' => BASE_URL . 'pages/customer/profile.php', 'icon' => 'fa-id-card', 'label' => 'Basic Details'],
                'con_enrolment_4a_type_retraining' => ['link' => BASE_URL . 'pages/contractor/enrolment-4a.php?type=retraining', 'icon' => 'fa-arrows-rotate', 'label' => 'Re-Training Workmen'],
                'saf_dashboard' => ['link' => BASE_URL . 'pages/safety/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                'saf_enrollment_approval' => ['link' => BASE_URL . 'pages/safety/enrollment_approval.php', 'icon' => 'fa-user-check', 'label' => 'Enrollment Approval Inbox'],
                'saf_training_class_master' => ['link' => BASE_URL . 'pages/safety/training_class_master.php', 'icon' => 'fa-calendar-plus', 'label' => 'Training Batch Creation'],
                'saf_training_requests' => ['link' => BASE_URL . 'pages/safety/training_requests.php', 'icon' => 'fa-envelope-open-text', 'label' => 'Training Requests'],
                'saf_training_schedule' => ['link' => BASE_URL . 'pages/safety/training_schedule.php', 'icon' => 'fa-calendar-alt', 'label' => 'Training Schedule'],
                'saf_upcoming_sessions' => ['link' => BASE_URL . 'pages/safety/upcoming_sessions.php', 'icon' => 'fa-clock', 'label' => 'Upcoming Sessions'],
                'saf_conduct_results' => ['link' => BASE_URL . 'pages/safety/conduct_results.php', 'icon' => 'fa-users-cog', 'label' => 'Conduct & Results'],
                'saf_training_status' => ['link' => BASE_URL . 'pages/safety/training_status.php', 'icon' => 'fa-user-check', 'label' => 'Training Status'],
                'saf_training_location_master' => ['link' => BASE_URL . 'pages/safety/training_location_master.php', 'icon' => 'fa-location-dot', 'label' => 'Location Master'],
                'saf_instructor_master' => ['link' => BASE_URL . 'pages/safety/instructor_master.php', 'icon' => 'fa-person-chalkboard', 'label' => 'Instructor Master'],
                'saf_safety_training_type_master' => ['link' => BASE_URL . 'pages/safety/safety_training_type_master.php', 'icon' => 'fa-list-check', 'label' => 'Training Type Master'],
                'saf_training_fee_master' => ['link' => BASE_URL . 'pages/safety/training_fee_master.php', 'icon' => 'fa-indian-rupee-sign', 'label' => 'Training Fee Master'],
                'saf_training_language_master' => ['link' => BASE_URL . 'pages/safety/training_language_master.php', 'icon' => 'fa-language', 'label' => 'Language Master'],
                'saf_pending_training' => ['link' => BASE_URL . 'pages/safety/pending_training.php', 'icon' => 'fa-hourglass-half', 'label' => 'Pending Workers'],
                'saf_retraining' => ['link' => BASE_URL . 'pages/safety/retraining.php', 'icon' => 'fa-redo', 'label' => 'Re-Training Eligibility'],
                'saf_retraining_requests' => ['link' => BASE_URL . 'pages/safety/retraining_requests.php', 'icon' => 'fa-tasks', 'label' => 'Re-Training Inbox'],
                'saf_training_batch_report' => ['link' => BASE_URL . 'pages/safety/training_batch_report.php', 'icon' => 'fa-file-lines', 'label' => 'Batch Reports'],
                'saf_reports' => ['link' => BASE_URL . 'pages/safety/reports.php', 'icon' => 'fa-chart-bar', 'label' => 'Training Details'],
                'exe_dashboard' => ['link' => BASE_URL . 'pages/execution/dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Command Center'],
                'exe_training_attendance' => ['link' => BASE_URL . 'pages/execution/training_attendance.php', 'icon' => 'fa-file-signature', 'label' => 'Workmen Enrollment Approval'],
                'exe_contractors' => ['link' => BASE_URL . 'pages/execution/contractors.php', 'icon' => 'fa-building', 'label' => 'Assigned Contractors'],
                'exe_work_orders' => ['link' => BASE_URL . 'pages/execution/work_orders.php', 'icon' => 'fa-handshake', 'label' => 'Work Order Tracking'],
                'exe_deployments' => ['link' => BASE_URL . 'pages/execution/deployments.php', 'icon' => 'fa-users-viewfinder', 'label' => 'Deployment Monitoring'],
                'exe_attendance' => ['link' => BASE_URL . 'pages/execution/attendance.php', 'icon' => 'fa-calendar-check', 'label' => 'Attendance Monitoring'],
                'exe_attendance_exceptions' => ['link' => BASE_URL . 'pages/execution/attendance_exceptions.php', 'icon' => 'fa-triangle-exclamation', 'label' => 'System Exceptions'],
                'exe_observations' => ['link' => BASE_URL . 'pages/execution/observations.php', 'icon' => 'fa-edit', 'label' => 'Field Observations'],
                'exe_escalations' => ['link' => BASE_URL . 'pages/execution/escalations.php', 'icon' => 'fa-bullhorn', 'label' => 'Escalation Management'],
                'exe_productivity' => ['link' => BASE_URL . 'pages/execution/productivity.php', 'icon' => 'fa-chart-line', 'label' => 'Productivity Center'],
                'exe_reports' => ['link' => BASE_URL . 'pages/execution/reports.php', 'icon' => 'fa-file-invoice', 'label' => 'Reports & Exports'],
            ];
            $specialLinks = [];
            foreach ($accessible as $mod) {
                if (isset($dynamicModules[$mod])) {
                    $m = $dynamicModules[$mod];
                    // Prevent rendering if the user already has this link (rough check)
                    $specialLinks[] = '<a href="'.$m['link'].'" class="sidebar-item" style="background:rgba(99,102,241,0.05); border-left:3px solid #6366f1;"><i class="fas '.$m['icon'].' text-primary"></i> '.$m['label'].'</a>';
                }
            }
            if (!empty($specialLinks)) {
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label" style="color:#6366f1;"><i class="fas fa-star"></i> Granted Access</div>';
                echo implode('', array_unique($specialLinks));
            }
        }
    }
    // ----------------------------------------
    
    echo '</div><div class="sidebar-section" style="margin-top:auto;">';
    echo '<a href="' . BASE_URL . 'api/logout.php" class="sidebar-item text-danger"><i class="fas fa-power-off"></i> Logout</a>';
    echo '</div>';
}
?>
