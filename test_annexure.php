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

    // Point 1 (First Time Registration Login): If no profile exists, create a draft stub
    if (empty($c) && $vendor_code !== '') {
        $sap_vendor    = @db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ? LIMIT 1", 's', [$vendor_code]);
        $draft_name    = $sap_vendor['vendor_name']  ?? $sap_vendor['name']   ?? '';
        $draft_address = $sap_vendor['address']      ?? $sap_vendor['street'] ?? '';
        $draft_email   = $sap_vendor['email']        ?? '';
        $draft_mobile  = $sap_vendor['mobile']       ?? $sap_vendor['phone']  ?? '';
        $uid           = (int)($_SESSION['user_id']  ?? 0);
        @db_execute($conn, "INSERT IGNORE INTO contractors (user_id, vendor_code, vendor_name, address, email, mobile, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')",
            'isssss', [$uid, $vendor_code, $draft_name, $draft_address, $draft_email, $draft_mobile]);
        $c = db_single($conn, "SELECT * FROM contractors WHERE vendor_code = ?", 's', [$vendor_code]);
    }

    $selected_pos = [];
    $selected_pwos = [];
    $selected_sos = [];
    if (!empty($c['id'])) {
        $cid = (int)$c['id'];
        $pos_rows = db_fetch_all($conn, "SELECT po_number FROM contractor_po_selection WHERE contractor_id = ?", 'i', [$cid]);
        foreach ($pos_rows as $row) {
            $selected_pos[] = $row['po_number'];
        }
        $pwos_rows = db_fetch_all($conn, "SELECT pwo_number FROM contractor_pwo_selection WHERE contractor_id = ?", 'i', [$cid]);
        foreach ($pwos_rows as $row) {
            $selected_pwos[] = $row['pwo_number'];
        }
        $sos_rows = db_fetch_all($conn, "SELECT sale_order_no FROM contractor_so_selection WHERE contractor_id = ?", 'i', [$cid]);
        foreach ($sos_rows as $row) {
            $selected_sos[] = $row['sale_order_no'];
        }
    }

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
    $saved_limited_row_readonly_attr = $limited_edit_readonly_attr;
    $saved_limited_file_disabled_attr = $limited_edit_disabled_attr;
    $saved_limited_action_disabled_attr = $limited_edit_disabled_attr;
    $ecp_choice_disabled_attr = $limited_edit_disabled_attr;
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
    $epf_reason_type = '';
    $epf_reason_other = '';
    if ($epf_reason === 'Above Coverage') {
        $epf_reason_type = 'Above Coverage';
    } elseif (!empty($epf_reason)) {
        $epf_reason_type = 'Others';
        $epf_reason_other = $epf_reason;
    }

    $esi_reason = $reason_value('ESI Reason');
    $esi_reason_type = '';
    $esi_reason_other = '';
    if ($esi_reason === 'Above Coverage') {
        $esi_reason_type = 'Above Coverage';
    } elseif (!empty($esi_reason)) {
        $esi_reason_type = 'Others';
        $esi_reason_other = $esi_reason;
    }
    
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

        /* Contractor registration polish: compact cards and contained actions */
        .container-fluid.py-4 {
            max-width: 1180px;
            padding-top: 18px !important;
            padding-bottom: 28px !important;
        }
        #contractorTabs {
            margin-bottom: 18px !important;
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            padding: 4px 8px 0;
        }
        #contractorTabs .nav-link {
            padding: 12px 18px;
            font-size: 13px;
        }
        .sticky-bottom-bar {
            position: static !important;
            background: #fff !important;
            border-top: 1px solid #e2e8f0;
            padding: 14px 0 0 !important;
            margin: 0 !important;
            z-index: 1 !important;
            backdrop-filter: none !important;
        }
        #registrationDetails .registration-card {
            border: 1px solid #d7e0ec;
            border-radius: 8px;
            margin-bottom: 14px;
            padding: 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        #registrationDetails .registration-section-header {
            background: #eef6ff;
            border-left: 3px solid #2563eb;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 800;
            color: #15345f;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        #registrationDetails .registration-grid {
            grid-template-columns: minmax(220px, .95fr) minmax(280px, 1.35fr);
            gap: 12px 18px;
            align-items: end;
        }
        #registrationDetails .form-label {
            margin-bottom: 6px;
            font-size: 11px;
            color: #334155;
        }
        #registrationDetails .form-control,
        #registrationDetails .form-select {
            min-height: 38px;
            border-radius: 7px;
            border: 1px solid #c8d3df;
            padding: 8px 10px;
            font-size: 13px;
        }
        #registrationDetails .form-control:disabled,
        #registrationDetails .form-select:disabled,
        #registrationDetails .form-control[readonly] {
            background-color: #f1f5f9;
            color: #0f172a;
            opacity: 1;
        }
        #registrationDetails textarea.form-control { min-height: 82px; }
        #registrationDetails .gov-radio-group { min-height: 38px; gap: 16px; }
        #registrationDetails .gov-radio-group .form-check-label { font-size: 13px; font-weight: 700; color: #475569; }
        #registrationDetails .registration-actions {
            padding: 14px 0 0;
            margin-top: 4px;
            background: #fff;
        }
        @media (max-width: 768px) {
            #registrationDetails .registration-grid { grid-template-columns: 1fr; }
        }

        /* Annexure 2A UI fix: keep this page contained inside the CLMS shell. */
        .annexure2a-page {
            max-width: 1180px;
            padding-top: 18px !important;
            padding-bottom: 30px !important;
        }
        .annexure2a-page,
        .annexure2a-page * { letter-spacing: 0; }
        .annexure2a-page > .d-flex.justify-content-between.align-items-center.mb-4 {
            align-items: center !important;
            gap: 16px;
            margin-bottom: 18px !important;
        }
        .annexure2a-page h2 {
            font-size: 22px;
            line-height: 1.25;
        }
        .annexure2a-page > .d-flex.justify-content-between.align-items-center.mb-4 > .text-end {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .annexure2a-page .card {
            border-radius: 8px;
            border: 1px solid #dbe3ef;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }
        .annexure2a-page .card-header {
            padding: 12px 16px;
            background: #fff;
        }
        .annexure2a-page .card-header h5 {
            font-size: 14px;
            font-weight: 800;
        }
        .annexure2a-page .form-label {
            letter-spacing: 0;
            line-height: 1.25;
        }
        .annexure2a-page .form-control,
        .annexure2a-page .form-select {
            min-height: 40px;
            border-radius: 7px;
            font-size: 13px;
            padding: 9px 12px;
        }
        .annexure2a-page textarea.form-control { min-height: 78px; }
        .annexure2a-page #contractorTabs {
            display: flex;
            gap: 4px;
            margin-bottom: 18px !important;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .annexure2a-page #contractorTabs .nav-link {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            border-radius: 7px 7px 0 0;
        }
        .annexure2a-page .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 0 0 8px 8px;
        }
        .annexure2a-page #poTable { min-width: 920px; }
        .annexure2a-page #pwoTable { min-width: 620px; }
        .annexure2a-page .gov-table { min-width: 760px; }
        .annexure2a-page .gov-table th,
        .annexure2a-page .gov-table td {
            padding: 9px 10px;
            font-size: 12px;
        }
        .annexure2a-page .gov-table .form-control { min-width: 132px; }
        .annexure2a-page #basicDetails > .text-end.mb-5 {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 22px !important;
        }
        .annexure2a-page #basicDetails > .text-end.mb-5 .btn,
        .annexure2a-page .registration-actions .btn {
            border-radius: 7px;
            min-height: 40px;
            line-height: 1.2;
        }
        .annexure2a-page #basicDetails > .text-end.mb-5 .btn {
            padding: 10px 22px !important;
            font-size: 13px;
        }
        .annexure2a-page .registration-actions {
            position: sticky;
            bottom: 0;
            z-index: 5;
            margin-top: 18px;
            padding: 12px 0 4px;
            background: linear-gradient(180deg, rgba(248,250,252,0), #fff 28%);
        }
        @media (max-width: 768px) {
            .annexure2a-page { padding-left: 12px !important; padding-right: 12px !important; }
            .annexure2a-page > .d-flex.justify-content-between.align-items-center.mb-4 {
                align-items: flex-start !important;
                flex-direction: column;
            }
            .annexure2a-page > .d-flex.justify-content-between.align-items-center.mb-4 > .text-end {
                width: 100%;
                justify-content: flex-start;
            }
            .annexure2a-page > .d-flex.justify-content-between.align-items-center.mb-4 > .text-end .badge,
            .annexure2a-page > .d-flex.justify-content-between.align-items-center.mb-4 > .text-end .btn,
            .annexure2a-page #basicDetails > .text-end.mb-5 .btn {
                width: 100%;
                justify-content: center;
                margin-left: 0 !important;
            }
            .annexure2a-page .card-body.p-4 { padding: 14px !important; }
            .annexure2a-page .registration-actions {
                position: static;
                flex-direction: column;
            }
            .annexure2a-page .registration-actions .btn { width: 100%; }
        }

        /* Full-width registration tab requested: remove side gap in main workspace. */
        .annexure2a-page.container-fluid.py-4 {
            width: calc(100% + 48px) !important;
            max-width: none !important;
            margin: -24px -24px 0 -24px !important;
            padding: 16px 14px 28px !important;
        }
        .annexure2a-page #contractorTabs,
        .annexure2a-page #contractorTabsContent,
        .annexure2a-page #annexure2AForm,
        .annexure2a-page #registrationDetails {
            width: 100%;
            max-width: none;
        }
        .annexure2a-page #contractorTabs {
            border-radius: 0;
            border-left: 0;
            border-right: 0;
            padding-left: 10px;
            padding-right: 10px;
        }
        .annexure2a-page #registrationDetails {
            display: none;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            align-items: start;
        }
        .annexure2a-page #registrationDetails.active,
        .annexure2a-page #registrationDetails.show.active {
            display: grid;
        }
        .annexure2a-page #registrationDetails .registration-card {
            width: 100%;
            margin-bottom: 0;
        }
        .annexure2a-page #registrationDetails .registration-card:has(.gov-table),
        .annexure2a-page #registrationDetails #reasonCard,
        .annexure2a-page #registrationDetails #section7Card,
        .annexure2a-page #registrationDetails .registration-actions {
            grid-column: 1 / -1;
        }
        .annexure2a-page #registrationDetails .registration-grid {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px 14px;
            align-items: end;
        }
        .annexure2a-page #registrationDetails .registration-grid .span-2 {
            grid-column: 1 / -1;
        }
        .annexure2a-page #registrationDetails .registration-section-header {
            min-height: 36px;
            display: flex;
            align-items: center;
        }
        .annexure2a-page #registrationDetails .registration-actions {
            border-top: 1px solid #e2e8f0;
            padding: 12px 14px;
            margin: 0 -14px -14px;
        }
        @media (min-width: 1500px) {
            .annexure2a-page #registrationDetails {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .annexure2a-page #registrationDetails .registration-card:has(.gov-table),
            .annexure2a-page #registrationDetails #section7Card,
            .annexure2a-page #registrationDetails .registration-actions {
                grid-column: 1 / -1;
            }
        }
        @media (max-width: 900px) {
            .annexure2a-page.container-fluid.py-4 {
                width: calc(100% + 48px) !important;
                margin: -24px -24px 0 -24px !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
            .annexure2a-page #registrationDetails,
            .annexure2a-page #registrationDetails.show.active {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .annexure2a-page.container-fluid.py-4 {
                width: 100% !important;
                margin: 0 !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }

        /* Registration tab reset: no page scrollbar, clean full-width sections. */
        .main-content:has(.annexure2a-page) {
            padding: 0 !important;
            overflow-x: hidden !important;
        }
        .main-content:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4 {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 14px 12px 24px !important;
            overflow-x: hidden !important;
        }
        .annexure2a-page #contractorTabsContent,
        .annexure2a-page #annexure2AForm,
        .annexure2a-page #registrationDetails {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }
        .annexure2a-page #registrationDetails,
        .annexure2a-page #registrationDetails.show.active {
            display: block !important;
        }
        .annexure2a-page #registrationDetails:not(.show) {
            display: none !important;
        }
        .annexure2a-page #registrationDetails .registration-card {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 0 10px !important;
            padding: 12px !important;
            border-radius: 8px !important;
        }
        .annexure2a-page #registrationDetails .registration-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) !important;
            gap: 10px 12px !important;
            align-items: end !important;
        }
        .annexure2a-page #registrationDetails .registration-grid .span-2 {
            grid-column: 1 / -1 !important;
        }
        .annexure2a-page #registrationDetails .row {
            --bs-gutter-x: 12px;
            --bs-gutter-y: 10px;
        }
        .annexure2a-page #registrationDetails .registration-section-header {
            min-height: 0 !important;
            padding: 7px 10px !important;
            margin-bottom: 10px !important;
            font-size: 12px !important;
            line-height: 1.25 !important;
        }
        .annexure2a-page #registrationDetails .form-control,
        .annexure2a-page #registrationDetails .form-select {
            width: 100% !important;
            min-width: 0 !important;
        }
        .annexure2a-page #registrationDetails textarea.form-control {
            min-height: 64px !important;
        }
        .annexure2a-page #registrationDetails .table-responsive {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
        }
        .annexure2a-page #registrationDetails .gov-table,
        .annexure2a-page #registrationDetails #ecpTable,
        .annexure2a-page #registrationDetails #licenseTable {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: fixed !important;
        }
        .annexure2a-page #registrationDetails .gov-table th,
        .annexure2a-page #registrationDetails .gov-table td {
            white-space: normal !important;
            word-break: break-word;
            padding: 7px 8px !important;
            font-size: 11px !important;
        }
        .annexure2a-page #registrationDetails .gov-table input[type="file"] {
            font-size: 11px !important;
            padding: 6px !important;
        }
        .annexure2a-page #registrationDetails .gov-table .delete-btn {
            min-width: 28px;
            padding: 4px 6px;
        }
        .annexure2a-page #registrationDetails .registration-actions {
            position: sticky !important;
            bottom: 0;
            z-index: 20;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin: 12px -12px -24px !important;
            padding: 10px 12px !important;
            background: #fff !important;
            border-top: 1px solid #dbe3ef;
            box-shadow: 0 -8px 16px rgba(15, 23, 42, 0.06);
        }
        @media (max-width: 1100px) {
            .annexure2a-page #registrationDetails .registration-grid {
                grid-template-columns: 1fr !important;
            }
            .annexure2a-page #registrationDetails .gov-table th,
            .annexure2a-page #registrationDetails .gov-table td {
                padding: 6px !important;
                font-size: 10.5px !important;
            }
        }
        @media (max-width: 768px) {
            .main-content:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4 {
                padding: 10px !important;
            }
            .annexure2a-page #registrationDetails .table-responsive {
                overflow-x: auto !important;
            }
            .annexure2a-page #registrationDetails .gov-table {
                min-width: 720px !important;
            }
            .annexure2a-page #registrationDetails .registration-actions {
                position: static !important;
                flex-direction: column;
                margin: 12px -10px -10px !important;
            }
            .annexure2a-page #registrationDetails .registration-actions .btn {
                width: 100%;
            }
        }

        /* Kill double vertical scrollbar on this page. */
        html:has(.annexure2a-page),
        body:has(.annexure2a-page) {
            height: 100vh !important;
            max-height: 100vh !important;
            overflow: hidden !important;
        }
        body:has(.annex2a-page) .layout-wrapper,
        body:has(.annexure2a-page) .layout-wrapper {
            height: calc(100vh - 72px) !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
        body:has(.annexure2a-page) .main-content {
            height: calc(100vh - 72px) !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 0 !important;
            scrollbar-gutter: stable;
        }
        body:has(.annexure2a-page) .sidebar {
            height: calc(100vh - 72px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }
        .annexure2a-page { min-height: auto !important; }

        /* Hide the remaining visible scrollbar while preserving wheel/touch scrolling. */
        body:has(.annexure2a-page) .main-content,
        .annexure2a-page #registrationDetails .table-responsive {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar,
        .annexure2a-page #registrationDetails .table-responsive::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important;
        }

        /* Single-scroll mode: only rendered Annexure data scrolls. */
        body:has(.annexure2a-page) .main-content {
            overflow: hidden !important;
            padding: 0 !important;
        }
        body:has(.annexure2a-page) .sidebar {
            overflow: hidden !important;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4 {
            height: calc(100vh - 72px) !important;
            max-height: calc(100vh - 72px) !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 14px 12px 76px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scrollbar-width: auto !important;
            -ms-overflow-style: auto !important;
            scrollbar-gutter: stable;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4::-webkit-scrollbar {
            width: 10px !important;
            height: 0 !important;
            display: block !important;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4::-webkit-scrollbar-track {
            background: #e2e8f0 !important;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4::-webkit-scrollbar-thumb {
            background: #94a3b8 !important;
            border-radius: 10px !important;
            border: 2px solid #e2e8f0 !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar,
        body:has(.annexure2a-page) .sidebar::-webkit-scrollbar,
        body:has(.annexure2a-page)::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important;
        }

        /* Final scroll placement: layout scrolls, not the inner Annexure form. */
        body:has(.annexure2a-page) {
            overflow: hidden !important;
        }
        body:has(.annexure2a-page) .layout-wrapper {
            height: calc(100vh - 72px) !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
        body:has(.annexure2a-page) .main-content {
            height: calc(100vh - 72px) !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 0 !important;
            scrollbar-width: auto !important;
            -ms-overflow-style: auto !important;
            scrollbar-gutter: stable;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar {
            width: 10px !important;
            height: 0 !important;
            display: block !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar-track {
            background: #e2e8f0 !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar-thumb {
            background: #94a3b8 !important;
            border-radius: 10px !important;
            border: 2px solid #e2e8f0 !important;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4 {
            height: auto !important;
            max-height: none !important;
            overflow-y: visible !important;
            overflow-x: hidden !important;
            padding: 14px 12px 24px !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important;
        }

        /* Annexure scroll fix: keep one scrollbar on the main content only. */
        html:has(.annexure2a-page),
        body:has(.annexure2a-page) {
            height: 100vh !important;
            max-height: 100vh !important;
            overflow: hidden !important;
        }
        body:has(.annexure2a-page) .layout-wrapper {
            height: calc(100vh - 72px) !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
        body:has(.annexure2a-page) .sidebar {
            height: calc(100vh - 72px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }
        body:has(.annexure2a-page) .main-content {
            height: calc(100vh - 72px) !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 24px !important;
            scrollbar-width: auto !important;
            -ms-overflow-style: auto !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar {
            width: 10px !important;
            height: 0 !important;
            display: block !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar-track {
            background: #e2e8f0 !important;
        }
        body:has(.annexure2a-page) .main-content::-webkit-scrollbar-thumb {
            background: #94a3b8 !important;
            border-radius: 10px !important;
            border: 2px solid #e2e8f0 !important;
        }
        body:has(.annexure2a-page) .annexure2a-page.container-fluid.py-4 {
            height: auto !important;
            max-height: none !important;
            overflow-y: visible !important;
            overflow-x: hidden !important;
            padding-top: 0 !important;
            padding-bottom: 24px !important;
        }
        body:has(.annexure2a-page) #contractorTabsContent,
        body:has(.annexure2a-page) #annexure2AForm,
        body:has(.annexure2a-page) #registrationDetails,
        body:has(.annexure2a-page) .tab-content,
        body:has(.annexure2a-page) .tab-pane {
            height: auto !important;
            max-height: none !important;
            overflow-y: visible !important;
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
                                            <input type="file" class="form-control" name="license_file[]" accept="application/pdf,.pdf" <?= $saved_limited_file_disabled_attr ? 'style="display:none;"' : '' ?>>
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
                <div class="registration-card"><div class="registration-section-header">12. Name of Contact Person <span class="text-danger">*</span></div><input type="text" class="form-control" name="contact_person" id="contact_person" pattern="^[a-zA-Z\s]+$" value="<?= htmlspecialchars($c['contact_person'] ?? '') ?>" required placeholder="Alphabets only" <?= $readonly_attr ?>><div class="invalid-feedback">Contact person must be letters only.</div></div>
                <div class="registration-card">
                    <div class="registration-section-header">13. Mobile Number </div>
                    <div class="registration-grid">
                        <div><label class="form-label required">Mobile Number 1</label><input type="text" class="form-control" name="mobile" pattern="^[0-9]{10}$" value="<?= htmlspecialchars($c['mobile'] ?? '') ?>" required oninvalid="this.setCustomValidity('Enter correct mobile number.')" oninput="this.setCustomValidity('')" <?= $readonly_attr ?>></div>
                        <div><label class="form-label">Mobile Number 2</label><input type="text" class="form-control" name="vendor_mob2" pattern="^[0-9]{10}$" value="<?= htmlspecialchars($c['vendor_mob2'] ?? '') ?>" oninvalid="this.setCustomValidity('Enter correct mobile number.')" oninput="this.setCustomValidity('')" <?= $readonly_attr ?>></div>
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
<script>
(function normalizeAnnexure2AScroll() {
    document.documentElement.style.height = '100vh';
    document.documentElement.style.overflow = 'hidden';
    document.body.style.height = '100vh';
    document.body.style.overflow = 'hidden';
    const wrapper = document.querySelector('.layout-wrapper');
    const main = document.querySelector('.main-content');
    const page = document.querySelector('.annexure2a-page');
    if (wrapper) {
        wrapper.style.height = 'calc(100vh - 72px)';
        wrapper.style.minHeight = '0';
        wrapper.style.overflow = 'hidden';
    }
    if (main) {
        main.style.height = 'calc(100vh - 72px)';
        main.style.overflowY = 'auto';
        main.style.overflowX = 'hidden';
        main.style.padding = '24px';
    }
    if (page) {
        page.style.height = 'auto';
        page.style.maxHeight = 'none';
        page.style.overflowY = 'visible';
        page.style.overflowX = 'hidden';
    }
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const ANNEXURE_IS_READONLY = <?= $is_readonly ? 'true' : 'false' ?>;
    const ANNEXURE2A_LIMITED_EDIT = <?= $is_limited_update_mode ? 'true' : 'false' ?>;
    const SAVED_POS = <?= json_encode($selected_pos) ?>;
    const SAVED_PWOS = <?= json_encode($selected_pwos) ?>;
    const SAVED_SOS = <?= json_encode($selected_sos) ?>;

    function getEditableState() {
        const state = {
            ecp: [],
            license: []
        };
        document.querySelectorAll('#ecpTableBody .ecp-row').forEach(row => {
            state.ecp.push({
                number: row.querySelector('input[name="ecp_number[]"]')?.value || '',
                valid_from: row.querySelector('input[name="ecp_valid_from[]"]')?.value || '',
                valid_to: row.querySelector('input[name="ecp_valid_to[]"]')?.value || '',
                workers: row.querySelector('input[name="ecp_workers[]"]')?.value || ''
            });
        });
        document.querySelectorAll('#licenseTableBody .license-row').forEach(row => {
            state.license.push({
                no: row.querySelector('input[name="license_no[]"]')?.value || '',
                validity: row.querySelector('input[name="license_validity[]"]')?.value || '',
                issued: row.querySelector('input[name="issued_date[]"]')?.value || '',
                expiry: row.querySelector('input[name="expiry_date[]"]')?.value || '',
                existing_file: row.querySelector('input[name="existing_license_file[]"]')?.value || ''
            });
        });
        return JSON.stringify(state);
    }
    let initialEditableState = '';

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


    function unlockEcpForResubmit() {
        if (!ANNEXURE2A_LIMITED_EDIT) return;

        document.querySelectorAll('input[name="ecp_covered"]').forEach(input => {
            input.disabled = false;
            input.readOnly = false;
        });
        document.querySelectorAll('input[type="hidden"][name="ecp_covered"]').forEach(input => input.remove());

        document.querySelectorAll('#ecpTableBody input, #ecp_exemption_reason').forEach(input => {
            input.disabled = false;
            input.readOnly = false;
        });

        document.querySelectorAll('#ecpTableBody .delete-btn, #addEcpBtn').forEach(btn => {
            btn.disabled = false;
            btn.style.display = '';
        });

        updateSlNos('ecpTableBody');
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
        console.log("[SAP Fetch] Initializing fetch for vendor code:", code);
        if (!code) {
            console.warn("[SAP Fetch] Vendor code is empty. Aborting fetch.");
            return;
        }
        
        document.getElementById('po-loading').style.display = 'block';
        
        try {
            // Fetch POs
            const poUrl = `../../api/contractor/get_vendor_pos.php?vendor_code=${code}`;
            console.log("[SAP Fetch] Fetching POs from:", poUrl);
            const poResp = await fetch(poUrl);
            const poData = await poResp.json();
            console.log("[SAP Fetch] PO response data:", poData);
            
            const poBody = document.getElementById('poTableBody');
            if (poData.status === 'success' && poData.data.length > 0) {
                poBody.innerHTML = poData.data.map(p => `
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="po-check form-check-input" value="${p.po_number}" ${SAVED_POS.includes(p.po_number) ? 'checked' : ''} ${(ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) ? 'disabled' : ''}></td>
                        <td><span class="fw-bold text-dark">${p.po_number}</span></td>
                        <td><span class="badge bg-light text-dark border">${p.po_type}</span></td>
                        <td>${p.purchasing_group}</td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${p.header_text}</td>
                        <td>${p.currency}</td>
                        <td class="fw-bold">${p.total_value}</td>
                        <td>${p.document_date}</td>
                    </tr>
                `).join('');
            } else {
                console.log("[SAP Fetch] No PO records found or status not success.");
            }

            // Fetch PWOs
            const pwoUrl = `../../api/contractor/get_vendor_pwos.php?vendor_code=${code}`;
            console.log("[SAP Fetch] Fetching PWOs from:", pwoUrl);
            const pwoResp = await fetch(pwoUrl);
            const pwoData = await pwoResp.json();
            console.log("[SAP Fetch] PWO response data:", pwoData);
            
            const pwoBody = document.getElementById('pwoTableBody');
            if(pwoData.status === 'success' && pwoData.data.length > 0) {
                pwoBody.innerHTML = pwoData.data.map(p => `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="pwo-check form-check-input" value="${p.pwo_number}" ${SAVED_PWOS.includes(p.pwo_number) ? 'checked' : ''} ${(ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) ? 'disabled' : ''}></td>
                        <td>${p.pwo_number}</td>
                        <td>${p.vessel}</td>
                        <td>${p.work_completion_date}</td>
                    </tr>
                `).join('');
            } else {
                console.log("[SAP Fetch] No PWO records found or status not success.");
            }

            // Fetch Sales Orders
            const soUrl = `../../api/contractor/get_vendor_sales.php?vendor_code=${code}`;
            console.log("[SAP Fetch] Fetching Sales Orders from:", soUrl);
            const soResp = await fetch(soUrl);
            const soData = await soResp.json();
            console.log("[SAP Fetch] Sales Order response data:", soData);
            
            const soBody = document.getElementById('soTableBody');
            if(soData.status === 'success' && soData.data.length > 0) {
                soBody.innerHTML = soData.data.map(s => `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="so-check form-check-input" value="${s.sale_order_no}" ${SAVED_SOS.includes(s.sale_order_no) ? 'checked' : ''} ${(ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) ? 'disabled' : ''}></td>
                        <td>${s.sale_order_no}</td>
                        <td class="fw-bold">${s.amount}</td>
                        <td>${s.currency}</td>
                    </tr>
                `).join('');
            } else {
                console.log("[SAP Fetch] No Sales Order records found or status not success.");
            }
        } catch (e) {
            console.error("[SAP Fetch] Error during fetching:", e);
        } finally {
            document.getElementById('po-loading').style.display = 'none';
            collectData();
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
        
        const posEl = document.getElementById('selected_pos');
        if (posEl) posEl.value = JSON.stringify(pos);
        
        const pwosEl = document.getElementById('selected_pwos');
        if (pwosEl) pwosEl.value = JSON.stringify(pwos);
        
        const salesEl = document.getElementById('selected_sales');
        if (salesEl) salesEl.value = JSON.stringify(sos);
        
        syncLicenseIssuedFields();
    }

    document.getElementById('annexure2AForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (ANNEXURE_IS_READONLY && !ANNEXURE2A_LIMITED_EDIT) {
            showAnnexure2AFeedback('This registration is locked while it is pending Welfare action.', 'warning', 'Locked');
            return;
        }
        
        const form = e.target;
        const btn = e.submitter || form.querySelector('.btn-reg-submit[type="submit"]') || document.getElementById('submitBtn');
        const isResubmit = btn && (btn.id === 'submitBtn' || btn.classList.contains('btn-reg-submit'));

        // Client-side modification verification in resubmit mode
        if (ANNEXURE2A_LIMITED_EDIT && isResubmit) {
            const currentState = getEditableState();
            if (currentState === initialEditableState) {
                showAnnexure2AFeedback('No changes detected in either "Employee Compensation Policy" or "Labour License Details". Please make modifications before resubmitting.', 'warning', 'No changes detected');
                return;
            }
        }
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
        collectData();
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
        collectData();
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
        unlockEcpForResubmit();
        toggleLicenceMandatory();
        initialEditableState = getEditableState();
    });

    document.querySelector('[name="workers_proposed_to_be_engaged"]')?.addEventListener('input', toggleLicenceMandatory);
    document.querySelectorAll('input[name="epf_registered"]').forEach(r => r.addEventListener('change', toggleEPF));
    document.querySelectorAll('input[name="esi_registered"]').forEach(r => r.addEventListener('change', toggleESI));
    document.querySelectorAll('input[name="ecp_covered"]').forEach(r => r.addEventListener('change', toggleEcpPolicy));

    // Robust event delegation on document level for "Select All" checkbox
    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'selectAllPO') {
            document.querySelectorAll('.po-check:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
            collectData();
        }
        if (e.target && e.target.id === 'selectAllPWO') {
            document.querySelectorAll('.pwo-check:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
            collectData();
        }
        if (e.target && e.target.id === 'selectAllSO') {
            document.querySelectorAll('.so-check:not(:disabled)').forEach(cb => cb.checked = e.target.checked);
            collectData();
        }
        // Point 2/3: Immediately sync hidden inputs on any individual PO/PWO/SO checkbox change
        if (e.target && (e.target.classList.contains('po-check') || e.target.classList.contains('pwo-check') || e.target.classList.contains('so-check'))) {
            collectData();
        }
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
