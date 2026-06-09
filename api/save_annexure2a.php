<?php
ob_start();
session_start();
header('Content-Type: application/json');

function annexure2a_json_response($payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    error_log('[save_annexure2a] Uncaught: ' . $e->getMessage());
    annexure2a_json_response([
        'success' => false,
        'message' => 'Server error while saving contractor registration.',
        'error' => $e->getMessage()
    ], 500);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[save_annexure2a] Fatal: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        annexure2a_json_response([
            'success' => false,
            'message' => 'Fatal server error while saving contractor registration.',
            'error' => $error['message']
        ], 500);
    }
});

try {
include '../include/config.php';
require_once __DIR__ . '/../include/labour_license_threshold.php';

// include/session.php installs diagnostic handlers; restore JSON handlers for this API.
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    error_log('[save_annexure2a] Uncaught: ' . $e->getMessage());
    annexure2a_json_response([
        'success' => false,
        'message' => 'Server error while saving contractor registration.',
        'error' => $e->getMessage()
    ], 500);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    annexure2a_json_response(['success' => false, 'message' => 'Invalid request method'], 405);
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    annexure2a_json_response(['success' => false, 'message' => 'User not authenticated'], 401);
}

if (!function_exists('clean')) {
    function clean($data) {
        return htmlspecialchars(trim($data ?? ''));
    }
}

function annexure2a_table_exists($conn, $table) {
    $safeTable = str_replace('`', '``', $table);
    $result = clms_db_query($conn, "SHOW TABLES LIKE '" . clms_db_real_escape_string($conn, $safeTable) . "'");
    return $result && clms_db_num_rows($result) > 0;
}

function annexure2a_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $result && clms_db_num_rows($result) > 0;
}

