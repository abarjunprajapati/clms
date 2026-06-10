<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
require_once '../../include/labour_license_threshold.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
$vendor_code = $_SESSION['contractor_id'] ?? '';

function renderContent() {
    global $conn, $user_id, $vendor_code;

    $vendor_code = $_SESSION['contractor_id'] ?? $_SESSION['customer_code'] ?? '';

    // Fetch existing registration data by vendor_code
    $c = db_single($conn, "SELECT * FROM contractors WHERE vendor_code = ?", 's', [$vendor_code]);
    $status = strtolower($c['status'] ?? 'new');
    $annexure2a_status = '';
    if (!empty($c['id'])) {
        $annexure2a_row = db_single($conn, "SELECT workflow_status FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1", 'i', [(int)$c['id']]);
        $annexure2a_status = strtolower($annexure2a_row['workflow_status'] ?? '');
    }
    $display_status = ($status === 'pending' && $annexure2a_status === 'resubmitted') ? 'resubmitted' : $status;
    $is_resubmit_mode = (($_GET['resubmit'] ?? '') === '1');
    // After submission, only EC Policy and Labour License rows remain editable.
    $is_readonly = in_array($status, ['pending', 'submitted', 'resubmitted', 'under_review', 'hold'], true);
    $is_approved_limited_edit = $status === 'approved';
    $is_limited_update_mode = $is_readonly || $is_approved_limited_edit;
    $is_approved_view_only = false;
    
    // Parse worker categories
    $worker_cats = !empty($c['worker_category']) ? explode(',', $c['worker_category']) : [];
    $readonly_attr = ($is_readonly || $is_approved_limited_edit || $is_approved_view_only) ? 'readonly' : '';
    $disabled_attr = ($is_readonly || $is_approved_limited_edit || $is_approved_view_only) ? 'disabled' : '';
    $limited_edit_readonly_attr = $is_approved_view_only ? 'readonly' : '';
    $limited_edit_disabled_attr = $is_approved_view_only ? 'disabled' : '';
    $saved_limited_row_readonly_attr = $is_limited_update_mode ? 'readonly' : $limited_edit_readonly_attr;
    $saved_limited_file_disabled_attr = $is_limited_update_mode ? 'disabled' : $limited_edit_disabled_attr;
    $saved_limited_action_disabled_attr = $is_limited_update_mode ? 'disabled' : $limited_edit_disabled_attr;
    $ecp_choice_disabled_attr = $is_limited_update_mode ? 'disabled' : $limited_edit_disabled_attr;
    $submit_disabled_attr = $is_approved_view_only ? 'disabled' : '';
    $draft_disabled_attr = ($is_readonly || $is_approved_view_only) ? 'disabled' : '';
    $selected_ecp_covered = $c['ecp_covered'] ?? 'YES';

    $ecp_rows = [];
    if (!empty($c['ecp_details_json'])) {
        $decoded = json_decode($c['ecp_details_json'], true);
        if (is_array($decoded)) $ecp_rows = $decoded;
    }
    if (empty($ecp_rows) && !empty($c['ecp_number'])) {
        $ecp_rows[] = [
            'ecp_number' => $c['ecp_number'] ?? '',
            'ecp_valid_from' => $c['ecp_valid_from'] ?? '',
            'ecp_valid_to' => $c['ecp_valid_to'] ?? '',
            'workers_under_policy' => $c['workers_ecp'] ?? ''
        ];
    }
    if (empty($ecp_rows)) $ecp_rows[] = ['ecp_number' => '', 'ecp_valid_from' => '', 'ecp_valid_to' => '', 'workers_under_policy' => ''];

    $license_rows = [];
    if (!empty($c['license_details_json'])) {
        $decoded = json_decode($c['license_details_json'], true);
        if (is_array($decoded)) $license_rows = $decoded;
    }
    if (empty($license_rows) && (!empty($c['license_no']) || !empty($c['license_file']))) {
        $license_rows[] = [
            'license_no' => $c['license_no'] ?? '',
            'validity' => $c['license_issued'] ?? '',
            'license_issued' => $c['license_issued'] ?? '',
            'issued_date' => $c['issued_date'] ?? '',
            'expiry_date' => $c['expiry_date'] ?? '',
            'file_path' => $c['license_file'] ?? ''
        ];
    }
    if (empty($license_rows)) $license_rows[] = ['license_no' => '', 'validity' => '', 'license_issued' => '', 'issued_date' => '', 'expiry_date' => '', 'file_path' => ''];

    $stored_reason = $c['epf_esi_exemption_reason'] ?? '';
    $reason_value = function($label) use ($stored_reason) {
        if (preg_match('/' . preg_quote($label, '/') . ':\s*(.*?)(?=\n[A-Z][A-Za-z ]+ Reason:|$)/s', $stored_reason, $m)) {
            return trim($m[1]);
        }
        return $stored_reason;
    };
    $epf_reason = $reason_value('EPF Reason');
    $esi_reason = $reason_value('ESI Reason');
    $ecp_reason = $reason_value('EC Policy Reason');

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
    $epf_selected_yes = $yes_selected_by_default($c['epf_registered'] ?? 'YES', $epf_reason, $c['epf_code'] ?? '');
    $esi_selected_yes = $yes_selected_by_default($c['esi_registered'] ?? 'YES', $esi_reason, $c['esi_code'] ?? '');
    $ecp_selected_yes = $yes_selected_by_default($selected_ecp_covered, $ecp_reason, $c['ecp_details_json'] ?? ($c['ecp_number'] ?? ''));
    
    $sap_readonly = 'readonly style="background-color:#f1f5f9;"';

    $licence_threshold = clms_get_labour_license_threshold($conn);
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
            color: var(--primary-color); 
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

        #registrationDetails .registration-card {
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 18px;
            box-shadow: none;
        }
        #registrationDetails .registration-section-header {
            background: #eaf3ff;
            border-left: 4px solid #2b6cb0;
            padding: 10px 14px;
            font-weight: 600;
            color: #1e3a5f;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        #registrationDetails .registration-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 22px;
        }
        #registrationDetails .registration-grid .span-2 { grid-column: 1 / -1; }
        #registrationDetails .form-control,
        #registrationDetails .form-select {
            min-height: 42px;
            border-radius: 8px;
            border: 1px solid #cfd8e3;
            padding: 9px 12px;
        }
        #registrationDetails textarea.form-control { min-height: 100px; }
        .gov-radio-group { display:flex; flex-wrap:wrap; gap:18px; align-items:center; min-height:42px; }
        .gov-table { border: 1px solid #cfd8e3; margin-bottom: 0; }
        .gov-table th {
            background: #f1f6fd;
            color: #1e3a5f;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #cfd8e3;
            white-space: nowrap;
        }
        .gov-table td { border: 1px solid #dbe3ef; vertical-align: middle; }
        .gov-table .form-control { min-width: 140px; }
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
            #registrationDetails .registration-grid { grid-template-columns: 1fr; }
            #registrationDetails .registration-grid .span-2 { grid-column: auto; }
            .registration-actions { flex-direction: column; }
            .registration-actions .btn { width: 100%; }
        }
    </style>

<div class="container-fluid py-4 px-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-extrabold mb-1" style="font-weight: 800; color: #1e293b;">Contractor Registration</h2>
        </div>
        <?php if ($c): ?>
            <div class="text-end">
                <span class="badge rounded-pill bg-<?= $display_status==='approved' ? 'success' : ($display_status==='rejected' ? 'danger' : 'primary') ?> px-4 py-2 shadow-sm" style="font-size: 12px;">
                    <i class="fas fa-circle-notch fa-spin me-2" style="display: <?= in_array($display_status, ['pending', 'resubmitted'], true) ? 'inline-block' : 'none' ?>;"></i>
                    <?= strtoupper(str_replace('_', ' ', $display_status)) ?>
                </span>
                <a href="welfare-actions.php" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-clock-rotate-left me-1"></i> Welfare Action History
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (in_array($display_status, ['rejected', 'correction_required', 'hold'], true)): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="background:#fff7ed; color:#9a3412;">
            <div class="d-flex justify-content-between gap-3 flex-wrap align-items-center">
                <div>
                    <i class="fas fa-circle-exclamation me-2"></i>
                    Welfare action recorded. Please open history to view rejection/correction reason, date and attachment.
                </div>
                <a href="welfare-actions.php" class="btn btn-sm btn-warning fw-bold">
                    View History
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($is_readonly): ?>
        <div class="alert alert-info border-0 shadow-sm mb-4" style="background:#e0f2fe; color:#075985;">
            <i class="fas fa-lock me-2"></i>
            This registration is submitted. Only Employee Compensation Policy and Labour License Details are editable for resubmission.
        </div>
    <?php elseif ($is_approved_view_only): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4" style="background:#dcfce7; color:#166534;">
            <i class="fas fa-check-circle me-2"></i>
            This approved registration is read-only. Use Resubmit EC / Labour License from dashboard to update those sections.
        </div>
    <?php elseif ($is_approved_limited_edit): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4" style="background:#fef3c7; color:#92400e;">
            <i class="fas fa-edit me-2"></i>
            Resubmit mode: only Employee Compensation Policy and Labour License Details are editable.
        </div>
    <?php endif; ?>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="contractorTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="basic-tab" data-bs-toggle="tab" href="#basicDetails" role="tab">
                <i class="fas fa-id-card me-2"></i> 1. Basic Details
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="registration-tab" data-bs-toggle="tab" href="#registrationDetails" role="tab">
                <i class="fas fa-file-signature me-2"></i> 2. Registration
            </a>
        </li>
    </ul>

    <form id="annexure2AForm" enctype="multipart/form-data" novalidate class="needs-validation">
        <div class="tab-content" id="contractorTabsContent">

            <!-- ================= BASIC DETAILS TAB ================= -->
            <div class="tab-pane fade show active" id="basicDetails" role="tabpanel">
                
                <!-- Contractor Details Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-tie fa-sm"></i>
                            </div>
                            <h5 class="mb-0 text-primary">Contractor Details</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Contractor Code (Vendor)</label>
                                <input type="text" class="form-control" name="vendor_code" value="<?= htmlspecialchars($c['vendor_code'] ?? $vendor_code) ?>" <?= $sap_readonly ?> required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Vendor / Supplier Name</label>
                                <input type="text" class="form-control" name="vendor_name" value="<?= htmlspecialchars($c['vendor_name'] ?? '') ?>" <?= $sap_readonly ?> required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Contact Person Name</label>
                                <input type="text" class="form-control" name="contact_person_legacy" value="<?= htmlspecialchars($c['contact_person'] ?? '') ?>" <?= $sap_readonly ?>>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label required">Mobile No 1</label>
                                <input type="text" class="form-control" name="mobile_legacy" value="<?= htmlspecialchars($c['mobile'] ?? '') ?>" <?= $sap_readonly ?> required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Mobile No 2</label>
                                <input type="text" class="form-control" name="vendor_mob2" value="<?= htmlspecialchars($c['vendor_mob2'] ?? '') ?>" <?= $sap_readonly ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($c['email'] ?? '') ?>" <?= $sap_readonly ?> required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Registered Office Address</label>
                                <textarea class="form-control" name="address" rows="2" <?= $sap_readonly ?> required><?= htmlspecialchars($c['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PO Details Card (SAP) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-success text-white rounded-circle p-2 me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shopping-cart fa-sm"></i>
                            </div>
                            <h5 class="mb-0 text-success">Purchase Order Details</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="po-loading" class="text-center py-5" style="display:none;">
                            <div class="spinner-border text-success" role="status"></div>
                            <p class="mt-2 text-muted fw-bold">Connecting to SAP S/4 HANA...</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="poTable">
                                <thead>
                                    <tr>
                                        <th class="ps-4"><input type="checkbox" id="selectAllPO" class="form-check-input" <?= $disabled_attr ?>></th>
                                        <th>PO Number</th>
                                        <th>Type</th>
                                        <th>Purch. Group</th>
                                        <th>Description</th>
                                        <th>Currency</th>
                                        <th>Total Value</th>
                                        <th>Doc Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="poTableBody">
                                    <tr><td colspan="9" class="text-center py-4 text-muted">No PO records found for this vendor code.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" name="selected_pos" id="selected_pos">
                    </div>
                </div>

                <!-- PWO & Sales Orders -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 text-info" style="font-size: 0.9rem;"><i class="fas fa-ship me-2"></i> PWO Details</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 300px;">
                                    <table class="table table-sm table-hover mb-0" id="pwoTable">
                                        <thead><tr><th class="ps-3"><input type="checkbox" id="selectAllPWO" class="form-check-input" <?= $disabled_attr ?>></th><th>PWO No</th><th>Vessel</th><th>Completion</th></tr></thead>
                                        <tbody id="pwoTableBody"><tr><td colspan="4" class="text-center py-3 text-muted">No records.</td></tr></tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="selected_pwos" id="selected_pwos">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 text-warning" style="font-size: 0.9rem;"><i class="fas fa-file-invoice-dollar me-2"></i> Sales Order</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 300px;">
                                    <table class="table table-sm table-hover mb-0" id="soTable">
                                        <thead><tr><th class="ps-3"><input type="checkbox" id="selectAllSO" class="form-check-input" <?= $disabled_attr ?>></th><th>Sales Doc</th><th>Amount</th><th>Curr</th></tr></thead>
                                        <tbody id="soTableBody"><tr><td colspan="4" class="text-center py-3 text-muted">No records.</td></tr></tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="selected_sales" id="selected_sales">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <button type="button" class="btn btn-primary px-5 py-3 shadow-sm fw-bold" onclick="showTab('registrationDetails')">
                        NEXT: REGISTRATION <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- ================= REGISTRATION TAB ================= -->
            <div class="tab-pane fade" id="registrationDetails" role="tabpanel">
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
                                    <option value="<?= htmlspecialchars($d['dept_name']) ?>" <?= ($c['work_awarding_department'] ?? '') === $d['dept_name'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
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
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="epf_registered" id="epf_yes" value="YES" <?= $epf_selected_yes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                    <label class="form-check-label" for="epf_yes">YES</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="epf_registered" id="epf_no" value="NO" <?= !$epf_selected_yes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                    <label class="form-check-label" for="epf_no">NO</label>
                                </div>
                            </div>
                        </div>
                        <div id="epfDetailsCard">
                            <label class="form-label required">EPF Establishment Code</label>
                            <input type="text" class="form-control" name="epf_code" id="epf_code" value="<?= htmlspecialchars($c['epf_code'] ?? '') ?>" <?= $readonly_attr ?>>
                            <input type="hidden" name="epf_account_no" id="epf_account_no" value="<?= htmlspecialchars($c['epf_account_no'] ?? '') ?>">
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
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="esi_registered" id="esi_yes" value="YES" <?= $esi_selected_yes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                    <label class="form-check-label" for="esi_yes">YES</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="esi_registered" id="esi_no" value="NO" <?= !$esi_selected_yes ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                                    <label class="form-check-label" for="esi_no">NO</label>
                                </div>
                            </div>
                        </div>
                        <div id="esi_code_container">
                            <label class="form-label required">ESI Establishment Code</label>
                            <input type="text" class="form-control" name="esi_code" id="esi_code" value="<?= htmlspecialchars($c['esi_code'] ?? '') ?>" <?= $readonly_attr ?>>
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
                    <div class="registration-section-header">5. Wage Declaration by Contractor</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="wage_declaration" id="wage_declaration" value="I declare to pay minimum wage as per government norms" <?= !empty($c['wage_declaration']) ? 'checked' : '' ?> required <?= $disabled_attr ?>>
                        <label class="form-check-label fw-semibold" for="wage_declaration">With this I declare to pay minimum wage as per government norms.</label>
                    </div>
                    <input type="hidden" name="wage_category" value="<?= htmlspecialchars($c['wage_category'] ?? '') ?>">
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
                                        <td><input type="number" class="form-control" name="ecp_workers[]" min="0" value="<?= htmlspecialchars($row['workers_under_policy'] ?? $row['workers_ecp'] ?? $row['insurance_company'] ?? '') ?>" <?= $saved_limited_row_readonly_attr ?>></td>
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
                </div>

                <div class="registration-card">
                    <div class="registration-section-header">8. Approximate Workforce Details</div>
                    <div class="registration-grid">
                        <div>
                            <label class="form-label required">No. of Workers Proposed to be Engaged</label>
                            <input type="number" class="form-control" name="workers_proposed_to_be_engaged" value="<?= htmlspecialchars($c['workers_proposed_to_be_engaged'] ?? '') ?>" min="0" required <?= $readonly_attr ?>>
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
                        <span id="licenceMandatoryBadge" class="badge bg-warning text-dark" style="display:none;">Mandatory (Workers &gt;= <?= $licence_threshold ?>)</span>
                    </div>
                    <div class="d-flex justify-content-end mb-3"><button type="button" class="btn btn-sm btn-reg-draft" onclick="addLicenseRow()" <?= $limited_edit_disabled_attr ?>>Add Row</button></div>
                    <div class="table-responsive">
                        <table class="table gov-table align-middle" id="licenseTable">
                            <thead>
                                <tr><th>S.No</th><th>Labour No</th><th>Issued By</th><th>Issued Date</th><th>Expiry Date</th><th>License Upload</th><th>Action</th></tr>
                            </thead>
                            <tbody id="licenseTableBody">
                                <?php foreach ($license_rows as $i => $row): $file_path = $row['file_path'] ?? ($row['license_file'] ?? ''); ?>
                                    <tr class="license-row">
                                        <td class="sl-no text-center fw-bold"><?= $i + 1 ?></td>
                                        <td><input type="text" class="form-control" name="license_no[]" value="<?= htmlspecialchars($row['license_no'] ?? '') ?>" <?= $saved_limited_row_readonly_attr ?>></td>
                                        <td><input type="text" class="form-control" name="license_validity[]" value="<?= htmlspecialchars($row['validity'] ?? ($row['license_issued'] ?? '')) ?>" <?= $saved_limited_row_readonly_attr ?>><input type="hidden" name="license_issued[]" value="<?= htmlspecialchars($row['license_issued'] ?? ($row['validity'] ?? '')) ?>"></td>
                                        <td><input type="date" class="form-control lic-issued" name="issued_date[]" value="<?= htmlspecialchars($row['issued_date'] ?? '') ?>" onchange="validateLicRowDates(this)" <?= $saved_limited_row_readonly_attr ?>></td>
                                        <td><input type="date" class="form-control lic-expiry" name="expiry_date[]" value="<?= htmlspecialchars($row['expiry_date'] ?? '') ?>" onchange="validateLicRowDates(this)" <?= $saved_limited_row_readonly_attr ?>><div class="invalid-feedback lic-date-error">Issued Date must be before Expiry Date.</div></td>
                                        <td>
                                            <input type="file" class="form-control" name="license_file[]" accept="application/pdf,.pdf" <?= $saved_limited_file_disabled_attr ?> <?= $saved_limited_file_disabled_attr ? 'style="display:none;"' : '' ?>>
                                            <input type="hidden" name="existing_license_file[]" value="<?= htmlspecialchars($file_path) ?>">
                                            <?php if (!empty($file_path)): ?><a href="../../uploads/contractors/<?= htmlspecialchars($file_path) ?>" target="_blank" class="d-block mt-1 text-success fw-bold" style="font-size:12px;">Uploaded File</a><?php endif; ?>
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
                            <input type="text" class="form-control" name="labour_license_appl_no" value="<?= htmlspecialchars($c['labour_license_appl_no'] ?? '') ?>" <?= $readonly_attr ?>>
                        </div>
                        <div class="col-md-6">
                            <div class="registration-section-header">11. Labour Identification Number</div>
                            <input type="text" class="form-control" name="labour_identification_no" id="labour_identification_no" pattern="^[0-9]+$" value="<?= htmlspecialchars($c['labour_identification_no'] ?? '') ?>" placeholder="Numeric digits only" <?= $readonly_attr ?>>
                            <div class="invalid-feedback">LIN number must be numeric only.</div>
                        </div>
                    </div>
                </div>
                <div class="registration-card"><div class="registration-section-header">12. Name of Contact Person</div><input type="text" class="form-control" name="contact_person" id="contact_person" pattern="^[a-zA-Z\s]+$" value="<?= htmlspecialchars($c['contact_person'] ?? '') ?>" required placeholder="Alphabets only" <?= $readonly_attr ?>><div class="invalid-feedback">Contact person must be letters only.</div></div>
                <div class="registration-card">
                    <div class="registration-section-header">13. Mobile Number </div>
                    <div class="registration-grid">
                        <div><label class="form-label required">Mobile Number 1</label><input type="text" class="form-control" name="mobile" pattern="^[0-9]{10}$" value="<?= htmlspecialchars($c['mobile'] ?? '') ?>" required <?= $readonly_attr ?>></div>
                        <div><label class="form-label">Mobile Number 2</label><input type="text" class="form-control" name="vendor_mob2" pattern="^[0-9]{10}$" value="<?= htmlspecialchars($c['vendor_mob2'] ?? '') ?>" <?= $readonly_attr ?>></div>
                    </div>
                </div>
                <div class="registration-card"><div class="registration-section-header">14. Remarks</div><textarea class="form-control" name="remarks" placeholder="Enter remarks" <?= $readonly_attr ?>><?= htmlspecialchars($c['remarks'] ?? '') ?></textarea></div>

                <div class="registration-actions">
                    <button type="button" class="btn btn-reg-prev px-4" onclick="showTab('basicDetails')">Previous</button>
                    <button type="button" class="btn btn-reg-draft px-4" onclick="saveDraft()" <?= $draft_disabled_attr ?>>Save Draft</button>
                    <button type="submit" class="btn btn-reg-submit px-4" id="submitBtn" <?= $submit_disabled_attr ?>><?= $is_limited_update_mode ? 'Resubmit for Welfare Approval' : 'Submit Registration' ?></button>
                </div>

                <?php if (false): ?>
                
                <!-- Card 1: EPF, ESI & Wage Category (Fields 1 to 6) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-building me-2"></i> I. Department & Statutory Registration</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">1. Work Awarding Department</label>
                                <select class="form-select" name="work_awarding_department" required>
                                    <option value="">-- Select Department --</option>
                                    <?php
                                    $depts = db_fetch_all($conn, "SELECT dept_name FROM master_departments WHERE status='active' ORDER BY dept_name ASC");
                                    foreach($depts as $d): ?>
                                        <option value="<?= htmlspecialchars($d['dept_name']) ?>" <?= ($c['work_awarding_department'] ?? '') === $d['dept_name'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label required">2. Whether registered under EPF</label>
                                <select class="form-select" name="epf_registered" id="epf_registered" onchange="toggleEPF()" required>
                                    <option value="YES" <?= $epf_selected_yes ? 'selected' : '' ?>>YES</option>
                                    <option value="NO" <?= !$epf_selected_yes ? 'selected' : '' ?>>NO</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3" id="epf_code_container">
                                <label class="form-label required" id="epf_code_label">3. EPF Establishment code</label>
                                <input type="text" class="form-control" name="epf_code" id="epf_code" value="<?= htmlspecialchars($c['epf_code'] ?? '') ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label required">4. Whether registered under ESI</label>
                                <select class="form-select" name="esi_registered" id="esi_registered" onchange="toggleESI()" required>
                                    <option value="YES" <?= $esi_selected_yes ? 'selected' : '' ?>>YES</option>
                                    <option value="NO" <?= !$esi_selected_yes ? 'selected' : '' ?>>NO</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3" id="esi_code_container">
                                <label class="form-label required" id="esi_code_label">5. ESI Establishment code</label>
                                <input type="text" class="form-control" name="esi_code" id="esi_code" value="<?= htmlspecialchars($c['esi_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">6. Wage category for contractor</label>
                                <select class="form-select" name="wage_category" required>
                                    <option value="">-- Select Wage Category --</option>
                                    <option value="Skilled" <?= ($c['wage_category'] ?? '') === 'Skilled' ? 'selected' : '' ?>>Skilled</option>
                                    <option value="Semiskilled" <?= ($c['wage_category'] ?? '') === 'Semiskilled' ? 'selected' : '' ?>>Semiskilled</option>
                                    <option value="Unskilled" <?= ($c['wage_category'] ?? '') === 'Unskilled' ? 'selected' : '' ?>>Unskilled</option>
                                </select>
                            </div>
                            <div class="col-12 mt-2 d-none" id="epf-esi-validation-error">
                                <div class="alert alert-danger py-2 px-3 m-0" style="font-size: 13px; font-weight: 600;">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Both EPF and ESI cannot be 'NO'. At least one must be registered as 'YES'.
                                </div>
                            </div>
                            <div class="col-12 mt-2 d-none" id="epf-esi-reason-container">
                                <label class="form-label required">Reason for Non-Registration (EPF/ESI)</label>
                                <textarea class="form-control" name="epf_esi_exemption_reason" id="epf_esi_exemption_reason" rows="2" placeholder="Please specify the reason for not registering under EPF or ESI..."><?= htmlspecialchars($c['epf_esi_exemption_reason'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 5: Employee Compensation Policy -->
                <div class="card shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: #334155; font-size: 1.1rem;">5. Employee Compensation Policy</h5>
                        <button type="button" class="btn btn-sm rounded-pill fw-bold" style="background-color: #f1f5f9; color: #3b82f6; border: 1px solid #cbd5e1; padding: 6px 16px; transition: all 0.2s;" onclick="addEcpRow()" onmouseover="this.style.backgroundColor='#e2e8f0'" onmouseout="this.style.backgroundColor='#f1f5f9'">
                            + Add
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0" id="ecpTable">
                                <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th class="ps-4 text-secondary" style="font-size: 0.75rem; font-weight: 700; width: 70px;">SL NO</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">EC Policy No</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">EC Validity From</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">EC Validity To</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">No of Workers</th>
                                        <th class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="ecpTableBody">
                                    <tr class="ecp-row" style="transition: all 0.3s ease;">
                                        <td class="ps-4 fw-bold text-muted sl-no">1</td>
                                        <td><input type="text" class="form-control form-control-sm" name="ecp_number[]" value="<?= htmlspecialchars($c['ecp_number'] ?? '') ?>" required></td>
                                        <td><input type="date" class="form-control form-control-sm ecp-from" name="ecp_valid_from[]" value="<?= htmlspecialchars($c['ecp_valid_from'] ?? '') ?>" required onchange="validateEcpRowDates(this)"></td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm ecp-to" name="ecp_valid_to[]" value="<?= htmlspecialchars($c['ecp_valid_to'] ?? '') ?>" required onchange="validateEcpRowDates(this)">
                                            <div class="invalid-feedback ecp-date-error" style="font-size:10px;">"From" must be <= "To".</div>
                                        </td>
                                        <td><input type="number" class="form-control form-control-sm" name="workers_ecp[]" value="<?= htmlspecialchars($c['workers_ecp'] ?? '') ?>" required></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm text-danger delete-btn" onclick="deleteEcpRow(this)" style="display:none; padding: 2px 6px;"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-top" style="background-color: #f8fafc;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required">No. of workers proposed to be engaged</label>
                                    <input type="number" class="form-control" name="workers_proposed_to_be_engaged" value="<?= htmlspecialchars($c['workers_proposed_to_be_engaged'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Category of worker</label>
                                    <div class="d-flex gap-4 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input worker-cat-check" type="checkbox" name="worker_categories[]" value="Skilled" id="cat_skilled" <?= in_array('Skilled', $worker_cats) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-dark" for="cat_skilled">Skilled</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input worker-cat-check" type="checkbox" name="worker_categories[]" value="Semiskilled" id="cat_semiskilled" <?= in_array('Semiskilled', $worker_cats) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-dark" for="cat_semiskilled">Semiskilled</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input worker-cat-check" type="checkbox" name="worker_categories[]" value="Unskilled" id="cat_unskilled" <?= in_array('Unskilled', $worker_cats) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-dark" for="cat_unskilled">Unskilled</label>
                                        </div>
                                    </div>
                                    <div class="text-danger mt-1" id="worker-cat-error" style="display:none; font-size:12px; font-weight:600;">
                                        At least one worker category must be selected.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 7: Labour Licence Certificate -->
                <div class="card shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;" id="section7Card">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0 fw-bold" style="color: #334155; font-size: 1.1rem;">7. Labour Licence Certificate</h5>
                            <span id="licenceMandatoryBadge" class="badge" style="font-size:11px; display:none; background:#fef3c7; color:#92400e; border:1px solid #fcd34d; padding:4px 10px; border-radius:20px;">
                                <i class="fas fa-exclamation-circle me-1"></i> Mandatory (Workers &gt;= <?= $licence_threshold ?>)
                            </span>
                        </div>
                        <button type="button" class="btn btn-sm rounded-pill fw-bold" style="background-color: #f1f5f9; color: #3b82f6; border: 1px solid #cbd5e1; padding: 6px 16px; transition: all 0.2s;" onclick="addLicenseRow()" onmouseover="this.style.backgroundColor='#e2e8f0'" onmouseout="this.style.backgroundColor='#f1f5f9'">
                            + Add
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0" id="licenseTable">
                                <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th class="ps-4 text-secondary" style="font-size: 0.75rem; font-weight: 700; width: 70px;">SL NO</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">License No</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">Issued By</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">Issued On</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">Expiry Date</th>
                                        <th class="text-secondary" style="font-size: 0.75rem; font-weight: 700;">Certificate Upload</th>
                                        <th class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="licenseTableBody">
                                    <tr class="license-row" style="transition: all 0.3s ease;">
                                        <td class="ps-4 fw-bold text-muted sl-no">1</td>
                                        <td><input type="text" class="form-control form-control-sm" name="license_no[]" value="<?= htmlspecialchars($c['license_no'] ?? '') ?>" required></td>
                                        <td><input type="text" class="form-control form-control-sm" name="license_issued[]" value="<?= htmlspecialchars($c['license_issued'] ?? '') ?>" required></td>
                                        <td><input type="date" class="form-control form-control-sm lic-issued" name="issued_date[]" value="<?= htmlspecialchars($c['issued_date'] ?? '') ?>" required onchange="validateLicRowDates(this)"></td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm lic-expiry" name="expiry_date[]" value="<?= htmlspecialchars($c['expiry_date'] ?? '') ?>" required onchange="validateLicRowDates(this)">
                                            <div class="invalid-feedback lic-date-error" style="font-size:10px;">"Issued" must be <= "Expiry".</div>
                                        </td>
                                        <td>
                                            <input type="file" class="form-control form-control-sm" name="license_file[]" accept=".pdf" <?= empty($c['license_file']) ? 'required' : '' ?>>
                                            <?php if(!empty($c['license_file'])): ?>
                                                <a href="../../uploads/contractors/<?= htmlspecialchars($c['license_file']) ?>" target="_blank" class="d-block mt-1 text-decoration-none fw-bold text-success" style="font-size:11px;"><i class="fas fa-check-circle me-1"></i> Uploaded File</a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm text-danger delete-btn" onclick="deleteLicenseRow(this)" style="display:none; padding: 2px 6px;"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Welfare & Primary Contact Details (Fields 18 to 21) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-address-book me-2"></i> IV. Welfare & Contact Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">18. Kerala Labour Welfare Fund Registration Number</label>
                                <input type="text" class="form-control" name="klwf_registration_no" value="<?= htmlspecialchars($c['klwf_registration_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">19. Labour Identification No. <span class="text-muted" style="font-size:10px;text-transform:none;">(a unique number issued by Labour Department)</span></label>
                                <input type="text" class="form-control" name="labour_identification_no" id="labour_identification_no" pattern="^[0-9]+$" value="<?= htmlspecialchars($c['labour_identification_no'] ?? '') ?>" placeholder="Numeric digits only">
                                <div class="invalid-feedback">LIN number must be numeric only.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">20. Name of contact person</label>
                                <input type="text" class="form-control" name="contact_person" id="contact_person" pattern="^[a-zA-Z\s]+$" value="<?= htmlspecialchars($c['contact_person'] ?? '') ?>" required placeholder="Alphabets only">
                                <div class="invalid-feedback">Contact person must be letters only.</div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">21. Remarks</label>
                                <textarea class="form-control" name="remarks" rows="3" placeholder="Enter special remarks or notes here (Optional)"><?= htmlspecialchars($c['remarks'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky-bottom-bar d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary px-5 fw-bold rounded-pill" onclick="showTab('basicDetails')">PREVIOUS</button>
                    <button type="button" class="btn btn-outline-primary px-5 fw-bold rounded-pill" onclick="saveDraft()">SAVE DRAFT</button>
                    <button type="submit" class="btn btn-primary btn-submit px-5 shadow-lg rounded-pill" id="submitBtn">SUBMIT REGISTRATION</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const ANNEXURE_IS_READONLY = <?= $is_readonly ? 'true' : 'false' ?>;
    const ANNEXURE2A_LIMITED_EDIT = <?= $is_limited_update_mode ? 'true' : 'false' ?>;

    function showAnnexure2AFeedback(message, type = 'info', title = '') {
        if (typeof window.notifyUser === 'function') {
            return window.notifyUser(message, type, title);
        }
        alert((title ? title + ': ' : '') + message);
        return Promise.resolve();
    }

    function showTab(id) {
        const tabEl = document.querySelector(`a[href="#${id}"]`);
        if (!tabEl) return;
        const tab = new bootstrap.Tab(tabEl);
        tab.show();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateEPFESI(showPopup = false) {
        const esi = getRadioValue('esi_registered');
        const ecp = getRadioValue('ecp_covered');
        const warning = document.getElementById('esi-ec-warning');
        const invalid = esi === 'NO' && ecp !== 'YES';
        if (warning) warning.classList.toggle('d-none', !invalid);
        if (invalid && showPopup) {
            showAnnexure2AFeedback('Either ESI or EC Policy is mandatory', 'warning', 'Either ESI or EC Policy is mandatory');
        }
        return !invalid;
    }

    function toggleEPF() {
        const val = getRadioValue('epf_registered');
        const code = document.getElementById('epf_code');
        const card = document.getElementById('epfDetailsCard');
        const reasonCard = document.getElementById('epfReasonCard');
        const reasonInput = document.getElementById('epf_non_registration_reason');

        if (val === 'YES') {
            card.style.display = '';
            reasonCard.style.display = 'none';
            code.required = true;
            reasonInput.required = false;
        } else {
            card.style.display = 'none';
            reasonCard.style.display = '';
            code.required = false;
            reasonInput.required = false;
            code.value = '';
        }
        validateEPFESI(true);
    }

    function toggleESI() {
        const val = getRadioValue('esi_registered');
        const input = document.getElementById('esi_code');
        const container = document.getElementById('esi_code_container');
        const reasonContainer = document.getElementById('esi_reason_container');
        const reasonInput = document.getElementById('esi_non_registration_reason');

        if (val === 'YES') {
            container.style.display = 'block';
            reasonContainer.style.display = 'none';
            input.required = true;
            reasonInput.required = false;
        } else {
            container.style.display = 'none';
            reasonContainer.style.display = 'block';
            input.required = false;
            reasonInput.required = false;
            input.value = '';
        }
        validateEPFESI(true);
    }

    function getRadioValue(name) {
        return document.querySelector(`input[name="${name}"]:checked`)?.value || '';
    }

    function toggleEcpPolicy() {
        const val = getRadioValue('ecp_covered');
        const tableWrap = document.getElementById('ecpTableWrap');
        const reasonCard = document.getElementById('reasonCard');
        const addBtn = document.getElementById('addEcpBtn');
        const reasonInput = document.getElementById('ecp_exemption_reason');
        const ecpInputs = document.querySelectorAll('#ecpTableBody input');

        if (val === 'YES') {
            tableWrap.style.display = '';
            if (addBtn) addBtn.style.display = '';
            reasonCard.style.display = 'none';
            reasonInput.required = false;
            ecpInputs.forEach(input => input.required = input.type !== 'hidden');
        } else {
            tableWrap.style.display = 'none';
            if (addBtn) addBtn.style.display = 'none';
            reasonCard.style.display = '';
            reasonInput.required = false;
            ecpInputs.forEach(input => input.required = false);
        }
        validateEPFESI();
    }

    function validateEcpRowDates(input) {
        const row = input.closest('tr');
        const fromVal = row.querySelector('.ecp-from').value;
        const toVal = row.querySelector('.ecp-to').value;
        const err = row.querySelector('.ecp-date-error');
        
        if (fromVal && toVal) {
            if (new Date(fromVal) > new Date(toVal)) {
                row.querySelector('.ecp-to').classList.add('is-invalid');
                err.style.display = 'block';
                return false;
            }
        }
        row.querySelector('.ecp-to').classList.remove('is-invalid');
        err.style.display = 'none';
        return true;
    }

    function validateLicRowDates(input) {
        const row = input.closest('tr');
        const issue = row.querySelector('.lic-issued').value;
        const expiry = row.querySelector('.lic-expiry').value;
        const err = row.querySelector('.lic-date-error');
        
        if (issue && expiry) {
            if (new Date(issue) > new Date(expiry)) {
                row.querySelector('.lic-expiry').classList.add('is-invalid');
                err.style.display = 'block';
                return false;
            }
        }
        row.querySelector('.lic-expiry').classList.remove('is-invalid');
        err.style.display = 'none';
        return true;
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
            if(rows.length > 1) {
                deleteBtn.style.display = 'inline-block';
            } else {
                deleteBtn.style.display = 'none';
            }
        });
    }

    function addEcpRow() {
        if (ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) return;
        const tbody = document.getElementById('ecpTableBody');
        const row = tbody.querySelector('.ecp-row').cloneNode(true);
        // Clear values
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
        row.querySelector('.ecp-date-error').style.display = 'none';
        // Add animation class
        row.style.opacity = '0';
        row.style.transform = 'translateY(-10px)';
        tbody.appendChild(row);
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 10);
        updateSlNos('ecpTableBody');
    }

    function deleteEcpRow(btn) {
        if (ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) return;
        if (btn.disabled) return;
        const row = btn.closest('tr');
        row.style.opacity = '0';
        row.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            row.remove();
            updateSlNos('ecpTableBody');
        }, 300);
    }

    function addLicenseRow() {
        if (ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) return;
        const tbody = document.getElementById('licenseTableBody');
        const row = tbody.querySelector('.license-row').cloneNode(true);
        // Clear values and remove old anchor tags
        row.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.readOnly = false;
            input.disabled = false;
            input.style.display = '';
            input.classList.remove('is-invalid');
            if(input.type === 'file') {
                input.required = false;
            }
        });
        row.querySelectorAll('.delete-btn').forEach(btn => {
            btn.disabled = false;
            btn.style.display = 'inline-block';
        });
        const existingLink = row.querySelector('a');
        if(existingLink) existingLink.remove();
        
        row.querySelector('.lic-date-error').style.display = 'none';
        
        // Add animation class
        row.style.opacity = '0';
        row.style.transform = 'translateY(-10px)';
        tbody.appendChild(row);
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 10);
        updateSlNos('licenseTableBody');
    }

    function deleteLicenseRow(btn) {
        if (ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) return;
        if (btn.disabled) return;
        const row = btn.closest('tr');
        row.style.opacity = '0';
        row.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            row.remove();
            updateSlNos('licenseTableBody');
        }, 300);
    }

    function validateAllDates() {
        let isValid = true;
        document.querySelectorAll('.ecp-to').forEach(input => {
            if(!validateEcpRowDates(input)) isValid = false;
        });
        document.querySelectorAll('.lic-expiry').forEach(input => {
            if(!validateLicRowDates(input)) isValid = false;
        });
        return isValid;
    }

    function validateWorkerCategories() {
        const checked = document.querySelectorAll('.worker-cat-check:checked').length;
        const err = document.getElementById('worker-cat-error');
        if (checked === 0) {
            err.style.display = 'block';
            return false;
        }
        err.style.display = 'none';
        return true;
    }

    // SAP Fetch logic
    async function fetchSAPData() {
        const code = '<?= $vendor_code ?>';
        if (!code) return;
        
        document.getElementById('po-loading').style.display = 'block';
        
        try {
            // Fetch POs
            const poResp = await fetch(`../../api/contractor/get_vendor_pos.php?vendor_code=${code}`);
            const poData = await poResp.json();
            const poBody = document.getElementById('poTableBody');
            if (poData.status === 'success' && poData.data.length > 0) {
                poBody.innerHTML = poData.data.map(p => `
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="po-check form-check-input" value="${p.po_number}" ${ANNEXURE_IS_READONLY ? 'disabled' : ''}></td>
                        <td><span class="fw-bold text-dark">${p.po_number}</span></td>
                        <td><span class="badge bg-light text-dark border">${p.po_type}</span></td>
                        <td>${p.purchasing_group}</td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${p.header_text}</td>
                        <td>${p.currency}</td>
                        <td class="fw-bold">${p.total_value}</td>
                        <td>${p.document_date}</td>
                        <td><span class="badge ${p.release_status==='R'?'bg-success':'bg-warning'}">${p.release_status==='R'?'Released':'Pending'}</span></td>
                    </tr>
                `).join('');
            }

            // Fetch PWOs
            const pwoResp = await fetch(`../../api/contractor/get_vendor_pwos.php?vendor_code=${code}`);
            const pwoData = await pwoResp.json();
            const pwoBody = document.getElementById('pwoTableBody');
            if(pwoData.status === 'success' && pwoData.data.length > 0) {
                pwoBody.innerHTML = pwoData.data.map(p => `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="pwo-check form-check-input" value="${p.pwo_number}" ${ANNEXURE_IS_READONLY ? 'disabled' : ''}></td>
                        <td>${p.pwo_number}</td>
                        <td>${p.vessel}</td>
                        <td>${p.work_completion_date}</td>
                    </tr>
                `).join('');
            }

            // Fetch Sales Orders
            const soResp = await fetch(`../../api/contractor/get_vendor_sales.php?vendor_code=${code}`);
            const soData = await soResp.json();
            const soBody = document.getElementById('soTableBody');
            if(soData.status === 'success' && soData.data.length > 0) {
                soBody.innerHTML = soData.data.map(s => `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="so-check form-check-input" value="${s.sale_order_no}" ${ANNEXURE_IS_READONLY ? 'disabled' : ''}></td>
                        <td>${s.sale_order_no}</td>
                        <td class="fw-bold">${s.amount}</td>
                        <td>${s.currency}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error("SAP Fetch Error", e);
        } finally {
            document.getElementById('po-loading').style.display = 'none';
        }
    }

    function syncLicenseIssuedFields() {
        document.querySelectorAll('#licenseTableBody .license-row').forEach(row => {
            const validity = row.querySelector('input[name="license_validity[]"]')?.value || '';
            const issuedBy = row.querySelector('input[name="license_issued[]"]');
            if (issuedBy) issuedBy.value = validity;
        });
    }

    function collectData() {
        const pos = Array.from(document.querySelectorAll('.po-check:checked')).map(cb => cb.value);
        const pwos = Array.from(document.querySelectorAll('.pwo-check:checked')).map(cb => cb.value);
        const sos = Array.from(document.querySelectorAll('.so-check:checked')).map(cb => cb.value);
        document.getElementById('selected_pos').value = JSON.stringify(pos);
        document.getElementById('selected_pwos').value = JSON.stringify(pwos);
        document.getElementById('selected_sales').value = JSON.stringify(sos);
        syncLicenseIssuedFields();
    }

    document.getElementById('annexure2AForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) {
            showAnnexure2AFeedback('This registration is locked while it is pending Welfare action.', 'warning', 'Locked');
            return;
        }
        
        const form = e.target;
        const isDateValid = validateAllDates();
        const isWorkerCatValid = ANNEXURE2A_LIMITED_EDIT || validateWorkerCategories();
        const isEPFESIValid = validateEPFESI();

        if ((!ANNEXURE2A_LIMITED_EDIT && !form.checkValidity()) || !isDateValid || !isWorkerCatValid || !isEPFESIValid) {
            e.stopPropagation();
            form.classList.add('was-validated');
            const invalidField = form.querySelector('input:invalid, select:invalid, textarea:invalid');
            if (form.querySelector('#registrationDetails input:invalid, #registrationDetails select:invalid, #registrationDetails textarea:invalid') || !isDateValid || !isWorkerCatValid || !isEPFESIValid) {
                showTab('registrationDetails');
            } else {
                showTab('basicDetails');
            }
            setTimeout(() => invalidField?.focus({ preventScroll: false }), 250);
            showAnnexure2AFeedback('Please complete the highlighted mandatory fields before submitting.', 'warning', 'Validation required');
            return;
        }

        syncLicenseIssuedFields();
        if (!ANNEXURE2A_LIMITED_EDIT) collectData();
        const btn = e.submitter || form.querySelector('.btn-reg-submit[type="submit"]') || document.getElementById('submitBtn');
        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> PROCESSING...';
        }
        
        const formData = new FormData(form);
        formData.append('action', ANNEXURE2A_LIMITED_EDIT ? 'resubmit' : 'submit');

        try {
            const resp = await fetch('../../api/save_annexure2a.php', { method: 'POST', body: formData });
            const raw = await resp.text();
            let res;
            try {
                res = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error(raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Invalid server response');
            }
            if (res.success) {
                await showAnnexure2AFeedback('Your application is now under Welfare review.', 'success', 'Registration submitted successfully');
                window.location.href = 'dashboard.php';
            } else {
                await showAnnexure2AFeedback(res.message || res.error || 'Error submitting registration', 'error', 'Submission failed');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }
        } catch (err) {
            await showAnnexure2AFeedback(err.message || 'Please try again.', 'error', 'Submit failed');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    });

    async function saveDraft() {
        if (ANNEXURE_IS_READONLY) {
            showAnnexure2AFeedback('This registration is locked while it is pending Welfare action.', 'warning', 'Locked');
            return;
        }
        syncLicenseIssuedFields();
        if (!ANNEXURE2A_LIMITED_EDIT) collectData();
        const draftBtn = document.querySelector('button[onclick="saveDraft()"]');
        const originalText = draftBtn ? draftBtn.innerHTML : '';
        if (draftBtn) {
            draftBtn.disabled = true;
            draftBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
        }
        const formData = new FormData(document.getElementById('annexure2AForm'));
        formData.append('action', 'draft');
        try {
            const resp = await fetch('../../api/save_annexure2a.php', { method: 'POST', body: formData });
            const raw = await resp.text();
            let res;
            try {
                res = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error(raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Invalid server response');
            }
            await showAnnexure2AFeedback(res.message || res.error || 'Draft saved successfully.', res.success ? 'success' : 'error', res.success ? 'Draft saved' : 'Draft save failed');
        } catch (err) {
            await showAnnexure2AFeedback(err.message || 'Please try again.', 'error', 'Error saving draft');
        } finally {
            if (draftBtn) {
                draftBtn.disabled = false;
                draftBtn.innerHTML = originalText;
            }
        }
    }

    const LICENCE_THRESHOLD = <?= $licence_threshold ?>;

    function toggleLicenceMandatory() {
        const workers = parseInt(document.querySelector('[name="workers_proposed_to_be_engaged"]')?.value || '0', 10) || 0;
        const mandatory = workers >= LICENCE_THRESHOLD;
        const badge = document.getElementById('licenceMandatoryBadge');
        const card = document.getElementById('section7Card');
        const licInputs = document.querySelectorAll('#licenseTableBody input[type="text"], #licenseTableBody input[type="date"]');
        const fileInputs = document.querySelectorAll('#licenseTableBody input[type="file"]');

        if (badge) badge.style.display = mandatory ? 'inline-flex' : 'none';
        if (card) card.style.borderColor = mandatory ? '#f59e0b' : '';
        licInputs.forEach(i => i.required = mandatory);
        fileInputs.forEach(i => i.required = mandatory && !i.closest('td')?.querySelector('input[name="existing_license_file[]"]')?.value);
    }

    window.addEventListener('load', () => {
        fetchSAPData();
        updateSlNos('ecpTableBody');
        updateSlNos('licenseTableBody');
        toggleEPF();
        toggleESI();
        toggleEcpPolicy();
        toggleLicenceMandatory();
    });

    document.querySelector('[name="workers_proposed_to_be_engaged"]')?.addEventListener('input', toggleLicenceMandatory);
    document.querySelectorAll('input[name="epf_registered"]').forEach(r => r.addEventListener('change', toggleEPF));
    document.querySelectorAll('input[name="esi_registered"]').forEach(r => r.addEventListener('change', toggleESI));
    document.querySelectorAll('input[name="ecp_covered"]').forEach(r => r.addEventListener('change', toggleEcpPolicy));

    // Select all logic
    document.getElementById('selectAllPO')?.addEventListener('change', (e) => {
        document.querySelectorAll('.po-check').forEach(cb => cb.checked = e.target.checked);
    });
    document.getElementById('selectAllPWO')?.addEventListener('change', (e) => {
        document.querySelectorAll('.pwo-check').forEach(cb => cb.checked = e.target.checked);
    });
    document.getElementById('selectAllSO')?.addEventListener('change', (e) => {
        document.querySelectorAll('.so-check').forEach(cb => cb.checked = e.target.checked);
    });

    // Worker category checklist change handler
    document.querySelectorAll('.worker-cat-check').forEach(cb => {
        cb.addEventListener('change', validateWorkerCategories);
    });
</script>

<?php
}
renderLayout('Contractor Registration', 'renderContent', $role, $name);
?>
