<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
  <script>window.CLMS_BASE_URL = "<?= BASE_URL ?>";</script>
  <title><?= $page_title ?> – CLMS</title>
  <link rel="stylesheet" href="../../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
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
      <div class="alert alert-success" id="auto-hide-alert">
        <i class="fas fa-check-circle"></i>
        <div><?= htmlspecialchars($success_msg) ?></div>
        <button type="button" style="margin-left:auto; background:none; border:none; cursor:pointer;" onclick="this.parentElement.remove()">&times;</button>
      </div>
    <?php endif; ?>

    <?php if($error_msg): ?>
      <div class="alert alert-danger" id="auto-hide-alert">
        <i class="fas fa-exclamation-circle"></i>
        <div><?= htmlspecialchars($error_msg) ?></div>
        <button type="button" style="margin-left:auto; background:none; border:none; cursor:pointer;" onclick="this.parentElement.remove()">&times;</button>
      </div>
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

  // Global DataTables Initialization with Auto S.No
  $(document).ready(function() {
      $('.data-table').each(function() {
          var table = $(this);
          var t = table.DataTable({
              "pageLength": 10,
              "ordering": false,
              "language": {
                  "search": "<i class='fas fa-search'></i> Filter:",
                  "lengthMenu": "Show _MENU_ entries"
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
            echo '<a href="'.$ab.'manage_work_orders.php" class="sidebar-item"><i class="fas fa-handshake"></i> Work Order Mapping</a>';
            echo '<a href="'.$ab.'pass_limits.php" class="sidebar-item"><i class="fas fa-sliders-h"></i> Pass Limits</a>';
            echo '<a href="'.$ab.'master_data.php" class="sidebar-item"><i class="fas fa-database"></i> Master Data</a>';
            
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
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Pass & Policy Control</div>';
            echo '<a href="'.$wb.'pass_limits.php" class="sidebar-item"><i class="fas fa-sliders-h"></i> Pass Limits</a>';
            echo '<a href="'.$wb.'verify_documents.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Document Master</a>';
            echo '<a href="'.$wb.'temp_pass_control.php" class="sidebar-item"><i class="fas fa-clock"></i> Temp Pass Control</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Monitoring & Oversight</div>';
            echo '<a href="'.$wb.'approve_contractors.php" class="sidebar-item"><i class="fas fa-building"></i> Contractor Verification</a>';
            echo '<a href="'.$wb.'entity_directory.php" class="sidebar-item"><i class="fas fa-address-book"></i> Contractor / Customer Data</a>';
            echo '<a href="'.$wb.'approve_3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info Verification </a>';
            echo '<a href="'.$wb.'enrollment_monitor.php" class="sidebar-item"><i class="fas fa-users-viewfinder"></i> Worker Monitor</a>';
            echo '<a href="'.$wb.'education_correction.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Education Correction</a>';
            echo '<a href="'.$wb.'training_monitor.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Training Monitor</a>';
            echo '<a href="'.$wb.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card-clip"></i> Gate Pass Monitor</a>';
            echo '<a href="'.$wb.'acc_tracking.php" class="sidebar-item"><i class="fas fa-fingerprint"></i> ACC Monitor</a>';
            echo '<a href="'.$ab.'manage_work_orders.php" class="sidebar-item"><i class="fas fa-handshake"></i> Work Order Mapping</a>';
            echo '<a href="'.$wb.'productivity_dashboard.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Productivity Dashboard</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Compliance & Lifecycle</div>';
            echo '<a href="'.$wb.'compliance_monitor.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Monitor</a>';
            echo '<a href="'.$wb.'blocking_control.php" class="sidebar-item"><i class="fas fa-building-circle-exclamation"></i> Contractor Control</a>';
            echo '<a href="'.$wb.'worker_block.php" class="sidebar-item"><i class="fas fa-user-slash"></i> Worker Blocking</a>';
            echo '<a href="'.$wb.'noc_transfer.php" class="sidebar-item"><i class="fas fa-exchange-alt"></i> NOC & Transfer</a>';
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
            echo '<a href="'.$wb.'approve_contractors.php" class="sidebar-item"><i class="fas fa-building-circle-check"></i> Contractor Verification (2A)</a>';
            echo '<a href="'.$wb.'entity_directory.php" class="sidebar-item"><i class="fas fa-address-book"></i> Contractor / Customer Data</a>';
            echo '<a href="'.$wb.'approve_3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info Verification (3A)</a>';
            echo '<a href="'.$wb.'enrollment_monitor.php" class="sidebar-item"><i class="fas fa-users-viewfinder"></i> Enrollment Verification</a>';
            echo '<a href="'.$wb.'verify_documents.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Document Verification</a>';
            echo '<a href="'.$wb.'training_monitor.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Safety Training</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Pass Issuance</div>';
            echo '<a href="'.$wb.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card-clip"></i> Gate Pass Approval</a>';
            echo '<a href="'.$wb.'issue_temp_pass.php" class="sidebar-item"><i class="fas fa-clock"></i> Temporary Pass Issue</a>';
            echo '<a href="'.$wb.'acc_generation.php" class="sidebar-item"><i class="fas fa-fingerprint"></i> Permanent ACC Approval</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Worker Lifecycle</div>';
            echo '<a href="'.$wb.'blocking_control.php" class="sidebar-item"><i class="fas fa-building-circle-exclamation"></i> Contractor Control</a>';
            echo '<a href="'.$wb.'worker_block.php" class="sidebar-item"><i class="fas fa-user-slash"></i> Worker Blocking</a>';
            echo '<a href="'.$wb.'noc_transfer.php" class="sidebar-item"><i class="fas fa-exchange-alt"></i> Company Change / NOC</a>';
            echo '<a href="'.$wb.'acc_return_queue.php" class="sidebar-item"><i class="fas fa-undo"></i> Relieving Management</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Compliance & Monitor</div>';
            echo '<a href="'.$wb.'compliance_monitor.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Verification</a>';
            echo '<a href="'.$wb.'gatepass_monitor.php" class="sidebar-item"><i class="fas fa-id-card"></i> Gate Pass Monitoring</a>';
            echo '<a href="'.$wb.'attendance_monitor.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance Monitor</a>';
            echo '<a href="'.$wb.'verification_history.php" class="sidebar-item"><i class="fas fa-history"></i> Contractor History</a>';
            echo '<a href="'.$wb.'reports.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Reports</a>';
            echo '<a href="'.$wb.'sap_logs.php" class="sidebar-item"><i class="fas fa-sync"></i> SAP Integration</a>';
            break;

        case 'contractor':
            $user_id = $_SESSION['user_id'] ?? 0;
            $contractor = db_single($conn, "SELECT status, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
            $status = strtolower($contractor['status'] ?? 'new');
            $sap_code = $contractor['vendor_code'] ?? '';

            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Contractor Lifecycle</div>';
            echo '<a href="dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="profile.php" class="sidebar-item"><i class="fas fa-id-card"></i> Basic Details</a>';
            
            // Annexure 2A (Always visible, but status changes)
            echo '<a href="annexure-2a.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Contractor Registration</a>';
            
            // Annexure 3A (Only visible/active if 2A is approved)
            if ($status === 'approved') {
                // echo '<a href="annexure-3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info</a>';
                
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Workforce Management</div>';
                echo '<a href="enrolment-4a.php?type=workmen" class="sidebar-item"><i class="fas fa-users"></i> Worker Management</a>';
                echo '<a href="training_request.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Safety Training</a>';
                echo '<a href="gatepass-6a.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Gate Pass</a>';
                echo '<a href="pass_status.php" class="sidebar-item"><i class="fas fa-id-card"></i> ACC Card</a>';
                
                echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Operations & Compliance</div>';
                echo '<a href="attendance.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Attendance</a>';
                echo '<a href="compliance.php" class="sidebar-item"><i class="fas fa-shield-check"></i> Compliance Monitor</a>';
                echo '<a href="documents.php" class="sidebar-item"><i class="fas fa-folder-open"></i> Documents</a>';
                echo '<a href="reports.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Reports</a>';
            } else {
                echo '<div class="sidebar-item text-muted" style="font-size:12px; padding:10px 15px; background:rgba(0,0,0,0.03); margin-top:10px; border-radius:8px;">';
                echo '<i class="fas fa-lock me-2"></i> Approve Annexure 2A to unlock further modules.';
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
            echo '<a href="pending_requests.php" class="sidebar-item"><i class="fas fa-list-ul"></i> Pending Pass Requests</a>';
            echo '<a href="verify_documents.php" class="sidebar-item"><i class="fas fa-file-shield"></i> Verify Documents</a>';
            echo '<a href="issue_temp_pass.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Temporary Pass Issue</a>';
            echo '<a href="issue_acc_pass.php" class="sidebar-item"><i class="fas fa-id-card-clip"></i> Permanent Pass (ACC)</a>';
            echo '<a href="acc_generation.php" class="sidebar-item"><i class="fas fa-microchip"></i> ACC Number Generation</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Management</div>';
            echo '<a href="pass_status.php" class="sidebar-item"><i class="fas fa-satellite-dish"></i> Pass Status Tracking</a>';
            echo '<a href="reupload_cases.php" class="sidebar-item"><i class="fas fa-upload"></i> Rejected / Re-upload</a>';
            echo '<a href="pass_validity.php" class="sidebar-item"><i class="fas fa-calendar-check"></i> Pass Validity Management</a>';
            echo '<a href="reports.php" class="sidebar-item"><i class="fas fa-file-invoice"></i> Reports</a>';
            break;

        case 'customer':
            $cb = BASE_URL . 'pages/customer/';
            $cp = BASE_URL . 'pages/contractor/';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Customer Portal</div>';
            echo '<a href="'.$cb.'dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>';
            echo '<a href="'.$cb.'profile.php" class="sidebar-item"><i class="fas fa-id-card"></i> Basic Details</a>';
            echo '<a href="'.$cb.'annexure-3a.php" class="sidebar-item"><i class="fas fa-file-contract"></i> Contractor Info (3A)</a>';
            
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Workforce Monitoring</div>';
            echo '<a href="'.$cp.'enrolment-4a.php?type=workmen" class="sidebar-item"><i class="fas fa-users"></i> Worker Management</a>';
            echo '<a href="'.$cp.'training_request.php" class="sidebar-item"><i class="fas fa-graduation-cap"></i> Safety Training</a>';
            echo '<a href="'.$cp.'gatepass-6a.php" class="sidebar-item"><i class="fas fa-id-badge"></i> Gate Pass</a>';
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
            echo '<a href="training_requests.php" class="sidebar-item"><i class="fas fa-envelope-open-text"></i> Training Requests</a>';
            echo '<a href="training_schedule.php" class="sidebar-item"><i class="fas fa-calendar-alt"></i> Training Schedule</a>';
            echo '<a href="manage_session.php" class="sidebar-item"><i class="fas fa-users-cog"></i> Conduct & Results</a>';
            echo '<a href="training_status.php" class="sidebar-item"><i class="fas fa-user-check"></i> Training Status</a>';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Monitoring</div>';
            echo '<a href="pending_training.php" class="sidebar-item"><i class="fas fa-hourglass-half"></i> Pending Workers</a>';
            echo '<a href="retraining.php" class="sidebar-item"><i class="fas fa-redo"></i> Re-Training Requests</a>';
            echo '<a href="reports.php" class="sidebar-item"><i class="fas fa-chart-bar"></i> Training Reports</a>';
            break;
        case 'execution_officer':
        case 'execution':
            $eb = BASE_URL . 'pages/execution/';
            echo '</div><div class="sidebar-section"><div class="sidebar-section-label">Supervision Command</div>';
            echo '<a href="'.$eb.'dashboard.php" class="sidebar-item"><i class="fas fa-tachometer-alt"></i> Command Center</a>';
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
    
    echo '</div><div class="sidebar-section">';
    echo '<a href="../../api/logout.php" class="sidebar-item text-danger"><i class="fas fa-power-off"></i> Logout</a>';
    echo '</div>';
}
?>