function annexure2a_ensure_column($conn, $table, $column, $definition) {
    if (annexure2a_column_exists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    if (!clms_db_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
        throw new Exception("Missing DB column `$table.$column` and auto-create failed: " . clms_db_error($conn));
    }
}

function annexure2a_ensure_submit_schema($conn) {
    $contractorColumns = [
        'vendor_mob2' => 'VARCHAR(20) NULL',
        'work_awarding_department' => 'VARCHAR(100) NULL',
        'epf_registered' => "VARCHAR(10) NULL",
        'epf_code' => 'VARCHAR(50) NULL',
        'epf_account_no' => 'VARCHAR(100) NULL',
        'esi_registered' => "VARCHAR(10) NULL",
        'esi_code' => 'VARCHAR(50) NULL',
        'epf_esi_exemption_reason' => 'TEXT NULL',
        'wage_category' => 'VARCHAR(100) NULL',
        'wage_declaration' => 'TEXT NULL',
        'ecp_covered' => "VARCHAR(10) DEFAULT 'NO'",
        'ecp_details_json' => 'TEXT NULL',
        'license_details_json' => 'TEXT NULL',
        'ecp_number' => 'VARCHAR(100) NULL',
        'ecp_valid_from' => 'DATE NULL',
        'ecp_valid_to' => 'DATE NULL',
        'workers_ecp' => 'INT DEFAULT 0',
        'workers_proposed_to_be_engaged' => 'INT DEFAULT 0',
        'workers_proposed' => 'INT DEFAULT 0',
        'worker_category' => 'VARCHAR(255) NULL',
        'license_no' => 'VARCHAR(100) NULL',
        'license_issued' => 'VARCHAR(100) NULL',
        'issued_date' => 'DATE NULL',
        'expiry_date' => 'DATE NULL',
        'license_file' => 'VARCHAR(255) NULL',
        'labour_license_appl_no' => 'VARCHAR(100) NULL',
        'labour_identification_no' => 'VARCHAR(100) NULL',
        'contact_person' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'application_no' => 'VARCHAR(50) NULL',
    ];
    foreach ($contractorColumns as $column => $definition) {
        annexure2a_ensure_column($conn, 'contractors', $column, $definition);
    }

    $annexureColumns = [
        'vendor_mob2' => 'VARCHAR(20) NULL',
        'office_address' => 'TEXT NULL',
        'epf_registered' => "VARCHAR(10) NULL",
        'epf_code' => 'VARCHAR(50) NULL',
        'epf_account_no' => 'VARCHAR(100) NULL',
        'esi_registered' => "VARCHAR(10) NULL",
        'esic_code' => 'VARCHAR(50) NULL',
        'epf_esi_exemption_reason' => 'TEXT NULL',
        'project_name' => 'VARCHAR(300) NULL',
        'wage_category' => 'VARCHAR(100) NULL',
        'wage_declaration' => 'TEXT NULL',
        'ecp_covered' => "VARCHAR(10) DEFAULT 'NO'",
        'ecp_details_json' => 'TEXT NULL',
        'license_details_json' => 'TEXT NULL',
        'ecp_number' => 'VARCHAR(100) NULL',
        'ecp_valid_from' => 'DATE NULL',
        'ecp_valid_to' => 'DATE NULL',
        'workers_ecp' => 'INT DEFAULT 0',
        'workers_proposed_to_be_engaged' => 'INT DEFAULT 0',
        'worker_category' => 'VARCHAR(255) NULL',
        'license_no' => 'VARCHAR(100) NULL',
        'license_issued' => 'VARCHAR(100) NULL',
        'issued_date' => 'DATE NULL',
        'expiry_date' => 'DATE NULL',
        'klwf_registration_no' => 'VARCHAR(100) NULL',
        'labour_license_appl_no' => 'VARCHAR(100) NULL',
        'labour_identification_no' => 'VARCHAR(100) NULL',
        'contact_person' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'workflow_status' => "VARCHAR(30) DEFAULT 'draft'",
        'submitted_at' => 'TIMESTAMP NULL DEFAULT NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ];
    foreach ($annexureColumns as $column => $definition) {
        annexure2a_ensure_column($conn, 'annexure2a', $column, $definition);
    }

    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS contractor_ecp_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        ecp_number VARCHAR(100) NULL,
        ecp_valid_from DATE NULL,
        ecp_valid_to DATE NULL,
        workers_ecp INT DEFAULT 0,
        file_path VARCHAR(255) NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS contractor_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        doc_type VARCHAR(100) NULL,
        file_path VARCHAR(255) NULL,
        original_name VARCHAR(255) NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS contractor_po_selection (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        po_number VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS contractor_pwo_selection (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        pwo_number VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS contractor_so_selection (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        sale_order_no VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value VARCHAR(255) NULL,
        setting_group VARCHAR(100) NULL,
        description TEXT NULL,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    annexure2a_ensure_column($conn, 'contractor_documents', 'contractor_id', 'INT NULL');
    annexure2a_ensure_column($conn, 'contractor_documents', 'doc_type', 'VARCHAR(100) NULL');
    annexure2a_ensure_column($conn, 'contractor_documents', 'file_path', 'VARCHAR(255) NULL');
    annexure2a_ensure_column($conn, 'contractor_po_selection', 'contractor_id', 'INT NULL');
    annexure2a_ensure_column($conn, 'contractor_po_selection', 'po_number', 'VARCHAR(100) NULL');
    annexure2a_ensure_column($conn, 'contractor_pwo_selection', 'contractor_id', 'INT NULL');
    annexure2a_ensure_column($conn, 'contractor_pwo_selection', 'pwo_number', 'VARCHAR(100) NULL');
    annexure2a_ensure_column($conn, 'contractor_so_selection', 'contractor_id', 'INT NULL');
    annexure2a_ensure_column($conn, 'contractor_so_selection', 'sale_order_no', 'VARCHAR(100) NULL');
    clms_ensure_labour_license_thresholds($conn);
}

function annexure2a_send_submission_email($vendorCode, $vendorName, $applicationNo, $status) {
    $to = 'arjunparajapati8595@gmail.com';
    $subject = 'Annexure 2A Submitted - ' . ($vendorCode ?: 'Contractor');
    $submittedAt = date('d-m-Y h:i A');
    $bodyLines = [
        'A new Annexure 2A form has been submitted.',
        '',
        'Vendor Code: ' . ($vendorCode ?: 'N/A'),
        'Vendor Name: ' . ($vendorName ?: 'N/A'),
        'Application No: ' . ($applicationNo ?: 'N/A'),
        'Status: ' . strtoupper($status ?: 'pending'),
        'Submitted At: ' . $submittedAt,
    ];
    $body = implode("\r\n", $bodyLines);
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: noreply@cslweb.teleconsystems.com',
        'Reply-To: noreply@cslweb.teleconsystems.com',
    ];

    $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
    if (!$sent) {
        error_log('[save_annexure2a] Submission email failed for vendor ' . $vendorCode);
    }

    return $sent;
}

annexure2a_ensure_submit_schema($conn);

// 1. Basic Details
$vendor_code = clean($_POST['vendor_code'] ?? '');
$vendor_name = clean($_POST['vendor_name'] ?? '');
$mobile_legacy = clean($_POST['mobile_legacy'] ?? '');
$email = clean($_POST['email'] ?? '');
$address = clean($_POST['address'] ?? '');

// 2. Registration Fields (redesigned Sections 1 to 14)
$work_awarding_department = clean($_POST['work_awarding_department'] ?? '');
$epf_registered = clean($_POST['epf_registered'] ?? 'NO');
$epf_code = ($epf_registered === 'YES') ? clean($_POST['epf_code'] ?? '') : '';
$epf_account_no = '';
$epf_reason = clean($_POST['epf_non_registration_reason'] ?? '');

$esi_registered = clean($_POST['esi_registered'] ?? 'NO');
$esi_code = ($esi_registered === 'YES') ? clean($_POST['esi_code'] ?? '') : '';
$esi_reason = clean($_POST['esi_non_registration_reason'] ?? '');

$wage_declaration = clean($_POST['wage_declaration'] ?? '');
$wage_category = clean($_POST['wage_category'] ?? ''); // Saved for compatibility

$ecp_covered = clean($_POST['ecp_covered'] ?? 'NO');
$ecp_reason = clean($_POST['ecp_exemption_reason'] ?? ($_POST['epf_esi_exemption_reason'] ?? ''));

$reason_parts = [];
if ($epf_registered === 'NO' && $epf_reason !== '') $reason_parts[] = 'EPF Reason: ' . $epf_reason;
if ($esi_registered === 'NO' && $esi_reason !== '') $reason_parts[] = 'ESI Reason: ' . $esi_reason;
if ($ecp_covered === 'NO' && $ecp_reason !== '') $reason_parts[] = 'EC Policy Reason: ' . $ecp_reason;
$epf_esi_exemption_reason = $reason_parts ? implode("\n", $reason_parts) : clean($_POST['epf_esi_exemption_reason'] ?? '');

$workers_proposed_to_be_engaged = !empty($_POST['workers_proposed_to_be_engaged']) ? intval($_POST['workers_proposed_to_be_engaged']) : 0;
$labour_license_threshold = clms_get_labour_license_threshold($conn);

// Category of workmen proposed (checkbox group)
$worker_categories = $_POST['worker_categories'] ?? [];
$worker_categories_str = implode(',', array_map('clean', $worker_categories));

$labour_license_appl_no = clean($_POST['labour_license_appl_no'] ?? '');
$labour_identification_no = clean($_POST['labour_identification_no'] ?? '');
$contact_person = clean($_POST['contact_person'] ?? '');
$mobile = clean($_POST['mobile'] ?? '');
$vendor_mob2 = clean($_POST['vendor_mob2'] ?? '');
$remarks = clean($_POST['remarks'] ?? '');

// 3. Process Multi-Row ECP policies
$ecps = [];
$ecp_numbers = $_POST['ecp_number'] ?? [];
$ecp_valid_froms = $_POST['ecp_valid_from'] ?? [];
$ecp_valid_tos = $_POST['ecp_valid_to'] ?? [];
$ecp_workers = $_POST['ecp_workers'] ?? ($_POST['ecp_insurance_company'] ?? []);

if ($ecp_covered === 'YES' && is_array($ecp_numbers)) {
    for ($i = 0; $i < count($ecp_numbers); $i++) {
        $has_ecp_row = !empty($ecp_numbers[$i]) || !empty($ecp_valid_froms[$i]) || !empty($ecp_valid_tos[$i]) || !empty($ecp_workers[$i]);
        if ($has_ecp_row) {
            $ecps[] = [
                'ecp_number' => clean($ecp_numbers[$i]),
                'ecp_valid_from' => clean($ecp_valid_froms[$i] ?? ''),
                'ecp_valid_to' => clean($ecp_valid_tos[$i] ?? ''),
                'workers_under_policy' => max(0, intval($ecp_workers[$i] ?? 0)),
                'insurance_company' => ''
            ];
        }
    }
}
$ecp_details_json = !empty($ecps) ? json_encode($ecps) : null;

// Primary ECP fields for backward compatibility
$ecp_number = !empty($ecps) ? $ecps[0]['ecp_number'] : '';
$ecp_valid_from = (!empty($ecps) && !empty($ecps[0]['ecp_valid_from'])) ? $ecps[0]['ecp_valid_from'] : null;
$ecp_valid_to = (!empty($ecps) && !empty($ecps[0]['ecp_valid_to'])) ? $ecps[0]['ecp_valid_to'] : null;
$workers_ecp = !empty($ecps) ? intval($ecps[0]['workers_under_policy'] ?? 0) : $workers_proposed_to_be_engaged;

// 4. Retrieve existing contractor profile for license file uploads
$existing = db_single($conn, "SELECT * FROM contractors WHERE vendor_code = ?", 's', [$vendor_code]);
$contractor_id = $existing ? $existing['id'] : null;
$current_status = '';
$limited_existing_edit = false;
$approved_limited_edit = false;

if ($existing) {
    $current_status = strtolower($existing['status'] ?? '');
    $limited_existing_edit = in_array($current_status, ['approved', 'pending', 'submitted', 'resubmitted', 'under_review', 'hold'], true);
    $approved_limited_edit = $current_status === 'approved';
}

$existing_license_details = [];
if ($existing && !empty($existing['license_details_json'])) {
    $existing_license_details = json_decode($existing['license_details_json'], true) ?: [];
}

// Process Multi-Row Labour Licenses
$licenses = [];
$lic_nos = $_POST['license_no'] ?? [];
$lic_validities = $_POST['license_validity'] ?? [];
$lic_issued_dates = $_POST['issued_date'] ?? [];
$lic_expiry_dates = $_POST['expiry_date'] ?? [];
$lic_issued_bys = $_POST['license_issued'] ?? [];
$existing_license_files = $_POST['existing_license_file'] ?? [];

// Upload directory setup
$uploadDir = "../uploads/contractors/" . $vendor_code . "/";

if (is_array($lic_nos)) {
    for ($i = 0; $i < count($lic_nos); $i++) {
        $has_uploaded_file = isset($_FILES['license_file']['name'][$i]) && !empty($_FILES['license_file']['name'][$i]);
        $has_license_row = !empty($lic_nos[$i]) || !empty($lic_validities[$i]) || !empty($lic_issued_dates[$i]) || !empty($lic_expiry_dates[$i]) || !empty($existing_license_files[$i]) || $has_uploaded_file;
        if ($has_license_row) {
            $file_path = $existing_license_files[$i] ?? '';

            // Check if a new file was uploaded for this row index
            if ($has_uploaded_file) {
                $file_name = $_FILES['license_file']['name'][$i];
                $file_tmp = $_FILES['license_file']['tmp_name'][$i];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if ($ext !== 'pdf') {
                    annexure2a_json_response(['success' => false, 'message' => 'Only PDF files are allowed for Labour License Certificates.'], 400);
                }

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $new_file_name = uniqid('lic_') . '.pdf';
                if (move_uploaded_file($file_tmp, $uploadDir . $new_file_name)) {
                    $file_path = $vendor_code . '/' . $new_file_name;
                } else {
                    annexure2a_json_response(['success' => false, 'message' => 'Failed to upload Labour License Certificate PDF. Please check uploads/contractors folder permission on Linux server.'], 500);
                }
            }

            $licenses[] = [
                'license_no' => clean($lic_nos[$i]),
                'validity' => clean($lic_validities[$i] ?? ''),
                'issued_date' => clean($lic_issued_dates[$i] ?? ''),
                'expiry_date' => clean($lic_expiry_dates[$i] ?? ''),
                'license_issued' => clean($lic_issued_bys[$i] ?? ''),
                'file_path' => $file_path
            ];
        }
    }
}
$license_details_json = !empty($licenses) ? json_encode($licenses) : null;

// Primary Licence fields for backward compatibility
$license_no = !empty($licenses) ? $licenses[0]['license_no'] : '';
$license_issued = !empty($licenses) ? $licenses[0]['license_issued'] : '';
$issued_date = (!empty($licenses) && !empty($licenses[0]['issued_date'])) ? $licenses[0]['issued_date'] : null;
$expiry_date = (!empty($licenses) && !empty($licenses[0]['expiry_date'])) ? $licenses[0]['expiry_date'] : null;
$license_file_path = !empty($licenses) ? $licenses[0]['file_path'] : '';

// Enforce setting of primary license_file if empty but existing profile has one
if (empty($license_file_path) && $existing && !empty($existing['license_file'])) {
    $license_file_path = $existing['license_file'];
}

if ($limited_existing_edit && $existing) {
    $vendor_name = $existing['vendor_name'] ?? $vendor_name;
    $mobile_legacy = $existing['mobile'] ?? $mobile_legacy;
    $email = $existing['email'] ?? $email;
    $address = $existing['address'] ?? $address;
    $work_awarding_department = $existing['work_awarding_department'] ?? $work_awarding_department;
    $epf_registered = $existing['epf_registered'] ?? $epf_registered;
    $epf_code = $existing['epf_code'] ?? $epf_code;
    $epf_account_no = $existing['epf_account_no'] ?? $epf_account_no;
    $esi_registered = $existing['esi_registered'] ?? $esi_registered;
    $esi_code = $existing['esi_code'] ?? $esi_code;
    $wage_category = $existing['wage_category'] ?? $wage_category;
    $wage_declaration = $existing['wage_declaration'] ?? $wage_declaration;
    $workers_proposed_to_be_engaged = intval($existing['workers_proposed_to_be_engaged'] ?? $workers_proposed_to_be_engaged);
    $worker_categories_str = $existing['worker_category'] ?? $worker_categories_str;
    $labour_license_appl_no = $existing['labour_license_appl_no'] ?? $labour_license_appl_no;
    $labour_identification_no = $existing['labour_identification_no'] ?? $labour_identification_no;
    $contact_person = $existing['contact_person'] ?? $contact_person;
    $mobile = $existing['mobile'] ?? $mobile;
    $vendor_mob2 = $existing['vendor_mob2'] ?? $vendor_mob2;
    $remarks = $existing['remarks'] ?? $remarks;

    $existing_reason = $existing['epf_esi_exemption_reason'] ?? '';
    $reason_parts = [];
    if ($epf_registered === 'NO') {
        $reason_parts[] = preg_match('/EPF Reason:\s*(.*?)(?=\n[A-Z][A-Za-z ]+ Reason:|$)/s', $existing_reason, $m) ? 'EPF Reason: ' . trim($m[1]) : '';
    }
    if ($esi_registered === 'NO') {
        $reason_parts[] = preg_match('/ESI Reason:\s*(.*?)(?=\n[A-Z][A-Za-z ]+ Reason:|$)/s', $existing_reason, $m) ? 'ESI Reason: ' . trim($m[1]) : '';
    }
    if ($ecp_covered === 'NO' && $ecp_reason !== '') {
        $reason_parts[] = 'EC Policy Reason: ' . $ecp_reason;
    }
    $reason_parts = array_values(array_filter($reason_parts));
    $epf_esi_exemption_reason = $reason_parts ? implode("\n", $reason_parts) : $existing_reason;

    if (empty($ecps) && !empty($existing['ecp_details_json'])) {
        $existing_ecps = json_decode($existing['ecp_details_json'], true);
        if (is_array($existing_ecps)) {
            $ecps = $existing_ecps;
            $ecp_details_json = json_encode($ecps);
            $ecp_number = $ecps[0]['ecp_number'] ?? '';
            $ecp_valid_from = $ecps[0]['ecp_valid_from'] ?? null;
            $ecp_valid_to = $ecps[0]['ecp_valid_to'] ?? null;
            $workers_ecp = intval($ecps[0]['workers_under_policy'] ?? $ecps[0]['workers_ecp'] ?? 0);
        }
    }
}

// 5. Input Validation Rules (Enforced during final Submission)
$request_action = $_POST['action'] ?? 'submit';
$is_final_submit = in_array($request_action, ['submit', 'resubmit'], true);

if ($is_final_submit) {
    if ($limited_existing_edit) {
        if ($esi_registered === 'NO' && $ecp_covered !== 'YES') {
            annexure2a_json_response(['success' => false, 'message' => 'Either ESI or EC Policy is mandatory'], 400);
        }
        if ($ecp_covered === 'YES' && empty($ecps)) {
            annexure2a_json_response(['success' => false, 'message' => 'Please provide at least one Employee Compensation (EC) Policy details row.'], 400);
        }
        if ($ecp_covered === 'YES') {
            foreach ($ecps as $policy) {
                if (empty($policy['ecp_number']) || empty($policy['ecp_valid_from']) || empty($policy['ecp_valid_to'])) {
                    annexure2a_json_response(['success' => false, 'message' => 'EC Policy Number, Valid From and Valid To are mandatory when EC Policy is Yes.'], 400);
                }
                if (intval($policy['workers_under_policy'] ?? 0) <= 0) {
                    annexure2a_json_response(['success' => false, 'message' => 'Please enter Number of Workers Under EC Policy.'], 400);
                }
            }
        }
        foreach ($licenses as $lic) {
            if (!empty($lic['issued_date']) && !empty($lic['expiry_date']) && strtotime($lic['expiry_date']) <= strtotime($lic['issued_date'])) {
                annexure2a_json_response(['success' => false, 'message' => 'Labour License Expiry Date must be later than Issue Date.'], 400);
            }
        }
        if ($workers_proposed_to_be_engaged >= $labour_license_threshold && (empty($license_no) || empty($license_file_path))) {
            annexure2a_json_response(['success' => false, 'message' => "Labour License is mandatory when proposed workmen are {$labour_license_threshold} or more."], 400);
        }
        goto annexure2a_validation_complete;
    }

    if (empty($work_awarding_department)) {
        annexure2a_json_response(['success' => false, 'message' => 'Work Awarding Department is mandatory.'], 400);
    }
    if ($epf_registered === 'YES' && empty($epf_code)) {
        annexure2a_json_response(['success' => false, 'message' => 'EPF Establishment Code is mandatory when EPF is Yes.'], 400);
    }
    if ($esi_registered === 'YES' && empty($esi_code)) {
        annexure2a_json_response(['success' => false, 'message' => 'ESI Establishment Code is mandatory when ESI is Yes.'], 400);
    }
    if (empty($wage_declaration)) {
        annexure2a_json_response(['success' => false, 'message' => 'Please accept the minimum wage declaration.'], 400);
    }
    
    if ($esi_registered === 'NO' && $ecp_covered !== 'YES') {
        annexure2a_json_response(['success' => false, 'message' => 'Either ESI or EC Policy is mandatory'], 400);
    }
    if ($ecp_covered === 'YES' && empty($ecps)) {
        annexure2a_json_response(['success' => false, 'message' => 'Please provide at least one Employee Compensation (EC) Policy details row.'], 400);
    }
    if ($ecp_covered === 'YES') {
        foreach ($ecps as $policy) {
            if (empty($policy['ecp_number']) || empty($policy['ecp_valid_from']) || empty($policy['ecp_valid_to'])) {
                annexure2a_json_response(['success' => false, 'message' => 'EC Policy Number, Valid From and Valid To are mandatory when EC Policy is Yes.'], 400);
            }
            if (intval($policy['workers_under_policy'] ?? 0) <= 0) {
                annexure2a_json_response(['success' => false, 'message' => 'Please enter Number of Workers Under EC Policy.'], 400);
            }
        }
    }

    if ($workers_proposed_to_be_engaged <= 0) {
        annexure2a_json_response(['success' => false, 'message' => 'Please enter a valid Number of Workmen Proposed.'], 400);
    }
    if (empty($worker_categories)) {
        annexure2a_json_response(['success' => false, 'message' => 'Please select at least one Category of Workmen.'], 400);
    }

    foreach ($licenses as $lic) {
        if (!empty($lic['issued_date']) && !empty($lic['expiry_date']) && strtotime($lic['expiry_date']) <= strtotime($lic['issued_date'])) {
            annexure2a_json_response(['success' => false, 'message' => 'Labour License Expiry Date must be later than Issue Date.'], 400);
        }
    }
    if ($workers_proposed_to_be_engaged >= $labour_license_threshold && (empty($license_no) || empty($license_file_path))) {
        annexure2a_json_response(['success' => false, 'message' => "Labour License is mandatory when proposed workmen are {$labour_license_threshold} or more."], 400);
    }

    if (empty($contact_person)) {
        annexure2a_json_response(['success' => false, 'message' => 'Name of Contact Person is mandatory.'], 400);
    }
    if (!preg_match("/^[a-zA-Z\s]+$/", $contact_person)) {
        annexure2a_json_response(['success' => false, 'message' => 'Contact Person Name must contain alphabets and spaces only.'], 400);
    }

    if (empty($mobile)) {
        annexure2a_json_response(['success' => false, 'message' => 'Mobile Number is mandatory.'], 400);
    }
    if (!preg_match("/^[0-9]{10}$/", $mobile)) {
        annexure2a_json_response(['success' => false, 'message' => 'Mobile Number must be exactly 10 numeric digits.'], 400);
    }
    if (!empty($vendor_mob2) && !preg_match("/^[0-9]{10}$/", $vendor_mob2)) {
        annexure2a_json_response(['success' => false, 'message' => 'Alternate Mobile Number must be exactly 10 numeric digits.'], 400);
    }
    if (!empty($labour_identification_no) && !preg_match("/^[0-9]+$/", $labour_identification_no)) {
        annexure2a_json_response(['success' => false, 'message' => 'Labour Identification No must contain digits only.'], 400);
    }
}

annexure2a_validation_complete:

if ($limited_existing_edit) {
    $status = ($request_action === 'draft') ? ($current_status ?: 'approved') : 'pending';
} else {
    $status = ($request_action === 'submit') ? 'pending' : 'draft';
}

// 6. DB INSERT OR UPDATE for 'contractors' table
if ($existing) {
    $sql = "UPDATE contractors SET 
        vendor_code=?, vendor_name=?, mobile=?, vendor_mob2=?, email=?, address=?,
        work_awarding_department=?, epf_registered=?, epf_code=?, epf_account_no=?, esi_registered=?, esi_code=?, epf_esi_exemption_reason=?,
        wage_category=?, wage_declaration=?, ecp_covered=?, ecp_details_json=?, license_details_json=?,
        ecp_number=?, ecp_valid_from=?, ecp_valid_to=?, workers_ecp=?,
        workers_proposed_to_be_engaged=?, workers_proposed=?, worker_category=?,
        license_no=?, license_issued=?, issued_date=?, expiry_date=?, license_file=?,
        labour_license_appl_no=?, labour_identification_no=?, contact_person=?, remarks=?,
        status=? WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        annexure2a_json_response(['success' => false, 'message' => 'Prepare contractors update failed: ' . $conn->error], 500);
    }
    $stmt->bind_param("sssssssssssssssssssssiiisssssssssssi", 
        $vendor_code, $vendor_name, $mobile, $vendor_mob2, $email, $address,
        $work_awarding_department, $epf_registered, $epf_code, $epf_account_no, $esi_registered, $esi_code, $epf_esi_exemption_reason,
        $wage_category, $wage_declaration, $ecp_covered, $ecp_details_json, $license_details_json,
        $ecp_number, $ecp_valid_from, $ecp_valid_to, $workers_ecp,
        $workers_proposed_to_be_engaged, $workers_proposed_to_be_engaged, $worker_categories_str,
        $license_no, $license_issued, $issued_date, $expiry_date, $license_file_path,
        $labour_license_appl_no, $labour_identification_no, $contact_person, $remarks,
        $status, $contractor_id
    );
} else {
    $sql = "INSERT INTO contractors (
        user_id, vendor_code, vendor_name, mobile, vendor_mob2, email, address,
        work_awarding_department, epf_registered, epf_code, epf_account_no, esi_registered, esi_code, epf_esi_exemption_reason,
        wage_category, wage_declaration, ecp_covered, ecp_details_json, license_details_json,
        ecp_number, ecp_valid_from, ecp_valid_to, workers_ecp,
        workers_proposed_to_be_engaged, workers_proposed, worker_category,
        license_no, license_issued, issued_date, expiry_date, license_file,
        labour_license_appl_no, labour_identification_no, contact_person, remarks,
        status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        annexure2a_json_response(['success' => false, 'message' => 'Prepare contractors insert failed: ' . $conn->error], 500);
    }
    $stmt->bind_param("isssssssssssssssssssssiiisssssssssss", 
        $user_id, $vendor_code, $vendor_name, $mobile, $vendor_mob2, $email, $address,
        $work_awarding_department, $epf_registered, $epf_code, $epf_account_no, $esi_registered, $esi_code, $epf_esi_exemption_reason,
        $wage_category, $wage_declaration, $ecp_covered, $ecp_details_json, $license_details_json,
        $ecp_number, $ecp_valid_from, $ecp_valid_to, $workers_ecp,
        $workers_proposed_to_be_engaged, $workers_proposed_to_be_engaged, $worker_categories_str,
        $license_no, $license_issued, $issued_date, $expiry_date, $license_file_path,
        $labour_license_appl_no, $labour_identification_no, $contact_person, $remarks,
        $status
    );
}

if (!$stmt->execute()) {
    annexure2a_json_response(['success' => false, 'message' => 'Database Error saving contractor: ' . $stmt->error], 500);
}

if (!$existing) {
    $contractor_id = $conn->insert_id;
}
$stmt->close();

// Log ECP details to historical logging table
if (!empty($ecp_number)) {
    $last_history = db_single($conn, "SELECT ecp_number, ecp_valid_from, ecp_valid_to, workers_ecp FROM contractor_ecp_history WHERE contractor_id = ? ORDER BY id DESC LIMIT 1", 'i', [$contractor_id]);
    if (!$last_history || 
        $last_history['ecp_number'] !== $ecp_number || 
        $last_history['ecp_valid_from'] !== $ecp_valid_from || 
        $last_history['ecp_valid_to'] !== $ecp_valid_to || 
        $last_history['workers_ecp'] != $workers_ecp) {
        
        $ecp_doc = db_single($conn, "SELECT file_path FROM contractor_documents WHERE contractor_id = ? AND doc_type = 'workmen_compensation'", 'i', [$contractor_id]);
        $ecp_file_path = $ecp_doc ? $ecp_doc['file_path'] : '';
        db_execute($conn, "INSERT INTO contractor_ecp_history (contractor_id, ecp_number, ecp_valid_from, ecp_valid_to, workers_ecp, file_path) VALUES (?,?,?,?,?,?)", 'isssis', [$contractor_id, $ecp_number, $ecp_valid_from, $ecp_valid_to, $workers_ecp, $ecp_file_path]);
    }
}

// 7. Sync with welfare application table (annexure2a)
$app_id = "APP-" . str_pad($user_id, 5, '0', STR_PAD_LEFT);
db_execute($conn, "UPDATE contractors SET application_no = ? WHERE id = ?", 'si', [$app_id, $contractor_id]);
$check_app = db_single($conn, "SELECT id FROM annexure2a WHERE contractor_id = ?", 'i', [$contractor_id]);

if ($limited_existing_edit && $request_action !== 'draft') {
    $wf_status = 'resubmitted';
} else {
    $wf_status = ($status === 'pending') ? 'submitted' : 'draft';
}

if ($check_app) {
    db_execute($conn, 
        "UPDATE annexure2a SET 
            contractor_name=?, mobile=?, vendor_mob2=?, email=?, office_address=?, 
            epf_registered=?, epf_code=?, epf_account_no=?, esi_registered=?, esic_code=?, epf_esi_exemption_reason=?,
            project_name=?, wage_category=?, wage_declaration=?, ecp_covered=?, ecp_details_json=?, license_details_json=?,
            ecp_number=?, ecp_valid_from=?, ecp_valid_to=?, 
            workers_ecp=?, workers_proposed_to_be_engaged=?, worker_category=?, 
            license_no=?, license_issued=?, issued_date=?, expiry_date=?, 
            klwf_registration_no=?, labour_license_appl_no=?, labour_identification_no=?, contact_person=?, remarks=?,
            workflow_status=?, submitted_at = IF(? = 'submitted', NOW(), submitted_at), updated_at=NOW() WHERE contractor_id=?",
        'ssssssssssssssssssssiissssssssssssi', 
        [
            $vendor_name, $mobile, $vendor_mob2, $email, $address,
            $epf_registered, $epf_code, $epf_account_no, $esi_registered, $esi_code, $epf_esi_exemption_reason,
            $work_awarding_department, $wage_category, $wage_declaration, $ecp_covered, $ecp_details_json, $license_details_json,
            $ecp_number, $ecp_valid_from, $ecp_valid_to,
            $workers_ecp, $workers_proposed_to_be_engaged, $worker_categories_str,
            $license_no, $license_issued, $issued_date, $expiry_date,
            $license_issued, $labour_license_appl_no, $labour_identification_no, $contact_person, $remarks,
            $wf_status, $wf_status, $contractor_id
        ]
    );
} else {
    db_execute($conn, 
        "INSERT INTO annexure2a (
            application_id, contractor_id, contractor_name, mobile, vendor_mob2, email, office_address, 
            epf_registered, epf_code, epf_account_no, esi_registered, esic_code, epf_esi_exemption_reason,
            project_name, wage_category, wage_declaration, ecp_covered, ecp_details_json, license_details_json,
            ecp_number, ecp_valid_from, ecp_valid_to, 
            workers_ecp, workers_proposed_to_be_engaged, worker_category, 
            license_no, license_issued, issued_date, expiry_date, 
            klwf_registration_no, labour_license_appl_no, labour_identification_no, contact_person, remarks,
            workflow_status
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        'sissssssssssssssssssssiisssssssssss', 
        [
            $app_id, $contractor_id, $vendor_name, $mobile, $vendor_mob2, $email, $address,
            $epf_registered, $epf_code, $epf_account_no, $esi_registered, $esi_code, $epf_esi_exemption_reason,
            $work_awarding_department, $wage_category, $wage_declaration, $ecp_covered, $ecp_details_json, $license_details_json,
            $ecp_number, $ecp_valid_from, $ecp_valid_to,
            $workers_ecp, $workers_proposed_to_be_engaged, $worker_categories_str,
            $license_no, $license_issued, $issued_date, $expiry_date,
            $license_issued, $labour_license_appl_no, $labour_identification_no, $contact_person, $remarks,
            $wf_status
        ]
    );
}

// 8. Handle PO/PWO/SO selections from basic tab (unchanged)
if (!empty($_POST['selected_pos'])) {
    $conn->query("DELETE FROM contractor_po_selection WHERE contractor_id = $contractor_id");
    foreach (json_decode($_POST['selected_pos'], true) as $po) {
        db_execute($conn, "INSERT INTO contractor_po_selection (contractor_id, po_number) VALUES (?,?)", 'is', [$contractor_id, $po]);
    }
}
if (!empty($_POST['selected_pwos'])) {
    $conn->query("DELETE FROM contractor_pwo_selection WHERE contractor_id = $contractor_id");
    foreach (json_decode($_POST['selected_pwos'], true) as $pwo) {
        db_execute($conn, "INSERT INTO contractor_pwo_selection (contractor_id, pwo_number) VALUES (?,?)", 'is', [$contractor_id, $pwo]);
    }
}
if (!empty($_POST['selected_sales'])) {
    $conn->query("DELETE FROM contractor_so_selection WHERE contractor_id = $contractor_id");
    foreach (json_decode($_POST['selected_sales'], true) as $so) {
        db_execute($conn, "INSERT INTO contractor_so_selection (contractor_id, sale_order_no) VALUES (?,?)", 'is', [$contractor_id, $so]);
    }
}

if (in_array($status, ['pending', 'resubmitted'], true)) {
    annexure2a_send_submission_email($vendor_code, $vendor_name, $app_id, $status);
}

$message = 'Contractor Registration Submitted Successfully';
if ($request_action === 'draft') {
    $message = 'Contractor Registration Saved as Draft';
} elseif ($request_action === 'resubmit' || ($limited_existing_edit && $request_action === 'submit')) {
    $message = 'Contractor Registration Resubmitted Successfully';
}

annexure2a_json_response(['success' => true, 'message' => $message, 'status' => $status, 'workflow_status' => $wf_status]);
} catch (Throwable $e) {
    error_log('[save_annexure2a] Catch: ' . $e->getMessage());
    annexure2a_json_response([
        'success' => false,
        'message' => 'Server error while saving contractor registration.',
        'error' => $e->getMessage()
    ], 500);
}
?>
