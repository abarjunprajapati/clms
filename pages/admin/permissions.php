<?php
/**
 * PERMISSIONS MANAGEMENT PAGE
 * Two modes:
 * 1. ROLE PERMISSIONS - What each role can do (module-level)
 * 2. USER PERMISSIONS - Assign individual users extra capabilities beyond their role
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Super Admin';

// Ensure tables exist before renderContent is called
$conn->query("CREATE TABLE IF NOT EXISTS user_extra_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module VARCHAR(100) NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_create TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    can_approve TINYINT(1) DEFAULT 0,
    can_block TINYINT(1) DEFAULT 0,
    can_export TINYINT(1) DEFAULT 0,
    can_override TINYINT(1) DEFAULT 0,
    granted_by INT DEFAULT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_module (user_id, module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function renderContent() {
    global $conn;

    $activeTab = $_GET['tab'] ?? 'roles';


    // ── DATA ────────────────────────────────────────────────────
    $roles      = ['welfare_admin','welfare_user','safety_user','front_line_user','pass_user','execution_officer'];
                    $modules    = [
        'dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#6366f1'],
        'users' => ['icon'=>'fa-users', 'color'=>'#8b5cf6'],
        'contractors' => ['icon'=>'fa-building', 'color'=>'#3b82f6'],
        'workmen' => ['icon'=>'fa-hard-hat', 'color'=>'#0ea5e9'],
        'documents' => ['icon'=>'fa-folder-open', 'color'=>'#f59e0b'],
        'training' => ['icon'=>'fa-graduation-cap', 'color'=>'#10b981'],
        'gate_pass' => ['icon'=>'fa-id-card', 'color'=>'#14b8a6'],
        'safety' => ['icon'=>'fa-shield-check', 'color'=>'#059669'],
        'compliance' => ['icon'=>'fa-balance-scale', 'color'=>'#6366f1'],
        'attendance' => ['icon'=>'fa-calendar-check', 'color'=>'#ec4899'],
        'reports' => ['icon'=>'fa-chart-bar', 'color'=>'#f97316'],
        'noc' => ['icon'=>'fa-exchange-alt', 'color'=>'#a855f7'],
        'sap' => ['icon'=>'fa-sync', 'color'=>'#64748b'],
        'settings' => ['icon'=>'fa-cog', 'color'=>'#94a3b8'],
        'blocking' => ['icon'=>'fa-ban', 'color'=>'#ef4444'],
        'audit_logs' => ['icon'=>'fa-history', 'color'=>'#475569'],
        'adm___dash_link_' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'adm_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'adm_users' => ['icon'=>'fa-users-cog', 'color'=>'#0ea5e9'],
        'adm_roles' => ['icon'=>'fa-user-shield', 'color'=>'#0ea5e9'],
        'adm_permissions' => ['icon'=>'fa-key', 'color'=>'#0ea5e9'],
        'adm_workflow_monitor' => ['icon'=>'fa-eye', 'color'=>'#0ea5e9'],
        'adm_workflow_control' => ['icon'=>'fa-gamepad', 'color'=>'#0ea5e9'],
        'adm_documents_monitor' => ['icon'=>'fa-file-invoice', 'color'=>'#0ea5e9'],
        'adm_training_monitor' => ['icon'=>'fa-graduation-cap', 'color'=>'#0ea5e9'],
        'adm_gatepass_monitor' => ['icon'=>'fa-id-card', 'color'=>'#0ea5e9'],
        'adm_sap_sync_logs' => ['icon'=>'fa-sync', 'color'=>'#0ea5e9'],
        'wor_worker_management' => ['icon'=>'fa-user-clock', 'color'=>'#0ea5e9'],
        'con_contractor_control' => ['icon'=>'fa-building-circle-exclamation', 'color'=>'#0ea5e9'],
        'exe_execution_management' => ['icon'=>'fa-link', 'color'=>'#0ea5e9'],
        'adm_compliance_dashboard' => ['icon'=>'fa-shield-check', 'color'=>'#0ea5e9'],
        'adm_attendance_dashboard' => ['icon'=>'fa-calendar-check', 'color'=>'#0ea5e9'],
        'adm_biometric_dashboard' => ['icon'=>'fa-fingerprint', 'color'=>'#0ea5e9'],
        'adm_pass_limits' => ['icon'=>'fa-sliders-h', 'color'=>'#0ea5e9'],
        'wel_certified_wages' => ['icon'=>'fa-indian-rupee-sign', 'color'=>'#0ea5e9'],
        'wel_labour_license_threshold' => ['icon'=>'fa-scale-balanced', 'color'=>'#0ea5e9'],
        'wel_temporary_pass_validity' => ['icon'=>'fa-calendar-day', 'color'=>'#0ea5e9'],
        'wel_age_range_mapping' => ['icon'=>'fa-user-clock', 'color'=>'#0ea5e9'],
        'wel_gate_pass_document_master' => ['icon'=>'fa-file-shield', 'color'=>'#0ea5e9'],
        'wel_payment_gateway' => ['icon'=>'fa-credit-card', 'color'=>'#0ea5e9'],
        'adm_master_data' => ['icon'=>'fa-database', 'color'=>'#0ea5e9'],
        'wel_muster_roll_monitor' => ['icon'=>'fa-file-invoice', 'color'=>'#0ea5e9'],
        'wel_check_esi_compliance' => ['icon'=>'fa-shield-check', 'color'=>'#0ea5e9'],
        'wel_check_epf_compliance' => ['icon'=>'fa-shield-check', 'color'=>'#0ea5e9'],
        'adm_invoices' => ['icon'=>'fa-file-invoice-dollar', 'color'=>'#0ea5e9'],
        'adm_request' => ['icon'=>'fa-user-clock', 'color'=>'#0ea5e9'],
        'adm_policy_monitor' => ['icon'=>'fa-microchip', 'color'=>'#0ea5e9'],
        'adm_notifications_logs' => ['icon'=>'fa-bell-slash', 'color'=>'#0ea5e9'],
        'adm_alerts_dashboard' => ['icon'=>'fa-exclamation-triangle', 'color'=>'#0ea5e9'],
        'adm_system_health' => ['icon'=>'fa-heartbeat', 'color'=>'#0ea5e9'],
        'adm_audit_logs' => ['icon'=>'fa-history', 'color'=>'#0ea5e9'],
        'adm_reports' => ['icon'=>'fa-file-medical-alt', 'color'=>'#0ea5e9'],
        'adm_data_export' => ['icon'=>'fa-download', 'color'=>'#0ea5e9'],
        'adm_settings' => ['icon'=>'fa-cog', 'color'=>'#0ea5e9'],
        'wel_admin_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'adm_create_user' => ['icon'=>'fa-user-plus', 'color'=>'#0ea5e9'],
        'wel_pass_limits' => ['icon'=>'fa-sliders-h', 'color'=>'#0ea5e9'],
        'wel_education_correction' => ['icon'=>'fa-graduation-cap', 'color'=>'#0ea5e9'],
        'wel_nationality_master' => ['icon'=>'fa-globe', 'color'=>'#0ea5e9'],
        'wel_training_type_master' => ['icon'=>'fa-graduation-cap', 'color'=>'#0ea5e9'],
        'wel_training_venue_master' => ['icon'=>'fa-location-dot', 'color'=>'#0ea5e9'],
        'wel_temp_pass_control' => ['icon'=>'fa-clock', 'color'=>'#0ea5e9'],
        'wel_entity_directory' => ['icon'=>'fa-address-book', 'color'=>'#0ea5e9'],
        'wel_approve_3a' => ['icon'=>'fa-file-contract', 'color'=>'#0ea5e9'],
        'wel_enrollment_monitor' => ['icon'=>'fa-users-viewfinder', 'color'=>'#0ea5e9'],
        'wel_training_monitor' => ['icon'=>'fa-graduation-cap', 'color'=>'#0ea5e9'],
        'wel_gatepass_monitor' => ['icon'=>'fa-id-card-clip', 'color'=>'#0ea5e9'],
        'wel_acc_tracking' => ['icon'=>'fa-fingerprint', 'color'=>'#0ea5e9'],
        'wel_productivity_dashboard' => ['icon'=>'fa-chart-line', 'color'=>'#0ea5e9'],
        'wel_esi_contribution_monitor' => ['icon'=>'fa-hand-holding-dollar', 'color'=>'#0ea5e9'],
        'wel_lwf_compliance_checking' => ['icon'=>'fa-clipboard-check', 'color'=>'#0ea5e9'],
        'wel_lwf_master' => ['icon'=>'fa-sliders-h', 'color'=>'#0ea5e9'],
        'wel_compliance_monitor' => ['icon'=>'fa-shield-check', 'color'=>'#0ea5e9'],
        'wel_blocking_control' => ['icon'=>'fa-building-circle-exclamation', 'color'=>'#0ea5e9'],
        'wor_worker_block' => ['icon'=>'fa-user-slash', 'color'=>'#0ea5e9'],
        'wel_noc_approvals' => ['icon'=>'fa-exchange-alt', 'color'=>'#0ea5e9'],
        'wel_verification_history' => ['icon'=>'fa-history', 'color'=>'#0ea5e9'],
        'wel_reports' => ['icon'=>'fa-file-invoice', 'color'=>'#0ea5e9'],
        'wel_sap_logs' => ['icon'=>'fa-sync', 'color'=>'#0ea5e9'],
        'con_approve_contractors' => ['icon'=>'fa-building-circle-check', 'color'=>'#0ea5e9'],
        'wel_verify_documents' => ['icon'=>'fa-file-shield', 'color'=>'#0ea5e9'],
        'wel_issue_temp_pass' => ['icon'=>'fa-clock', 'color'=>'#0ea5e9'],
        'wel_acc_generation' => ['icon'=>'fa-fingerprint', 'color'=>'#0ea5e9'],
        'wel_acc_return_queue' => ['icon'=>'fa-undo', 'color'=>'#0ea5e9'],
        'wel_monthly_compliance_approvals' => ['icon'=>'fa-certificate', 'color'=>'#0ea5e9'],
        'wel_esic_wage_limit' => ['icon'=>'fa-cog', 'color'=>'#0ea5e9'],
        'wel_attendance_monitor' => ['icon'=>'fa-calendar-check', 'color'=>'#0ea5e9'],
        'con_annexure_2a' => ['icon'=>'fa-file-invoice', 'color'=>'#0ea5e9'],
        'con_enrolment_4a_type_contractor' => ['icon'=>'fa-user-check', 'color'=>'#0ea5e9'],
        'con_enrolment_4a_type_representative' => ['icon'=>'fa-user-tie', 'color'=>'#0ea5e9'],
        'con_enrolment_4a_type_supervisor' => ['icon'=>'fa-user-shield', 'color'=>'#0ea5e9'],
        'con_enrolment_4a_type_workmen' => ['icon'=>'fa-users', 'color'=>'#0ea5e9'],
        'con_welfare_actions' => ['icon'=>'fa-clock-rotate-left', 'color'=>'#0ea5e9'],
        'con_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'con_profile' => ['icon'=>'fa-id-card', 'color'=>'#0ea5e9'],
        'con_training_request' => ['icon'=>'fa-graduation-cap', 'color'=>'#0ea5e9'],
        'con_payment' => ['icon'=>'fa-credit-card', 'color'=>'#0ea5e9'],
        'saf_book_safety_training' => ['icon'=>'fa-calendar-check', 'color'=>'#0ea5e9'],
        'con_gatepass_6a' => ['icon'=>'fa-id-badge', 'color'=>'#0ea5e9'],
        'con_gatepass_reupload' => ['icon'=>'fa-file-circle-exclamation', 'color'=>'#0ea5e9'],
        'con_pass_status' => ['icon'=>'fa-id-card', 'color'=>'#0ea5e9'],
        'wor_block_unblock' => ['icon'=>'fa-user-slash', 'color'=>'#0ea5e9'],
        'con_muster_roll' => ['icon'=>'fa-file-invoice', 'color'=>'#0ea5e9'],
        'con_esi_contribution' => ['icon'=>'fa-hand-holding-dollar', 'color'=>'#0ea5e9'],
        'con_esi_ecr_pf_upload' => ['icon'=>'fa-file-upload', 'color'=>'#0ea5e9'],
        'con_esi_compliance_check' => ['icon'=>'fa-shield-check', 'color'=>'#0ea5e9'],
        'con_epf_compliance' => ['icon'=>'fa-file-invoice-dollar', 'color'=>'#0ea5e9'],
        'con_lwf_compliance' => ['icon'=>'fa-balance-scale', 'color'=>'#0ea5e9'],
        'con_monthly_compliance_certificate' => ['icon'=>'fa-certificate', 'color'=>'#0ea5e9'],
        'con_lwf_rate_view' => ['icon'=>'fa-indian-rupee-sign', 'color'=>'#0ea5e9'],
        'con_attendance' => ['icon'=>'fa-calendar-check', 'color'=>'#0ea5e9'],
        'con_noc_management' => ['icon'=>'fa-exchange-alt', 'color'=>'#0ea5e9'],
        'con_compliance' => ['icon'=>'fa-shield-check', 'color'=>'#0ea5e9'],
        'con_documents' => ['icon'=>'fa-folder-open', 'color'=>'#0ea5e9'],
        'con_reports' => ['icon'=>'fa-chart-bar', 'color'=>'#0ea5e9'],
        'fro_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'fro_entry_validation' => ['icon'=>'fa-sign-in-alt', 'color'=>'#0ea5e9'],
        'fro_exit_validation' => ['icon'=>'fa-sign-out-alt', 'color'=>'#0ea5e9'],
        'fro_active_pass' => ['icon'=>'fa-id-badge', 'color'=>'#0ea5e9'],
        'wor_blocked_workers' => ['icon'=>'fa-user-slash', 'color'=>'#0ea5e9'],
        'fro_expired_pass' => ['icon'=>'fa-exclamation-triangle', 'color'=>'#0ea5e9'],
        'fro_logs' => ['icon'=>'fa-history', 'color'=>'#0ea5e9'],
        'fro_manual_override' => ['icon'=>'fa-unlock-alt', 'color'=>'#0ea5e9'],
        'fro_reports' => ['icon'=>'fa-chart-line', 'color'=>'#0ea5e9'],
        'adm_pass_issuer_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'adm_verify_documents' => ['icon'=>'fa-file-shield', 'color'=>'#0ea5e9'],
        'adm_pending_requests' => ['icon'=>'fa-list-ul', 'color'=>'#0ea5e9'],
        'adm_issue_temp_pass' => ['icon'=>'fa-id-badge', 'color'=>'#0ea5e9'],
        'adm_acc_generation' => ['icon'=>'fa-microchip', 'color'=>'#0ea5e9'],
        'adm_issue_acc_pass' => ['icon'=>'fa-id-card-clip', 'color'=>'#0ea5e9'],
        'adm_pass_status' => ['icon'=>'fa-satellite-dish', 'color'=>'#0ea5e9'],
        'adm_reupload_cases' => ['icon'=>'fa-upload', 'color'=>'#0ea5e9'],
        'adm_pass_validity' => ['icon'=>'fa-calendar-check', 'color'=>'#0ea5e9'],
        'cus_annexure_3a' => ['icon'=>'fa-file-contract', 'color'=>'#0ea5e9'],
        'cus_welfare_actions' => ['icon'=>'fa-clock-rotate-left', 'color'=>'#0ea5e9'],
        'cus_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'cus_profile' => ['icon'=>'fa-id-card', 'color'=>'#0ea5e9'],
        'con_enrolment_4a_type_retraining' => ['icon'=>'fa-arrows-rotate', 'color'=>'#0ea5e9'],
        'saf_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'saf_enrollment_approval' => ['icon'=>'fa-user-check', 'color'=>'#0ea5e9'],
        'saf_training_class_master' => ['icon'=>'fa-calendar-plus', 'color'=>'#0ea5e9'],
        'saf_training_requests' => ['icon'=>'fa-envelope-open-text', 'color'=>'#0ea5e9'],
        'saf_training_schedule' => ['icon'=>'fa-calendar-alt', 'color'=>'#0ea5e9'],
        'saf_upcoming_sessions' => ['icon'=>'fa-clock', 'color'=>'#0ea5e9'],
        'saf_conduct_results' => ['icon'=>'fa-users-cog', 'color'=>'#0ea5e9'],
        'saf_training_status' => ['icon'=>'fa-user-check', 'color'=>'#0ea5e9'],
        'saf_training_location_master' => ['icon'=>'fa-location-dot', 'color'=>'#0ea5e9'],
        'saf_instructor_master' => ['icon'=>'fa-person-chalkboard', 'color'=>'#0ea5e9'],
        'saf_safety_training_type_master' => ['icon'=>'fa-list-check', 'color'=>'#0ea5e9'],
        'saf_training_fee_master' => ['icon'=>'fa-indian-rupee-sign', 'color'=>'#0ea5e9'],
        'saf_training_language_master' => ['icon'=>'fa-language', 'color'=>'#0ea5e9'],
        'saf_pending_training' => ['icon'=>'fa-hourglass-half', 'color'=>'#0ea5e9'],
        'saf_retraining' => ['icon'=>'fa-redo', 'color'=>'#0ea5e9'],
        'saf_retraining_requests' => ['icon'=>'fa-tasks', 'color'=>'#0ea5e9'],
        'saf_training_batch_report' => ['icon'=>'fa-file-lines', 'color'=>'#0ea5e9'],
        'saf_reports' => ['icon'=>'fa-chart-bar', 'color'=>'#0ea5e9'],
        'exe_dashboard' => ['icon'=>'fa-tachometer-alt', 'color'=>'#0ea5e9'],
        'exe_training_attendance' => ['icon'=>'fa-file-signature', 'color'=>'#0ea5e9'],
        'exe_contractors' => ['icon'=>'fa-building', 'color'=>'#0ea5e9'],
        'exe_work_orders' => ['icon'=>'fa-handshake', 'color'=>'#0ea5e9'],
        'exe_deployments' => ['icon'=>'fa-users-viewfinder', 'color'=>'#0ea5e9'],
        'exe_attendance' => ['icon'=>'fa-calendar-check', 'color'=>'#0ea5e9'],
        'exe_attendance_exceptions' => ['icon'=>'fa-triangle-exclamation', 'color'=>'#0ea5e9'],
        'exe_observations' => ['icon'=>'fa-edit', 'color'=>'#0ea5e9'],
        'exe_escalations' => ['icon'=>'fa-bullhorn', 'color'=>'#0ea5e9'],
        'exe_productivity' => ['icon'=>'fa-chart-line', 'color'=>'#0ea5e9'],
        'exe_reports' => ['icon'=>'fa-file-invoice', 'color'=>'#0ea5e9'],
    ];
    $permCols   = ['can_view'=>'View','can_create'=>'Create','can_edit'=>'Edit','can_delete'=>'Delete','can_approve'=>'Approve','can_block'=>'Block','can_export'=>'Export','can_override'=>'Override'];
    $roleLabels = [
        'welfare_admin'=>'Welfare Admin','welfare_user'=>'Welfare User','safety_user'=>'Safety Officer',
        'front_line_user'=>'Frontline User','pass_user'=>'Pass Issuer','execution_officer'=>'Exec. Officer'
    ];
    $roleBadgeColors = [
        'welfare_admin'=>'#7c3aed','welfare_user'=>'#6366f1','safety_user'=>'#059669',
        'front_line_user'=>'#0ea5e9','pass_user'=>'#f59e0b','execution_officer'=>'#ef4444'
    ];

    // Load existing role permissions
    $perms = [];
    $rows = db_fetch_all($conn, "SELECT * FROM role_permissions");
    foreach ($rows as $r) { $perms[$r['role_name']][$r['module']] = $r; }

    // Load all non-contractor, non-super_admin users for User Permissions tab
    $users = db_fetch_all($conn, "SELECT id, name, role, email FROM users WHERE role NOT IN ('contractor','super_admin','customer') AND status='active' ORDER BY role, name");

    // Load existing user extra permissions
    $userPerms = [];
    $upr = $conn->query("SELECT * FROM user_extra_permissions");
    if ($upr) { while($row = $upr->fetch_assoc()) { $userPerms[$row['user_id']][$row['module']] = $row; } }

    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-shield-halved" style="color:#6366f1;margin-right:10px;"></i> Permissions Control Center</h2>
        <p class="page-subtitle">Configure role-level permissions and grant individual users special access.</p>
      </div>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:0;border-bottom:2px solid var(--border-color);margin-bottom:24px;">
      <a href="?tab=roles" class="perm-tab <?= $activeTab==='roles'?'active':'' ?>"><i class="fas fa-users-cog"></i> Role Permissions</a>
      <a href="?tab=users" class="perm-tab <?= $activeTab==='users'?'active':'' ?>"><i class="fas fa-user-shield"></i> User Permissions</a>
    </div>

    <!-- ═══════════════════════════ ROLE PERMISSIONS TAB ═══════════════════════════ -->
    <?php if ($activeTab === 'roles'): ?>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
      <select class="form-control" id="roleFilter" onchange="filterByRole()" style="width:200px;">
        <option value="">All Roles</option>
        <?php foreach($roles as $r): ?>
        <option value="<?= $r ?>"><?= $roleLabels[$r] ?? ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" id="moduleFilter" onchange="filterByModule()" style="width:200px;">
        <option value="">All Modules</option>
        <?php foreach(array_keys($modules) as $m): ?>
        <option value="<?= $m ?>"><?= ucwords(str_replace('_',' ',$m)) ?></option>
        <?php endforeach; ?>
      </select>
      <div style="margin-left:auto;display:flex;gap:10px;">
        <button class="btn" style="background:rgba(99,102,241,.1);color:#6366f1;" onclick="toggleAll(true)"><i class="fas fa-check-square"></i> Check All Visible</button>
        <button class="btn" style="background:rgba(239,68,68,.1);color:#ef4444;" onclick="toggleAll(false)"><i class="fas fa-square"></i> Uncheck All Visible</button>
        <button class="btn btn-primary" id="saveRolePerm" onclick="saveRolePermissions()"><i class="fas fa-save"></i> Save Role Permissions</button>
      </div>
    </div>

    <?php foreach($roles as $role): ?>
    <div class="card glass role-section" data-role="<?= $role ?>" style="margin-bottom:20px;overflow:hidden;">
      <div class="card-header" style="background:linear-gradient(135deg,<?= $roleBadgeColors[$role]??'#6366f1' ?>18,transparent);border-bottom:2px solid <?= $roleBadgeColors[$role]??'#6366f1' ?>30;">
        <div style="display:flex;align-items:center;gap:12px;">
          <span class="badge" style="background:<?= $roleBadgeColors[$role]??'#6366f1' ?>;color:#fff;font-size:12px;padding:5px 12px;border-radius:20px;"><?= $roleLabels[$role] ?? $role ?></span>
          <span style="font-size:13px;color:var(--text-muted);">Module-level access control</span>
          <button class="btn btn-sm" style="margin-left:auto;background:rgba(16,185,129,.1);color:#10b981;font-size:11px;" onclick="checkAll('<?= $role ?>')"><i class="fas fa-check-double"></i> Grant All</button>
          <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:#ef4444;font-size:11px;" onclick="uncheckAll('<?= $role ?>')"><i class="fas fa-times"></i> Revoke All</button>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table" style="font-size:12px;min-width:900px;">
          <thead>
            <tr>
              <th style="min-width:150px;position:sticky;left:0;background:var(--card-bg);z-index:1;">Module</th>
              <?php foreach($permCols as $pk => $pl): ?>
              <th style="text-align:center;min-width:75px;"><?= $pl ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($modules as $mod => $meta):
              $p = $perms[$role][$mod] ?? [];
              $isGranted = false; foreach($permCols as $pcol=>$_) { if(!empty($p[$pcol])) { $isGranted=true; break; } }
            ?>
            <tr class="mod-row" data-module="<?= $mod ?>" style="<?= $isGranted?'background:rgba(16,185,129,.03)':'' ?>">
              <td style="position:sticky;left:0;background:inherit;z-index:1;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <i class="fas <?= $meta['icon'] ?>" style="color:<?= $meta['color'] ?>;width:16px;"></i>
                  <strong><?= ucwords(str_replace('_',' ',$mod)) ?></strong>
                </div>
              </td>
              <?php foreach($permCols as $col => $label):
                $checked = !empty($p[$col]) ? 'checked' : '';
              ?>
              <td style="text-align:center;">
                <input type="checkbox" class="perm-check" 
                  data-role="<?= $role ?>" data-module="<?= $mod ?>" data-perm="<?= $col ?>"
                  <?= $checked ?> 
                  onchange="highlightRow(this)">
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- ═══════════════════════════ USER PERMISSIONS TAB ═══════════════════════════ -->
    <?php else: ?>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
      <div style="font-size:13px;color:var(--text-muted);">
        <i class="fas fa-info-circle text-primary"></i>
        Grant individual users extra capabilities beyond their role's default permissions.
      </div>
      <div style="margin-left:auto;">
        <button class="btn btn-primary" id="saveUserPerm" onclick="saveUserPermissions()"><i class="fas fa-save"></i> Save User Permissions</button>
      </div>
    </div>

    <?php foreach($users as $u): 
      $uid = $u['id'];
      $rc  = $roleBadgeColors[$u['role']] ?? '#6366f1';
      $rl  = $roleLabels[$u['role']] ?? ucfirst(str_replace('_',' ',$u['role']));
    ?>
    <div class="card glass user-perm-section" data-userid="<?= $uid ?>" style="margin-bottom:20px;overflow:hidden;">
      <div class="card-header" style="background:linear-gradient(135deg,<?= $rc ?>18,transparent);border-bottom:2px solid <?= $rc ?>30;">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <div style="width:40px;height:40px;border-radius:50%;background:<?= $rc ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;flex-shrink:0;">
            <?= strtoupper(substr($u['name'],0,1)) ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($u['name']) ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
          </div>
          <span class="badge" style="background:<?= $rc ?>;color:#fff;font-size:11px;padding:4px 10px;border-radius:20px;"><?= $rl ?></span>
          <div style="margin-left:auto;display:flex;gap:8px;">
            <button class="btn btn-sm" style="background:rgba(16,185,129,.1);color:#10b981;font-size:11px;" onclick="checkAllUser(<?= $uid ?>)"><i class="fas fa-check-double"></i> Grant All</button>
            <button class="btn btn-sm" style="background:rgba(239,68,68,.1);color:#ef4444;font-size:11px;" onclick="uncheckAllUser(<?= $uid ?>)"><i class="fas fa-times"></i> Revoke All</button>
          </div>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table" style="font-size:12px;min-width:850px;">
          <thead>
            <tr>
              <th style="min-width:150px;position:sticky;left:0;background:var(--card-bg);z-index:1;">Module</th>
              <?php foreach($permCols as $pk => $pl): ?>
              <th style="text-align:center;min-width:70px;"><?= $pl ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($modules as $mod => $meta):
              $up = $userPerms[$uid][$mod] ?? [];
              $isGranted = false; foreach($permCols as $pcol=>$_) { if(!empty($up[$pcol])) { $isGranted=true; break; } }
            ?>
            <tr style="<?= $isGranted?'background:rgba(16,185,129,.04)':'' ?>">
              <td style="position:sticky;left:0;background:inherit;z-index:1;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <i class="fas <?= $meta['icon'] ?>" style="color:<?= $meta['color'] ?>;width:16px;"></i>
                  <strong><?= ucwords(str_replace('_',' ',$mod)) ?></strong>
                </div>
              </td>
              <?php foreach($permCols as $col => $label):
                $checked = !empty($up[$col]) ? 'checked' : '';
              ?>
              <td style="text-align:center;">
                <input type="checkbox" class="user-perm-check"
                  data-userid="<?= $uid ?>" data-module="<?= $mod ?>" data-perm="<?= $col ?>"
                  <?= $checked ?>
                  onchange="highlightRow(this)">
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div id="perm-toast" class="um-toast" style="display:none;"></div>

    <style>
    .perm-tab { padding:12px 24px;font-size:14px;font-weight:600;color:var(--text-muted);border-bottom:3px solid transparent;margin-bottom:-2px;text-decoration:none;display:flex;align-items:center;gap:8px;transition:.2s; }
    .perm-tab:hover { color:var(--text-primary); }
    .perm-tab.active { color:#6366f1;border-bottom-color:#6366f1; }
    .perm-check, .user-perm-check { width:16px; height:16px; cursor:pointer; accent-color:#6366f1; }
    .perm-check:checked, .user-perm-check:checked { accent-color:#10b981; }
    .um-toast { position:fixed; bottom:30px; right:30px; z-index:99999; padding:14px 24px; border-radius:12px; font-size:14px; font-weight:600; color:#fff; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,0.2); animation: slideUp .3s ease; }
    .um-toast.success { background:#10b981; }
    .um-toast.error { background:#ef4444; }
    @keyframes slideUp { from{transform:translateY(20px);opacity:0;}to{transform:translateY(0);opacity:1;} }
    .form-control { padding:8px 12px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px; }
    </style>

    <script>
    // Role filter
    function filterByRole() {
        const val = document.getElementById('roleFilter').value;
        document.querySelectorAll('.role-section').forEach(s => {
            s.style.display = (!val || s.getAttribute('data-role') === val) ? 'block' : 'none';
        });
    }

    // Module filter
    function filterByModule() {
        const val = document.getElementById('moduleFilter').value;
        document.querySelectorAll('.mod-row').forEach(r => {
            r.style.display = (!val || r.getAttribute('data-module') === val) ? '' : 'none';
        });
    }

    // Check all visible
    function toggleAll(checked) {
        document.querySelectorAll('.perm-check:not([style*="display:none"])').forEach(cb => {
            cb.closest('tr').style.display !== 'none' && (cb.checked = checked);
        });
    }

    // Check all for a specific role
    function checkAll(role) {
        document.querySelectorAll(`.perm-check[data-role="${role}"]`).forEach(cb => cb.checked = true);
    }
    function uncheckAll(role) {
        document.querySelectorAll(`.perm-check[data-role="${role}"]`).forEach(cb => cb.checked = false);
    }

    // Check all for a specific user
    function checkAllUser(userId) {
        document.querySelectorAll(`.user-perm-check[data-userid="${userId}"]`).forEach(cb => cb.checked = true);
    }
    function uncheckAllUser(userId) {
        document.querySelectorAll(`.user-perm-check[data-userid="${userId}"]`).forEach(cb => cb.checked = false);
    }

    // Highlight row if any checked
    function highlightRow(cb) {
        const row = cb.closest('tr');
        const anyChecked = [...row.querySelectorAll('input[type=checkbox]')].some(c => c.checked);
        row.style.background = anyChecked ? 'rgba(16,185,129,.06)' : '';
    }

    // Save ROLE permissions
    function saveRolePermissions() {
        const btn = document.getElementById('saveRolePerm');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const permMap = {};
        document.querySelectorAll('.perm-check').forEach(cb => {
            const role = cb.getAttribute('data-role');
            const mod  = cb.getAttribute('data-module');
            const perm = cb.getAttribute('data-perm');
            const key  = role + '|' + mod;
            if (!permMap[key]) permMap[key] = {role_name: role, module: mod};
            permMap[key][perm] = cb.checked ? 1 : 0;
        });

        fetch('../../api/admin/save_permissions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            },
            body: JSON.stringify({permissions: Object.values(permMap)})
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message || 'Saved!', data.success ? 'success' : 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Role Permissions';
        })
        .catch(err => {
            showToast('Error: ' + err.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Role Permissions';
        });
    }

    // Save USER extra permissions
    function saveUserPermissions() {
        const btn = document.getElementById('saveUserPerm');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const permMap = {};
        document.querySelectorAll('.user-perm-check').forEach(cb => {
            const uid  = cb.getAttribute('data-userid');
            const mod  = cb.getAttribute('data-module');
            const perm = cb.getAttribute('data-perm');
            const key  = uid + '|' + mod;
            if (!permMap[key]) permMap[key] = {user_id: uid, module: mod};
            permMap[key][perm] = cb.checked ? 1 : 0;
        });

        fetch('../../api/admin/save_user_permissions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            },
            body: JSON.stringify({permissions: Object.values(permMap)})
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message || 'Saved!', data.success ? 'success' : 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save User Permissions';
        })
        .catch(err => {
            showToast('Error: ' + err.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save User Permissions';
        });
    }

    function showToast(msg, type) {
        const t = document.getElementById('perm-toast');
        t.className = 'um-toast ' + type;
        t.innerHTML = '<i class="fas fa-' + (type==='success'?'check-circle':'exclamation-circle') + '"></i> ' + msg;
        t.style.display = 'flex';
        setTimeout(() => t.style.display = 'none', 3500);
    }
    </script>
    <?php
}

renderLayout('Permissions Control Center', 'renderContent', $role, $name);
?>
