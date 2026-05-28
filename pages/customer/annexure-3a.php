<?php
require_once '../../include/auth.php';
checkAuth(['customer', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

if (empty($customer_code) || $role !== 'customer') {
    die('<div class="alert alert-danger m-5">Invalid Session. Please login as a customer.</div>');
}

// 1. Fetch All Active Work Orders & Mapped Contractors for this Customer
$work_orders = db_fetch_all($conn, "
    SELECT wo.*, c.customer_name, v.vendor_name, v.gst_no, v.pf_no as epf_code, v.esi_no as esi_code, v.address as vendor_address
    FROM work_orders wo
    LEFT JOIN sap_customer_master c ON c.customer_code = wo.customer_code
    LEFT JOIN sap_vendor_master v ON v.vendor_code = wo.vendor_code
    WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
    ORDER BY wo.id DESC
", 's', [$customer_code]);

$selected_wo_no = $_GET['wo'] ?? '';
$work_order = null;
if ($selected_wo_no) {
    foreach ($work_orders as $wo) {
        if (($wo['work_order_no'] ?? '') === $selected_wo_no) {
            $work_order = $wo;
            break;
        }
    }
}
if (!$work_order) {
    $work_order = $work_orders[0] ?? null;
}
if (!$work_order) {
    $customer_row = $customer_code ? db_single($conn, "SELECT customer_name FROM sap_customer_master WHERE customer_code = ?", 's', [$customer_code]) : null;
    $work_order = [
        'customer_code' => $customer_code,
        'customer_name' => $customer_row['customer_name'] ?? $name,
        'work_order_no' => '',
        'project_name' => '',
        'department' => '',
        'vendor_code' => '',
        'vendor_name' => '',
        'gst_no' => '',
        'epf_code' => '',
        'esi_code' => '',
        'vendor_address' => '',
    ];
}
$vendor_code = $work_order['vendor_code'] ?? '';

// 2. Fetch Contractor Profile (from Contractor Registration - approved data)
$c = $vendor_code ? db_single($conn, "SELECT * FROM contractors WHERE vendor_code = ?", 's', [$vendor_code]) : null;
$is_registered = ($c && $c['status'] === 'approved') ? true : false;

// Handle Edit Mode
$edit_id = $_GET['edit_id'] ?? null;
$existing_data = null;
if ($edit_id) {
    $existing_data = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE id = ? AND customer_code = ?", 'is', [$edit_id, $customer_code]);
}
if (!$existing_data && $work_order) {
    $existing_data = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE customer_code = ? AND work_order_no = ? ORDER BY id DESC LIMIT 1", 'ss', [$customer_code, $work_order['work_order_no']]);
    if ($existing_data) {
        $edit_id = $existing_data['id'];
    }
}

function renderContent() {
    global $conn, $c, $is_registered, $vendor_code, $customer_code, $work_order, $work_orders, $existing_data, $edit_id;

    $a3_status = strtolower($existing_data['status'] ?? 'new');
    $is_resubmit_mode = (($_GET['resubmit'] ?? '') === '1');
    $is_locked = in_array($a3_status, ['pending', 'resubmitted'], true);
    $is_approved_limited_edit = $a3_status === 'approved';
    $is_limited_update_mode = $is_locked || $is_approved_limited_edit;
    $is_approved_view_only = false;
    $statusClass = $a3_status === 'approved' ? 'success' : ($a3_status === 'rejected' ? 'danger' : (in_array($a3_status, ['pending', 'resubmitted'], true) ? 'warning' : 'secondary'));
    $readonly_attr = ($is_locked || $is_approved_limited_edit || $is_approved_view_only) ? 'readonly' : '';
    $disabled_attr = ($is_locked || $is_approved_limited_edit || $is_approved_view_only) ? 'disabled' : '';
    $limited_edit_readonly_attr = $is_approved_view_only ? 'readonly' : '';
    $limited_edit_disabled_attr = $is_approved_view_only ? 'disabled' : '';
    $saved_limited_row_readonly_attr = $is_limited_update_mode ? 'readonly' : $limited_edit_readonly_attr;
    $saved_limited_file_disabled_attr = $is_limited_update_mode ? 'disabled' : $limited_edit_disabled_attr;
    $saved_limited_action_disabled_attr = $is_limited_update_mode ? 'disabled' : $limited_edit_disabled_attr;
    $ecp_choice_disabled_attr = $is_limited_update_mode ? 'disabled' : $limited_edit_disabled_attr;
    $submit_disabled_attr = $is_approved_view_only ? 'disabled' : '';
    $draft_disabled_attr = ($is_locked || $is_approved_view_only) ? 'disabled' : '';
    $worker_category_source = $existing_data['worker_category'] ?? '';
    $worker_cats = !empty($worker_category_source) ? array_map('trim', explode(',', $worker_category_source)) : [];
    $selected_ecp_covered = $existing_data['ecp_covered'] ?? 'YES';

    $stored_reason = $existing_data['epf_esi_exemption_reason'] ?? '';
    $clean_reason = function($value) {
        $value = trim((string)$value);
        do {
            $old = $value;
            $value = preg_replace('/^(EPF Reason|ESI Reason|EC Policy Reason):\s*/i', '', $value);
            $value = trim($value);
        } while ($value !== $old);
        return $value;
    };
    $reason_value = function($label) use ($stored_reason, $clean_reason) {
        if (preg_match('/' . preg_quote($label, '/') . ':\s*(.*?)(?=\n(?:EPF Reason|ESI Reason|EC Policy Reason):|$)/is', $stored_reason, $m)) {
            return $clean_reason($m[1]);
        }
        return '';
    };
    $epf_reason = $reason_value('EPF Reason');
    $esi_reason = $reason_value('ESI Reason');
    $ecp_reason = $reason_value('EC Policy Reason');
    if (empty($epf_reason) && empty($esi_reason) && empty($ecp_reason) && !empty($stored_reason)) {
        $ecp_reason = $clean_reason($stored_reason);
    }

    $yes_selected_by_default = function($raw, $no_reason, $yes_detail = '') {
        $value = strtoupper(trim((string)$raw));
        if ($value === 'YES' || $value === '1' || $value === '') {
            return true;
        }
        if (($value === 'NO' || $value === '0') && trim((string)$no_reason) !== '' && trim((string)$yes_detail) === '') {
            return false;
        }
        return true;
    };
    $epf_selected_yes = $yes_selected_by_default($existing_data['is_epf_registered'] ?? 'YES', $epf_reason, $existing_data['epf_code'] ?? '');
    $esi_selected_yes = $yes_selected_by_default($existing_data['is_esi_registered'] ?? 'YES', $esi_reason, $existing_data['esi_code'] ?? '');
    $ecp_selected_yes = $yes_selected_by_default($selected_ecp_covered, $ecp_reason, $existing_data['ecp_details_json'] ?? '');

    $ecp_rows = [];
    if (!empty($existing_data['ecp_details_json'])) {
        $decoded = json_decode($existing_data['ecp_details_json'], true);
        if (is_array($decoded)) $ecp_rows = $decoded;
    }
    if (empty($ecp_rows)) $ecp_rows[] = ['ecp_number' => '', 'ecp_valid_from' => '', 'ecp_valid_to' => '', 'insurance_company' => ''];

    $license_rows = [];
    if (!empty($existing_data['license_details_json'])) {
        $decoded = json_decode($existing_data['license_details_json'], true);
        if (is_array($decoded)) $license_rows = $decoded;
    }
    if (empty($license_rows)) {
        $license_rows[] = [
            'license_no' => $existing_data['labour_license_no'] ?? '',
            'validity' => $existing_data['labour_license_issued_by'] ?? '',
            'license_issued' => $existing_data['labour_license_issued_by'] ?? '',
            'issued_date' => $existing_data['labour_license_issue_date'] ?? '',
            'expiry_date' => $existing_data['labour_license_expiry_date'] ?? '',
            'file_path' => ''
        ];
    }

    $threshold_row = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'labour_license_threshold'");
    $licence_threshold = intval($threshold_row['setting_value'] ?? 20);
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; color: #1e293b; }
        
        .nav-tabs { border-bottom: 2px solid #e2e8f0; }
        .nav-tabs .nav-link { 
            font-weight: 700; 
            color: #64748b; 
            border: none; 
            padding: 16px 24px; 
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-tabs .nav-link.active { 
            color: var(--primary-color) !important; 
            background: transparent; 
        }
        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px 3px 0 0;
        }
        
        .card { 
            border: none; 
            border-radius: 16px; 
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header { 
            background-color: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
        }
        
        .card-title {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-tag {
            font-size: 0.65rem;
            font-weight: 900;
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .form-label { 
            font-weight: 700; 
            font-size: 0.8rem; 
            color: #475569; 
            text-transform: uppercase; 
            margin-bottom: 8px; 
            letter-spacing: 0.025em;
        }
        
        .form-control, .form-select { 
            border-radius: 10px; 
            border: 1px solid #cbd5e1; 
            padding: 12px 16px; 
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .form-control:focus { 
            border-color: var(--secondary-color); 
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); 
        }
        
        .required::after { content: " *"; color: var(--danger-color); }
        
        .btn-submit { 
            padding: 14px 48px; 
            font-weight: 800; 
            border-radius: 12px; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            transition: all 0.3s;
        }
        
        .table thead th { 
            background: #f8fafc; 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            color: #64748b; 
            font-weight: 800; 
            letter-spacing: 0.05em;
            padding: 12px 16px;
        }

        .sticky-bottom-bar {
            position: sticky;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-top: 1px solid #e2e8f0;
            padding: 1.5rem;
            z-index: 1000;
            margin: 0 -1.5rem -1.5rem -1.5rem;
        }

        .upload-box { 
            border: 2px dashed #cbd5e1; 
            border-radius: 12px; 
            padding: 20px; 
            text-align: center; 
            transition: all 0.3s ease; 
            cursor: pointer;
            background: #fff;
        }
        .upload-box:hover { 
            border-color: var(--secondary-color); 
            background: #f0f7ff; 
        }
        .upload-box i { font-size: 24px; color: #94a3b8; margin-bottom: 10px; }
        .upload-box .file-name { font-size: 11px; color: var(--secondary-color); font-weight: 600; margin-top: 8px; }

        .status-badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-resubmitted { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }

        #annexure3aRegistration .registration-card {
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 18px;
            box-shadow: none;
        }
        #annexure3aRegistration .registration-section-header {
            background: #eaf3ff;
            border-left: 4px solid #2b6cb0;
            padding: 10px 14px;
            font-weight: 700;
            color: #1e3a5f;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        #annexure3aRegistration .registration-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 22px;
        }
        #annexure3aRegistration .span-2 { grid-column: 1 / -1; }
        #annexure3aRegistration .gov-radio-group { display:flex; flex-wrap:wrap; gap:18px; align-items:center; min-height:42px; }
        #annexure3aRegistration .gov-table { border: 1px solid #cfd8e3; margin-bottom: 0; }
        #annexure3aRegistration .gov-table th {
            background: #f1f6fd;
            color: #1e3a5f;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #cfd8e3;
            white-space: nowrap;
        }
        #annexure3aRegistration .gov-table td { border: 1px solid #dbe3ef; vertical-align: middle; }
        #annexure3aRegistration .gov-table .form-control { min-width: 140px; }
        #annexure3aRegistration .form-control,
        #annexure3aRegistration .form-select {
            min-height: 42px;
            border-radius: 8px;
            border: 1px solid #cfd8e3;
            padding: 9px 12px;
        }
        #annexure3aRegistration textarea.form-control { min-height: 100px; }
        .registration-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            padding: 18px 0 6px;
        }
        .registration-actions .btn {
            min-width: 132px;
            min-height: 38px;
            border-radius: 5px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1.2;
        }
        .btn-reg-prev { border: 1px solid #8aa4c8; color: #1f2937; background: #fff; }
        .btn-reg-prev:hover { background: #f8fafc; border-color: #2b6cb0; color: #1e3a5f; }
        .btn-reg-draft { border: 1px solid #2b6cb0; color: #2b6cb0; background: #fff; }
        .btn-reg-draft:hover { background: #eff6ff; color: #1e3a5f; }
        .btn-reg-submit { border: 1px solid #2b6cb0; color: #fff !important; background: #2b6cb0; }
        .btn-reg-submit:hover { background: #1e5a96; border-color: #1e5a96; color: #fff !important; }
        .registration-actions .btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }
        @media (max-width: 768px) {
            #annexure3aRegistration .registration-grid { grid-template-columns: 1fr; }
            #annexure3aRegistration .span-2 { grid-column: auto; }
            .registration-actions { flex-direction: column; }
            .registration-actions .btn { width: 100%; }
        }
    </style>

<div class="container-fluid py-4 px-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-extrabold mb-1" style="font-weight: 800; color: #1e293b;">Customer Information Form</h2>
            <p class="text-muted mb-0">Registration details for welfare verification</p>
        </div>
        <div class="text-end">
            <?php if ($existing_data): ?>
                <span class="badge rounded-pill bg-<?= $statusClass ?> px-4 py-2 shadow-sm me-2" style="font-size:12px;"><?= strtoupper($a3_status) ?></span>
            <?php endif; ?>
            <a href="welfare-actions.php" class="btn btn-outline-primary rounded-pill px-3 me-2">
                <i class="fas fa-clock-rotate-left me-1"></i> Welfare Action History
            </a>
            <button class="btn btn-outline-secondary rounded-pill px-3 me-2" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print / PDF
            </button>
        </div>
    </div>

    <?php if (in_array($a3_status, ['rejected', 'correction_required', 'hold'], true)): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="background:#fff7ed; color:#9a3412;">
            <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center">
                <div>
                    <i class="fas fa-circle-exclamation me-2"></i>
                    Welfare action recorded. Please open history to view reason, rejection date and attachment.
                </div>
                <a href="welfare-actions.php" class="btn btn-sm btn-warning fw-bold">View History</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="complianceTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#contractorDetails" role="tab">
                <i class="fas fa-building me-2"></i> 1. Basic Info
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="statutory-tab" data-bs-toggle="tab" href="#statutoryDetails" role="tab">
                <i class="fas fa-file-signature me-2"></i> 2. Registration
            </a>
        </li>
        <li class="nav-item d-none">
            <a class="nav-link" id="insurance-tab" data-bs-toggle="tab" href="#insuranceDetails" role="tab">
                <i class="fas fa-shield-alt me-2"></i> 3. Insurance & License
            </a>
        </li>
        <li class="nav-item d-none">
            <a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#mandatoryDocuments" role="tab">
                <i class="fas fa-upload me-2"></i> 4. Documents
            </a>
        </li>
        <li class="nav-item d-none">
            <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#submissionHistory" role="tab">
                <i class="fas fa-history me-2"></i> 5. History
            </a>
        </li>
    </ul>

    <form id="annexure3AForm" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="vendor_code" id="vendor_code" value="<?= htmlspecialchars($vendor_code) ?>">
        <input type="hidden" name="customer_code" id="hidden_customer_code" value="<?= htmlspecialchars($customer_code) ?>">
        <input type="hidden" name="work_order_no" id="hidden_work_order_no" value="<?= htmlspecialchars($work_order['work_order_no'] ?? '') ?>">
        <?php if($edit_id): ?>
            <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
        <?php endif; ?>

        <div class="tab-content" id="complianceTabsContent">
            <!-- ================= BASIC INFO TAB ================= -->
            <div class="tab-pane fade show active" id="contractorDetails" role="tabpanel">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-building fa-sm"></i>
                            </div>
                            <h5 class="mb-0 text-primary">Customer Information</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- CUSTOMER SIDE -->
                        <h6 class="text-uppercase fw-bold text-muted small mb-3">Customer Side (Auto-Fetched from Mapping)</h6>
                        <div class="row g-3 mb-4 p-3 bg-light rounded shadow-sm" style="border-left: 4px solid var(--primary-color);">
                            <div class="col-md-3">
                                <label class="form-label">Customer Code</label>
                                <input type="text" id="display_customer_code" class="form-control" value="<?= htmlspecialchars($work_order['customer_code'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Customer Name</label>
                                <input type="text" id="display_customer_name" class="form-control" value="<?= htmlspecialchars($work_order['customer_name'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Work Order No</label>
                                <input type="text" id="display_work_order_no" class="form-control" value="<?= htmlspecialchars($work_order['work_order_no'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project Name</label>
                                <input type="text" id="display_project_name" class="form-control" value="<?= htmlspecialchars($work_order['project_name'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" id="display_department" class="form-control" value="<?= htmlspecialchars($work_order['department'] ?? 'N/A') ?>" readonly>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="text-end mb-5">
                    <button type="button" class="btn btn-primary px-5 py-3 shadow-sm fw-bold rounded-pill" onclick="showTab('statutoryDetails')">
                        NEXT: REGISTRATION <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- ================= STATUTORY DETAILS TAB ================= -->
            <div class="tab-pane fade" id="statutoryDetails" role="tabpanel">
                <div id="annexure3aRegistration">
                    <div class="registration-card">
                        <div class="registration-section-header">1. Work Awarding Dept</div>
                        <div class="registration-grid">
                            <div class="span-2">
                                <label class="form-label required">Work Awarding Department</label>
                                <select class="form-select" name="work_awarding_department" required <?= $disabled_attr ?>>
                                    <option value="">-- Select Department --</option>
                                    <?php
                                    $depts = db_fetch_all($conn, "SELECT dept_name FROM master_departments WHERE status='active' ORDER BY dept_name ASC");
                                    foreach($depts as $d): ?>
                                        <option value="<?= htmlspecialchars($d['dept_name']) ?>" <?= (($existing_data['work_awarding_department'] ?? '') === $d['dept_name']) ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="registration-card">
                        <div class="registration-section-header">2. Whether Registered under EPF</div>
                        <div class="registration-grid">
                            <div>
                                <div class="gov-radio-group">
                                    <?php
                                    $epfYes = $epf_selected_yes;
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="epf_registered" id="epf_yes" value="YES" <?= $epfYes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                        <label class="form-check-label" for="epf_yes">YES</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="epf_registered" id="epf_no" value="NO" <?= !$epfYes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                        <label class="form-check-label" for="epf_no">NO</label>
                                    </div>
                                </div>
                            </div>
                            <div id="epfDetailsCard">
                                <label class="form-label required">EPF Establishment Code</label>
                                <input type="text" name="epf_code" id="epf_code" class="form-control" value="<?= htmlspecialchars($existing_data['epf_code'] ?? '') ?>" <?= $readonly_attr ?>>
                                <input type="hidden" name="epf_account_no" id="epf_account_no" value="<?= htmlspecialchars($existing_data['epf_account_no'] ?? '') ?>">
                            </div>
                            <div class="span-2" id="epfReasonCard">
                                <label class="form-label required">3. EPF Non-Registration Reason</label>
                                <textarea class="form-control" name="epf_non_registration_reason" id="epf_non_registration_reason" rows="3" placeholder="Enter reason for not registered under EPF" <?= $readonly_attr ?>><?= htmlspecialchars($epf_reason) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="registration-card">
                        <div class="registration-section-header">4. Whether Registered under ESI</div>
                        <div class="registration-grid">
                            <div>
                                <div class="gov-radio-group">
                                    <?php
                                    $esiYes = $esi_selected_yes;
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="esi_registered" id="esi_yes" value="YES" <?= $esiYes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                        <label class="form-check-label" for="esi_yes">YES</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="esi_registered" id="esi_no" value="NO" <?= !$esiYes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                        <label class="form-check-label" for="esi_no">NO</label>
                                    </div>
                                </div>
                            </div>
                            <div id="esi_code_container">
                                <label class="form-label required">ESI Establishment Code</label>
                                <input type="text" name="esi_code" id="esi_code" class="form-control" value="<?= htmlspecialchars($existing_data['esi_code'] ?? '') ?>" <?= $readonly_attr ?>>
                            </div>
                            <div class="span-2" id="esi_reason_container">
                                <label class="form-label required">Reason</label>
                                <textarea class="form-control" name="esi_non_registration_reason" id="esi_non_registration_reason" rows="3" placeholder="Enter reason for not registered under ESI" <?= $readonly_attr ?>><?= htmlspecialchars($esi_reason) ?></textarea>
                            </div>
                            <div class="span-2">
                                <div class="alert alert-warning py-2 px-3 mb-0 d-none" id="esi-ec-warning">Either ESI or EC Policy is mandatory</div>
                            </div>
                        </div>
                    </div>

                    <div class="registration-card">
                        <div class="registration-section-header">5. Wage Declaration</div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="wage_declaration" id="wage_declaration" value="I declare to pay minimum wage as per government norms" <?= !empty($existing_data['wage_declaration']) ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                            <label class="form-check-label fw-semibold" for="wage_declaration">With this I declare to pay minimum wage as per government norms.</label>
                        </div>
                        <input type="hidden" name="wage_category" value="<?= htmlspecialchars($existing_data['salary_category'] ?? ($existing_data['wage_category'] ?? '')) ?>">
                        <input type="hidden" name="salary_category" value="<?= htmlspecialchars($existing_data['salary_category'] ?? ($existing_data['wage_category'] ?? '')) ?>">
                    </div>

                    <div class="registration-card">
                        <div class="registration-section-header">6. Employee Compensation Policy</div>
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div class="gov-radio-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="ecp_covered" id="ecp_yes" value="YES" <?= $ecp_selected_yes ? 'checked' : '' ?> required <?= $ecp_choice_disabled_attr ?>>
                                    <label class="form-check-label" for="ecp_yes">YES</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="ecp_covered" id="ecp_no" value="NO" <?= !$ecp_selected_yes ? 'checked' : '' ?> required <?= $ecp_choice_disabled_attr ?>>
                                    <label class="form-check-label" for="ecp_no">NO</label>
                                </div>
                            </div>
                            <?php if ($ecp_choice_disabled_attr): ?>
                                <input type="hidden" name="ecp_covered" value="<?= $ecp_selected_yes ? 'YES' : 'NO' ?>">
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-reg-draft" id="addEcpBtn" onclick="addEcpRow()" <?= $limited_edit_disabled_attr ?>>Add Row</button>
                        </div>
                        <div class="table-responsive" id="ecpTableWrap">
                            <table class="table gov-table align-middle" id="ecpTable">
                                <thead>
                                    <tr><th>S.No</th><th>EC Policy Number</th><th>Valid From</th><th>Valid To</th><th>No. of Workers Under EC Policy</th><th>Action</th></tr>
                                </thead>
                                <tbody id="ecpTableBody">
                                    <?php foreach ($ecp_rows as $i => $row): ?>
                                        <tr class="ecp-row">
                                            <td class="sl-no text-center fw-bold"><?= $i + 1 ?></td>
                                            <td><input type="text" class="form-control" name="ecp_number[]" value="<?= htmlspecialchars($row['ecp_number'] ?? '') ?>" <?= $saved_limited_row_readonly_attr ?>></td>
                                            <td><input type="date" class="form-control ecp-from" name="ecp_valid_from[]" value="<?= htmlspecialchars($row['ecp_valid_from'] ?? '') ?>" onchange="validateEcpRowDates(this)" <?= $saved_limited_row_readonly_attr ?>></td>
                                            <td><input type="date" class="form-control ecp-to" name="ecp_valid_to[]" value="<?= htmlspecialchars($row['ecp_valid_to'] ?? '') ?>" onchange="validateEcpRowDates(this)" <?= $saved_limited_row_readonly_attr ?>><div class="invalid-feedback ecp-date-error">Valid From must be before Valid To.</div></td>
                                            <td><input type="number" class="form-control" name="ecp_workers[]" min="0" value="<?= htmlspecialchars($row['workers_under_policy'] ?? '') ?>" <?= $saved_limited_row_readonly_attr ?>></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm text-danger delete-btn" onclick="deleteEcpRow(this)" title="Delete row" <?= $saved_limited_action_disabled_attr ?> <?= $saved_limited_action_disabled_attr ? 'style="display:none;"' : '' ?>><i class="fas fa-trash-alt"></i><span class="visually-hidden">Delete</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="registration-card" id="reasonCard">
                        <div class="registration-section-header">7. EC Policy Non-Coverage Reason</div>
                        <textarea class="form-control" name="ecp_exemption_reason" id="ecp_exemption_reason" placeholder="Enter reason for not covered under EC Policy" <?= $limited_edit_readonly_attr ?>><?= htmlspecialchars($ecp_reason) ?></textarea>
                        <input type="hidden" name="epf_esi_exemption_reason" id="epf_esi_exemption_reason" value="<?= htmlspecialchars($existing_data['epf_esi_exemption_reason'] ?? '') ?>">
                    </div>

                    <div class="registration-card">
                        <div class="registration-section-header">8. Approximate Workforce Details</div>
                        <div class="registration-grid">
                            <div>
                                <label class="form-label required">No. of Workers Proposed to be Engaged</label>
                                <input type="number" class="form-control worker-count" name="workers_proposed_to_be_engaged" min="0" value="<?= htmlspecialchars($existing_data['workers_proposed_to_be_engaged'] ?? ($existing_data['total_workers'] ?? '')) ?>" required <?= $readonly_attr ?>>
                            </div>
                            <div>
                                <label class="form-label required">Category of Working</label>
                                <div class="gov-radio-group">
                                    <div class="form-check"><input class="form-check-input worker-cat-check" type="checkbox" name="worker_categories[]" value="Skilled" id="cat_skilled" <?= in_array('Skilled', $worker_cats) ? 'checked' : '' ?> <?= $disabled_attr ?>><label class="form-check-label" for="cat_skilled">Skilled</label></div>
                                    <div class="form-check"><input class="form-check-input worker-cat-check" type="checkbox" name="worker_categories[]" value="Semiskilled" id="cat_semiskilled" <?= in_array('Semiskilled', $worker_cats) ? 'checked' : '' ?> <?= $disabled_attr ?>><label class="form-check-label" for="cat_semiskilled">Semi-skilled</label></div>
                                    <div class="form-check"><input class="form-check-input worker-cat-check" type="checkbox" name="worker_categories[]" value="Unskilled" id="cat_unskilled" <?= in_array('Unskilled', $worker_cats) ? 'checked' : '' ?> <?= $disabled_attr ?>><label class="form-check-label" for="cat_unskilled">Unskilled</label></div>
                                </div>
                                <div class="text-danger mt-1" id="worker-cat-error" style="display:none; font-size:12px; font-weight:600;">At least one worker category must be selected.</div>
                            </div>
                        </div>
                    </div>

                    <div class="registration-card" id="section7Card">
                        <div class="registration-section-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <span>9. Labour License Details</span>
                            <span id="licenceMandatoryBadge" class="badge bg-warning text-dark" style="display:none;">Mandatory (Workers &ge; <?= $licence_threshold ?>)</span>
                        </div>
                        <div class="d-flex justify-content-end mb-3"><button type="button" class="btn btn-sm btn-reg-draft" onclick="addLicenseRow()" <?= $limited_edit_disabled_attr ?>>Add Row</button></div>
                        <div class="table-responsive">
                            <table class="table gov-table align-middle" id="licenseTable">
                                <thead>
                                    <tr><th>S.No</th><th>Labour No</th><th>Issued By</th><th>Issued Date</th><th>Expiry Date</th><th>License Upload</th><th>Action</th></tr>
                                </thead>
                                <tbody id="licenseTableBody">
                                    <?php foreach ($license_rows as $i => $row): $file_path = $row['file_path'] ?? ''; ?>
                                        <tr class="license-row">
                                            <td class="sl-no text-center fw-bold"><?= $i + 1 ?></td>
                                            <td><input type="text" class="form-control" name="license_no[]" value="<?= htmlspecialchars($row['license_no'] ?? '') ?>" <?= $saved_limited_row_readonly_attr ?>></td>
                                            <td><input type="text" class="form-control" name="license_validity[]" value="<?= htmlspecialchars($row['validity'] ?? ($row['license_issued'] ?? '')) ?>" <?= $saved_limited_row_readonly_attr ?>><input type="hidden" name="license_issued[]" value="<?= htmlspecialchars($row['license_issued'] ?? ($row['validity'] ?? '')) ?>"></td>
                                            <td><input type="date" class="form-control lic-issued" name="issued_date[]" value="<?= htmlspecialchars($row['issued_date'] ?? '') ?>" onchange="validateLicRowDates(this)" <?= $saved_limited_row_readonly_attr ?>></td>
                                            <td><input type="date" class="form-control lic-expiry" name="expiry_date[]" value="<?= htmlspecialchars($row['expiry_date'] ?? '') ?>" onchange="validateLicRowDates(this)" <?= $saved_limited_row_readonly_attr ?>><div class="invalid-feedback lic-date-error">Issued Date must be before Expiry Date.</div></td>
                                            <td>
                                                <input type="file" class="form-control" name="license_file[]" accept="application/pdf,.pdf" <?= $saved_limited_file_disabled_attr ?> <?= $saved_limited_file_disabled_attr ? 'style="display:none;"' : '' ?>>
                                                <input type="hidden" name="existing_license_file[]" value="<?= htmlspecialchars($file_path) ?>">
                                                <?php if (!empty($file_path)): ?><a href="../../<?= htmlspecialchars($file_path) ?>" target="_blank" class="d-block mt-1 text-success fw-bold" style="font-size:12px;">Uploaded File</a><?php endif; ?>
                                            </td>
                                            <td class="text-center"><button type="button" class="btn btn-sm text-danger delete-btn" onclick="deleteLicenseRow(this)" title="Delete row" <?= $saved_limited_action_disabled_attr ?> <?= $saved_limited_action_disabled_attr ? 'style="display:none;"' : '' ?>><i class="fas fa-trash-alt"></i><span class="visually-hidden">Delete</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="registration-card">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="registration-section-header">10. Kerala Labour Welfare Fund Registration No</div>
                                <input type="text" class="form-control" name="labour_license_appl_no" value="<?= htmlspecialchars($existing_data['labour_license_appl_no'] ?? '') ?>" <?= $readonly_attr ?>>
                            </div>
                            <div class="col-md-6">
                                <div class="registration-section-header">11. Labour Identification Number</div>
                                <input type="text" class="form-control" name="labour_identification_no" id="labour_identification_no" pattern="^[0-9]+$" value="<?= htmlspecialchars($existing_data['labour_identification_no'] ?? '') ?>" placeholder="Numeric digits only" <?= $readonly_attr ?>>
                                <div class="invalid-feedback">LIN number must be numeric only.</div>
                            </div>
                        </div>
                    </div>
                    <div class="registration-card"><div class="registration-section-header">12. Name of Contact Person</div><input type="text" class="form-control" name="contact_person" id="contact_person" pattern="^[a-zA-Z\s]+$" value="<?= htmlspecialchars($existing_data['contact_person'] ?? '') ?>" required placeholder="Alphabets only" <?= $readonly_attr ?>></div>
                    <div class="registration-card">
                        <div class="registration-section-header">13. Mobile Number + Alternate Mobile Number</div>
                        <div class="registration-grid">
                            <div><label class="form-label required">Mobile Number</label><input type="text" class="form-control" name="mobile" pattern="^[0-9]{10}$" value="<?= htmlspecialchars($existing_data['mobile'] ?? '') ?>" required <?= $readonly_attr ?>></div>
                            <div><label class="form-label">Alternate Mobile Number</label><input type="text" class="form-control" name="vendor_mob2" pattern="^[0-9]{10}$" value="<?= htmlspecialchars($existing_data['vendor_mob2'] ?? '') ?>" <?= $readonly_attr ?>></div>
                        </div>
                    </div>
                    <div class="registration-card"><div class="registration-section-header">14. Remarks</div><textarea class="form-control" name="remarks" placeholder="Enter remarks" <?= $readonly_attr ?>><?= htmlspecialchars($existing_data['remarks'] ?? '') ?></textarea></div>
                </div>

                <div class="registration-actions">
                    <button type="button" class="btn btn-reg-prev px-4" onclick="showTab('contractorDetails')">Previous</button>
                    <?php if ($is_locked): ?>
                        <span class="alert alert-info mb-0 py-2 px-3">Submitted form is locked except EC Policy and Labour License add rows.</span>
                    <?php else: ?>
                        <button type="button" class="btn btn-reg-draft px-4" id="saveDraftBtn" onclick="saveDraft()">Save Draft</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-reg-submit px-4" id="submitBtn" <?= $submit_disabled_attr ?>><?= $is_limited_update_mode ? 'Resubmit for Welfare Approval' : 'Submit Registration' ?></button>
                </div>
            </div>

            <!-- ================= INSURANCE & LICENSE TAB ================= -->
            <div class="tab-pane fade d-none" id="insuranceDetails" role="tabpanel">
                <fieldset disabled>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4 h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info text-white rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-shield-alt fa-sm"></i>
                                    </div>
                                    <h5 class="mb-0 text-info">WC Insurance Policy</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-12 mb-2">
                                        <label class="form-label required">Policy Name</label>
                                        <input type="text" name="insurance_policy_name" class="form-control" value="<?= htmlspecialchars($existing_data['insurance_policy_name'] ?? '') ?>" placeholder="Workmen Compensation Policy" required>
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <label class="form-label required">Policy Number</label>
                                        <input type="text" name="insurance_policy_no" class="form-control" value="<?= htmlspecialchars($existing_data['insurance_policy_no'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label required">Validity Date</label>
                                        <input type="date" name="insurance_validity" id="insurance_validity" class="form-control" value="<?= htmlspecialchars($existing_data['insurance_validity'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label required">Workers Covered</label>
                                        <input type="number" name="insurance_workers_count" class="form-control" value="<?= $existing_data['insurance_workers_count'] ?? '' ?>" min="1" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4 h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="bg-danger text-white rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-stamp fa-sm"></i>
                                    </div>
                                    <h5 class="mb-0 text-danger">Labour License</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-12 mb-2">
                                        <label class="form-label required">License Number</label>
                                        <input type="text" name="labour_license_no" class="form-control" value="<?= htmlspecialchars($existing_data['labour_license_no'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <label class="form-label required">Issued By Authority</label>
                                        <input type="text" name="labour_license_issued_by" class="form-control" value="<?= htmlspecialchars($existing_data['labour_license_issued_by'] ?? '') ?>" placeholder="e.g. Assistant Labour Commissioner" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Issue Date</label>
                                        <input type="date" name="labour_license_issue_date" id="license_issue" class="form-control" value="<?= htmlspecialchars($existing_data['labour_license_issue_date'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Expiry Date</label>
                                        <input type="date" name="labour_license_expiry_date" id="license_expiry" class="form-control" value="<?= htmlspecialchars($existing_data['labour_license_expiry_date'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5 gap-3 d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-secondary px-5 py-3 fw-bold rounded-pill" onclick="showTab('statutoryDetails')">PREVIOUS</button>
                    <button type="button" class="btn btn-primary px-5 py-3 shadow-sm fw-bold rounded-pill" onclick="showTab('mandatoryDocuments')">
                        NEXT: DOCUMENTS <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
                </fieldset>
            </div>

            <!-- ================= MANDATORY DOCUMENTS TAB ================= -->
            <div class="tab-pane fade d-none" id="mandatoryDocuments" role="tabpanel">
                <fieldset disabled>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-white text-dark rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-upload fa-sm"></i>
                            </div>
                            <h5 class="mb-0 text-white">Compliance Document Uploads</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-4">Allowed formats: <strong>PDF, JPG, PNG</strong>. Max size: <strong>5MB</strong> per file.</p>
                        <div class="row g-4">
                            <?php
                            $docs = [
                                'labour_license' => 'Labour License',
                                'insurance_policy' => 'Insurance Policy',
                                'epf_challan' => 'EPF Challan',
                                'esi_challan' => 'ESI Challan',
                                'bank_details' => 'Bank Details / Cancelled Cheque',
                                'pan' => 'PAN Card Copy',
                                'gst' => 'GST Registration Copy',
                                'agreement_copy' => 'Work Order / Agreement Copy'
                            ];
                            foreach($docs as $key => $label): 
                                $existing_doc = null;
                                if ($edit_id) {
                                    $existing_doc = db_single($conn, "SELECT * FROM contractor_documents WHERE annexure3a_id = ? AND doc_type = ?", 'is', [$edit_id, $key]);
                                }
                            ?>
                            <div class="col-md-4 col-xl-3">
                                <label class="form-label <?= $existing_doc ? '' : 'required' ?>"><?= $label ?></label>
                                <div class="upload-box" onclick="document.getElementById('file_<?= $key ?>').click()">
                                    <input type="file" name="<?= $key ?>" id="file_<?= $key ?>" class="d-none" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, '<?= $key ?>')" <?= $existing_doc ? '' : 'required' ?>>
                                    <i class="fas <?= $existing_doc ? 'fa-check-circle text-success' : 'fa-cloud-upload-alt' ?>"></i>
                                    <div style="font-size:11px; color:#64748b;"><?= $existing_doc ? 'Update file' : 'Click to upload' ?></div>
                                    <span class="file-name" id="name_<?= $key ?>"><?= $existing_doc ? basename($existing_doc['file_path']) : 'No file selected' ?></span>
                                </div>
                                <?php if($existing_doc): ?>
                                    <div class="mt-2 text-center">
                                        <a href="../../<?= $existing_doc['file_path'] ?>" target="_blank" class="btn btn-link btn-sm p-0 text-primary fw-bold" style="font-size: 11px;">Preview Current</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="sticky-bottom-bar d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary px-5 fw-bold rounded-pill" onclick="showTab('insuranceDetails')">PREVIOUS</button>
                    <button type="submit" class="btn btn-primary btn-submit px-5 shadow-lg rounded-pill" id="submitBtn">
                        <i class="fas fa-paper-plane me-2"></i> <?= $edit_id ? 'UPDATE SUBMISSION' : 'SUBMIT' ?>
                    </button>
                </div>
                </fieldset>
            </div>

            <!-- ================= HISTORY TAB ================= -->
            <div class="tab-pane fade" id="submissionHistory" role="tabpanel">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-secondary text-white rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-history fa-sm"></i>
                            </div>
                            <h5 class="mb-0 text-secondary">Compliance Submission History</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Submitted Date</th>
                                        <th>Work Order</th>
                                        <th>Salary Category</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $history = db_fetch_all($conn, "
                                        SELECT a.*, v.vendor_name 
                                        FROM contractor_annexure3a a
                                        LEFT JOIN sap_vendor_master v ON v.vendor_code = a.vendor_code
                                        WHERE a.customer_code = ? 
                                        ORDER BY a.created_at DESC
                                    ", 's', [$customer_code]);

                                    if(empty($history)): ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">No submissions found.</td></tr>
                                    <?php else:
                                        foreach($history as $h): 
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= date('d M Y', strtotime($h['created_at'])) ?></div>
                                                <small class="text-muted"><?= date('H:i', strtotime($h['created_at'])) ?></small>
                                            </td>
                                            <td><code><?= htmlspecialchars($h['work_order_no']) ?></code></td>
                                            <td><span class="badge bg-light text-dark border fw-bold"><?= strtoupper($h['salary_category']) ?></span></td>
                                            <td>
                                                <span class="status-badge badge-<?= strtolower($h['status']) ?>">
                                                    <?= $h['status'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle me-2" onclick="viewSubmission(<?= $h['id'] ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if(in_array($h['status'], ['pending', 'rejected', 'approved'], true)): ?>
                                                        <a href="?edit_id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-info rounded-circle" title="View">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if($h['status'] === 'approved'): ?>
                                                            <a href="?edit_id=<?= $h['id'] ?>&resubmit=1" class="btn btn-sm btn-outline-warning rounded-circle ms-2" title="Resubmit EC / Labour License">
                                                                <i class="fas fa-rotate"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal for Viewing Details -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Submission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <!-- Content loaded via JS -->
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    const ANNEXURE3A_LIMITED_EDIT = <?= $is_limited_update_mode ? 'true' : 'false' ?>;

    const workOrders = <?= json_encode($work_orders ?? []) ?>;

    function updateWorkOrderDetails(woNo) {
        const wo = workOrders.find(w => w.work_order_no === woNo);
        if (wo) {
            document.getElementById('hidden_customer_code').value = wo.customer_code || '';
            document.getElementById('hidden_work_order_no').value = wo.work_order_no || '';
            document.getElementById('display_customer_code').value = wo.customer_code || 'N/A';
            document.getElementById('display_customer_name').value = wo.customer_name || 'N/A';
            document.getElementById('display_work_order_no').value = wo.work_order_no || 'N/A';
            document.getElementById('display_project_name').value = wo.project_name || 'N/A';
            document.getElementById('display_department').value = wo.department || 'N/A';
            window.location.href = '?wo=' + encodeURIComponent(wo.work_order_no || '');
        }
    }
    function showTab(id) {
        const tabEl = document.querySelector(`a[href="#${id}"]`);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            console.error('Tab element not found for ID:', id);
        }
    }

    function getRadioValue(name) {
        return document.querySelector(`input[name="${name}"]:checked`)?.value || '';
    }

    function toggleEPF() {
        const yes = getRadioValue('epf_registered') === 'YES';
        const card = document.getElementById('epfDetailsCard');
        const reasonCard = document.getElementById('epfReasonCard');
        const code = document.getElementById('epf_code');
        const account = document.getElementById('epf_account_no');
        const reason = document.getElementById('epf_non_registration_reason');
        if (card) card.style.display = yes ? '' : 'none';
        if (reasonCard) reasonCard.style.display = yes ? 'none' : '';
        if (code) code.required = yes;
        if (account) account.required = false;
        if (reason) reason.required = false;
    }

    function toggleESI() {
        const yes = getRadioValue('esi_registered') === 'YES';
        const container = document.getElementById('esi_code_container');
        const reasonContainer = document.getElementById('esi_reason_container');
        const input = document.getElementById('esi_code');
        const reasonInput = document.getElementById('esi_non_registration_reason');
        if (container) container.style.display = yes ? '' : 'none';
        if (reasonContainer) reasonContainer.style.display = yes ? 'none' : '';
        if (input) input.required = yes;
        if (reasonInput) reasonInput.required = false;
        validateEPFESI(true);
    }

    function validateEPFESI(showPopup = false) {
        const invalid = getRadioValue('esi_registered') === 'NO' && getRadioValue('ecp_covered') !== 'YES';
        const warning = document.getElementById('esi-ec-warning');
        if (warning) warning.classList.toggle('d-none', !invalid);
        if (invalid && showPopup && typeof window.notifyUser === 'function') {
            window.notifyUser('Either ESI or EC Policy is mandatory', 'warning', 'Either ESI or EC Policy is mandatory');
        }
        return !invalid;
    }

    function toggleEcpPolicy() {
        const yes = getRadioValue('ecp_covered') === 'YES';
        document.getElementById('ecpTableWrap').style.display = yes ? '' : 'none';
        document.getElementById('addEcpBtn').style.display = yes ? '' : 'none';
        document.getElementById('reasonCard').style.display = yes ? 'none' : '';
        document.getElementById('ecp_exemption_reason').required = false;
        document.querySelectorAll('#ecpTableBody input').forEach(input => input.required = yes && input.type !== 'hidden');
        validateEPFESI(true);
    }

    function syncReasonSummary() {
        const cleanReasonValue = (value) => {
            let clean = (value || '').trim();
            let old = '';
            while (clean !== old) {
                old = clean;
                clean = clean.replace(/^(EPF Reason|ESI Reason|EC Policy Reason):\s*/i, '').trim();
            }
            return clean;
        };
        const parts = [];
        if (getRadioValue('epf_registered') === 'NO') {
            const epfReason = cleanReasonValue(document.getElementById('epf_non_registration_reason')?.value || '');
            const epfInput = document.getElementById('epf_non_registration_reason');
            if (epfInput) epfInput.value = epfReason;
            if (epfReason) parts.push('EPF Reason: ' + epfReason);
        }
        if (getRadioValue('esi_registered') === 'NO') {
            const esiReason = cleanReasonValue(document.getElementById('esi_non_registration_reason')?.value || '');
            const esiInput = document.getElementById('esi_non_registration_reason');
            if (esiInput) esiInput.value = esiReason;
            if (esiReason) parts.push('ESI Reason: ' + esiReason);
        }
        if (getRadioValue('ecp_covered') === 'NO') {
            const ecpReason = cleanReasonValue(document.getElementById('ecp_exemption_reason')?.value || '');
            const ecpInput = document.getElementById('ecp_exemption_reason');
            if (ecpInput) ecpInput.value = ecpReason;
            if (ecpReason) parts.push('EC Policy Reason: ' + ecpReason);
        }
        const hidden = document.getElementById('epf_esi_exemption_reason');
        if (hidden) hidden.value = parts.join('\n');
    }

    function syncLicenseIssuedFields() {
        document.querySelectorAll('#licenseTableBody .license-row').forEach(row => {
            const validity = row.querySelector('input[name="license_validity[]"]')?.value || '';
            const issuedBy = row.querySelector('input[name="license_issued[]"]');
            if (issuedBy) issuedBy.value = validity;
        });
    }

    function updateSlNos(tbodyId) {
        const rows = document.querySelectorAll(`#${tbodyId} tr`);
        rows.forEach((row, index) => {
            row.querySelector('.sl-no').innerText = index + 1;
            const deleteBtn = row.querySelector('.delete-btn');
            if (!deleteBtn) return;
            if (deleteBtn.disabled) {
                deleteBtn.style.display = 'none';
                return;
            }
            deleteBtn.style.display = rows.length > 1 ? 'inline-block' : 'none';
        });
    }

    function validateEcpRowDates(input) {
        const row = input.closest('tr');
        const fromVal = row.querySelector('.ecp-from')?.value || '';
        const toInput = row.querySelector('.ecp-to');
        const toVal = toInput?.value || '';
        const err = row.querySelector('.ecp-date-error');
        if (fromVal && toVal && new Date(fromVal) > new Date(toVal)) {
            toInput.classList.add('is-invalid');
            if (err) err.style.display = 'block';
            return false;
        }
        if (toInput) toInput.classList.remove('is-invalid');
        if (err) err.style.display = 'none';
        return true;
    }

    function validateLicRowDates(input) {
        const row = input.closest('tr');
        const issueVal = row.querySelector('.lic-issued')?.value || '';
        const expiryInput = row.querySelector('.lic-expiry');
        const expiryVal = expiryInput?.value || '';
        const err = row.querySelector('.lic-date-error');
        if (issueVal && expiryVal && new Date(issueVal) > new Date(expiryVal)) {
            expiryInput.classList.add('is-invalid');
            if (err) err.style.display = 'block';
            return false;
        }
        if (expiryInput) expiryInput.classList.remove('is-invalid');
        if (err) err.style.display = 'none';
        return true;
    }

    function validateAllDates() {
        let isValid = true;
        document.querySelectorAll('.ecp-to').forEach(input => {
            if (!validateEcpRowDates(input)) isValid = false;
        });
        document.querySelectorAll('.lic-expiry').forEach(input => {
            if (!validateLicRowDates(input)) isValid = false;
        });
        return isValid;
    }

    function validateWorkerCategories() {
        const checked = document.querySelectorAll('.worker-cat-check:checked').length;
        const err = document.getElementById('worker-cat-error');
        if (err) err.style.display = checked === 0 ? 'block' : 'none';
        return checked > 0;
    }

    function addEcpRow() {
        const tbody = document.getElementById('ecpTableBody');
        const row = tbody.querySelector('.ecp-row').cloneNode(true);
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.readOnly = false;
            input.disabled = false;
            input.style.display = '';
            input.classList.remove('is-invalid');
            input.required = getRadioValue('ecp_covered') === 'YES' && input.type !== 'hidden';
        });
        row.querySelectorAll('.delete-btn').forEach(btn => {
            btn.disabled = false;
            btn.style.display = 'inline-block';
        });
        row.querySelectorAll('.invalid-feedback').forEach(error => error.style.display = 'none');
        tbody.appendChild(row);
        updateSlNos('ecpTableBody');
    }

    function deleteEcpRow(btn) {
        if (btn.disabled) return;
        const rows = document.querySelectorAll('#ecpTableBody tr');
        if (rows.length > 1) btn.closest('tr').remove();
        updateSlNos('ecpTableBody');
    }

    function addLicenseRow() {
        const tbody = document.getElementById('licenseTableBody');
        const row = tbody.querySelector('.license-row').cloneNode(true);
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.readOnly = false;
            input.disabled = false;
            input.style.display = '';
            input.classList.remove('is-invalid');
        });
        row.querySelectorAll('.delete-btn').forEach(btn => {
            btn.disabled = false;
            btn.style.display = 'inline-block';
        });
        row.querySelectorAll('.invalid-feedback').forEach(error => error.style.display = 'none');
        row.querySelectorAll('a').forEach(a => a.remove());
        tbody.appendChild(row);
        updateSlNos('licenseTableBody');
        toggleLicenceMandatory();
    }

    function deleteLicenseRow(btn) {
        if (btn.disabled) return;
        const rows = document.querySelectorAll('#licenseTableBody tr');
        if (rows.length > 1) btn.closest('tr').remove();
        updateSlNos('licenseTableBody');
    }

    function updateFileName(input, key) {
        const nameSpan = document.getElementById(`name_${key}`);
        if (input.files && input.files[0]) {
            nameSpan.innerText = input.files[0].name;
            nameSpan.style.color = '#1e3a8a';
        } else {
            nameSpan.innerText = 'No file selected';
            nameSpan.style.color = '#94a3b8';
        }
    }

    function normalizeDateForSubmit(input) {
        if (!input) return '';
        let value = String(input.value || '').trim();
        if (/^\d{4}$/.test(value)) {
            value = `${value}-12-31`;
        } else if (/^\d{4}\/\d{2}\/\d{2}$/.test(value)) {
            value = value.replace(/\//g, '-');
        }
        input.value = value;
        return value;
    }

    function updateWorkerTotal() {
        return parseInt(document.querySelector('[name="workers_proposed_to_be_engaged"]')?.value || '0', 10) || 0;
    }

    const LICENCE_THRESHOLD = <?= $licence_threshold ?>;

    function toggleLicenceMandatory() {
        const workers = updateWorkerTotal();
        const badge = document.getElementById('licenceMandatoryBadge');
        const card = document.getElementById('section7Card');
        const licInputs = document.querySelectorAll('#licenseTableBody input[type="text"], #licenseTableBody input[type="date"]');
        const fileInputs = document.querySelectorAll('#licenseTableBody input[type="file"]');

        if (badge) badge.style.display = 'none';
        if (card) card.style.borderColor = '';
        licInputs.forEach(input => input.required = false);
        fileInputs.forEach(input => input.required = false);
    }

    async function viewSubmission(id) {
        const modalEl = document.getElementById('viewModal');
        const modal = new bootstrap.Modal(modalEl);
        document.getElementById('viewModalContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>';
        modal.show();
        
        try {
            const resp = await fetch(`../../api/contractor/get_annexure3a_details.php?id=${id}`);
            const data = await resp.json();
            if(data.success) {
                let html = `<div class="p-3">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3">Statutory Status</h6>
                            <p class="mb-2"><strong>EPF:</strong> ${data.data.is_epf_registered == 1 ? '<span class="text-success fw-bold">'+data.data.epf_code+'</span>' : '<span class="text-muted">Not Registered</span>'}</p>
                            <p class="mb-2"><strong>ESI:</strong> ${data.data.is_esi_registered == 1 ? '<span class="text-info fw-bold">'+data.data.esi_code+'</span>' : '<span class="text-muted">Not Registered</span>'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3">Insurance Details</h6>
                            <p class="mb-2"><strong>Policy:</strong> ${data.data.insurance_policy_no || 'N/A'}</p>
                            <p class="mb-2"><strong>Valid Till:</strong> ${data.data.insurance_validity || 'N/A'}</p>
                        </div>
                        <div class="col-md-12">
                            <h6 class="text-muted small text-uppercase fw-bold mb-2">Wage Declaration</h6>
                            <div class="p-3 bg-light rounded border shadow-sm" style="font-size: 0.9rem; line-height: 1.6;">${data.data.wage_declaration}</div>
                        </div>
                        <div class="col-md-12">
                            <h6 class="text-muted small text-uppercase fw-bold mb-3">Compliance Documents</h6>
                            <div class="row g-2">`;
                
                if (data.documents && data.documents.length > 0) {
                    data.documents.forEach(doc => {
                        html += `<div class="col-md-4">
                            <a href="../../${doc.file_path}" target="_blank" class="d-flex align-items-center p-2 border rounded text-decoration-none bg-white hover-shadow">
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <span class="small text-dark text-truncate">${doc.doc_type.replace(/_/g, ' ').toUpperCase()}</span>
                            </a>
                        </div>`;
                    });
                } else {
                    html += `<div class="col-12 text-muted italic">No documents uploaded.</div>`;
                }

                html += `</div></div></div></div>`;
                document.getElementById('viewModalContent').innerHTML = html;
            }
        } catch(e) {
            document.getElementById('viewModalContent').innerHTML = '<div class="alert alert-danger m-3">Error fetching details. Please try again.</div>';
        }
    }

    // Form Submission
    document.getElementById('annexure3AForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Manual Validation for Required Fields (since novalidate is on)
        const form = e.target;
        const isDateValid = validateAllDates();
        const isWorkerCatValid = ANNEXURE3A_LIMITED_EDIT || validateWorkerCategories();
        toggleLicenceMandatory();
        if ((!ANNEXURE3A_LIMITED_EDIT && !form.checkValidity()) || !isDateValid || !isWorkerCatValid || !validateEPFESI()) {
            const invalidEl = form.querySelector(':invalid');
            if (invalidEl) {
                const pane = invalidEl.closest('.tab-pane');
                if (pane) {
                    showTab(pane.id);
                    setTimeout(() => invalidEl.focus(), 100);
                }
                alert('Please fill all required fields: ' + (invalidEl.placeholder || invalidEl.name || 'Check form'));
                return;
            }
            alert('Please correct highlighted fields before submitting.');
            showTab('statutoryDetails');
            return;
        }

        const btn = document.getElementById('submitBtn');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> SUBMITTING...';

        syncReasonSummary();
        syncLicenseIssuedFields();
        const formData = new FormData(e.target);
        formData.append('action', ANNEXURE3A_LIMITED_EDIT ? 'resubmit' : 'submit');
        formData.set('total_workers', String(updateWorkerTotal()));
        
        try {
            const resp = await fetch('../../api/contractor/save_annexure3a.php', {
                method: 'POST',
                body: formData
            });
            const raw = await resp.text();
            let res;
            try {
                res = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error(raw ? raw.replace(/<[^>]*>/g, ' ').trim().slice(0, 300) : 'Server returned an empty response.');
            }
            
            if (res.success) {
                alert('Customer Registration & Statutory documents submitted successfully!');
                window.location.reload(); 
            } else {
                alert(res.message || 'Submission failed. Please check all fields.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        } catch(err) {
            alert(err.message || 'Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });

    async function showAnnexure3AFeedback(message, type = 'info', title = '') {
        if (typeof window.notifyUser === 'function') {
            return window.notifyUser(message, type, title);
        }
        alert(message);
        return Promise.resolve();
    }

    async function saveDraft() {
        syncReasonSummary();
        syncLicenseIssuedFields();
        const formData = new FormData(document.getElementById('annexure3AForm'));
        formData.append('action', 'draft');
        formData.set('total_workers', String(updateWorkerTotal()));
        const btn = document.getElementById('saveDraftBtn');
        const originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
        }
        try {
            const resp = await fetch('../../api/contractor/save_annexure3a.php', {
                method: 'POST',
                body: formData
            });
            const raw = await resp.text();
            let res;
            try {
                res = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error(raw ? raw.replace(/<[^>]*>/g, ' ').trim().slice(0, 300) : 'Server returned an empty response.');
            }
            await showAnnexure3AFeedback(res.message || 'Draft saved successfully.', res.success ? 'success' : 'error', res.success ? 'Draft saved' : 'Draft save failed');
            if (res.success) {
                window.setTimeout(() => window.location.reload(), 700);
            }
        } catch (err) {
            await showAnnexure3AFeedback(err.message || 'Network error. Please try again.', 'error', 'Draft save failed');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    }

    window.addEventListener('load', () => {
        toggleEPF();
        toggleESI();
        toggleEcpPolicy();
        updateSlNos('ecpTableBody');
        updateSlNos('licenseTableBody');
        toggleLicenceMandatory();
    });
    document.querySelectorAll('.worker-count').forEach(input => input.addEventListener('input', toggleLicenceMandatory));
    document.querySelectorAll('.worker-cat-check').forEach(input => input.addEventListener('change', validateWorkerCategories));
    document.querySelectorAll('input[name="epf_registered"]').forEach(input => input.addEventListener('change', toggleEPF));
    document.querySelectorAll('input[name="esi_registered"]').forEach(input => input.addEventListener('change', toggleESI));
    document.querySelectorAll('input[name="ecp_covered"]').forEach(input => input.addEventListener('change', toggleEcpPolicy));
</script>
<?php
}
renderLayout('Customer Registration & Compliance', 'renderContent', $role, $name);
?>
