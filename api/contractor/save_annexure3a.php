<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json; charset=utf-8');

function failJson($message, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function successJson($payload) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
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
    error_log('[save_annexure3a] Uncaught: ' . $e->getMessage());
    failJson('Server error while saving Annexure 3A: ' . $e->getMessage(), 500);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[save_annexure3a] Fatal: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        failJson('Fatal server error while saving Annexure 3A: ' . $error['message'], 500);
    }
});

try {
include '../../include/config.php';

// include/session.php installs diagnostic handlers; restore JSON handlers for this API.
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    error_log('[save_annexure3a] Uncaught: ' . $e->getMessage());
    failJson('Server error while saving Annexure 3A: ' . $e->getMessage(), 500);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failJson('Invalid request method', 405);
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    failJson('Unauthorized', 401);
}

$role = $_SESSION['role'] ?? '';
$is_customer_submission = $role === 'customer';
$vendor_code = trim($_POST['vendor_code'] ?? $_SESSION['contractor_id'] ?? '');
$customer_code = trim($_POST['customer_code'] ?? $_SESSION['customer_code'] ?? '');
$work_order_no = $_POST['work_order_no'] ?? '';
$action = trim($_POST['action'] ?? 'submit');

function normalizeDateField($value, $label, $required = true) {
    $value = trim((string)($value ?? ''));

    if ($value === '') {
        if ($required) {
            failJson("$label is required. Please select a complete date.");
        }
        return null;
    }

    if (preg_match('/^\d{4}$/', $value)) {
        $year = (int)$value;
        if ($year < 1900 || $year > 2100) {
            failJson("$label invalid hai. Please complete date select karein (YYYY-MM-DD).");
        }
        return sprintf('%04d-12-31', $year);
    }

    if (preg_match('/^(\d{4})[-\/](\d{2})[-\/](\d{2})/', $value, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    $formats = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat('!' . $format, $value);
        $errors = DateTime::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if ($date && !$hasErrors && $date->format($format) === $value) {
            return $date->format('Y-m-d');
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    failJson("$label invalid hai. Please calendar se complete date select karein (YYYY-MM-DD).");
}

function a3TableExists(mysqli $conn, string $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    return $result && mysqli_num_rows($result) > 0;
}

function a3ColumnExistsInTable(mysqli $conn, string $table, string $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function a3EnsureColumnInTable(mysqli $conn, string $table, string $column, string $definition) {
    if (!a3ColumnExistsInTable($conn, $table, $column)) {
        $safeTable = str_replace('`', '``', $table);
        $safeColumn = str_replace('`', '``', $column);
        if (!mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
            throw new Exception("Missing DB column `$table.$column` and auto-create failed: " . mysqli_error($conn));
        }
    }
}

function a3ColumnExists(mysqli $conn, string $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM contractor_annexure3a LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function a3EnsureColumn(mysqli $conn, string $column, string $definition) {
    if (!a3ColumnExists($conn, $column)) {
        mysqli_query($conn, "ALTER TABLE contractor_annexure3a ADD COLUMN `$column` $definition");
    }
}

function a3EnsureSchema(mysqli $conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contractor_annexure3a (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_code VARCHAR(50) NOT NULL,
        customer_code VARCHAR(50) NULL,
        work_order_no VARCHAR(100) NULL,
        status VARCHAR(30) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    a3EnsureColumn($conn, 'work_awarding_department', 'VARCHAR(150) NULL');
    a3EnsureColumn($conn, 'epf_account_no', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'ecp_covered', 'VARCHAR(10) NULL');
    a3EnsureColumn($conn, 'epf_esi_exemption_reason', 'TEXT NULL');
    a3EnsureColumn($conn, 'ecp_details_json', 'TEXT NULL');
    a3EnsureColumn($conn, 'workers_proposed_to_be_engaged', 'INT NULL DEFAULT 0');
    a3EnsureColumn($conn, 'worker_category', 'VARCHAR(150) NULL');
    a3EnsureColumn($conn, 'license_details_json', 'TEXT NULL');
    a3EnsureColumn($conn, 'labour_license_appl_no', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'labour_identification_no', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'contact_person', 'VARCHAR(150) NULL');
    a3EnsureColumn($conn, 'mobile', 'VARCHAR(20) NULL');
    a3EnsureColumn($conn, 'vendor_mob2', 'VARCHAR(20) NULL');
    a3EnsureColumn($conn, 'remarks', 'TEXT NULL');
    a3EnsureColumn($conn, 'pin_code', 'VARCHAR(20) NULL');
    a3EnsureColumn($conn, 'is_epf_registered', 'TINYINT DEFAULT 0');
    a3EnsureColumn($conn, 'epf_code', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'is_esi_registered', 'TINYINT DEFAULT 0');
    a3EnsureColumn($conn, 'esi_code', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'insurance_policy_name', 'VARCHAR(255) NULL');
    a3EnsureColumn($conn, 'insurance_policy_no', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'insurance_validity', 'DATE NULL');
    a3EnsureColumn($conn, 'insurance_workers_count', 'INT DEFAULT 0');
    a3EnsureColumn($conn, 'labour_license_no', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'labour_license_issued_by', 'VARCHAR(200) NULL');
    a3EnsureColumn($conn, 'labour_license_issue_date', 'DATE NULL');
    a3EnsureColumn($conn, 'labour_license_expiry_date', 'DATE NULL');
    a3EnsureColumn($conn, 'wage_declaration', 'TEXT NULL');
    a3EnsureColumn($conn, 'salary_category', 'VARCHAR(100) NULL');
    a3EnsureColumn($conn, 'skilled_workers', 'INT DEFAULT 0');
    a3EnsureColumn($conn, 'semi_skilled_workers', 'INT DEFAULT 0');
    a3EnsureColumn($conn, 'unskilled_workers', 'INT DEFAULT 0');
    a3EnsureColumn($conn, 'total_workers', 'INT DEFAULT 0');
    a3EnsureColumn($conn, 'status', "VARCHAR(30) DEFAULT 'pending'");
    a3EnsureColumn($conn, 'created_by', 'INT NULL');
    a3EnsureColumn($conn, 'updated_by', 'INT NULL');
    a3EnsureColumn($conn, 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contractor_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NULL,
        annexure3a_id INT NULL,
        doc_type VARCHAR(100) NULL,
        file_path VARCHAR(255) NULL,
        original_name VARCHAR(255) NULL,
        status VARCHAR(30) DEFAULT 'pending',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_annexure3a (annexure3a_id),
        INDEX idx_contractor (contractor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    a3EnsureColumnInTable($conn, 'contractor_documents', 'contractor_id', 'INT NULL');
    a3EnsureColumnInTable($conn, 'contractor_documents', 'annexure3a_id', 'INT NULL');
    a3EnsureColumnInTable($conn, 'contractor_documents', 'doc_type', 'VARCHAR(100) NULL');
    a3EnsureColumnInTable($conn, 'contractor_documents', 'file_path', 'VARCHAR(255) NULL');
    a3EnsureColumnInTable($conn, 'contractor_documents', 'original_name', 'VARCHAR(255) NULL');
    a3EnsureColumnInTable($conn, 'contractor_documents', 'status', "VARCHAR(30) DEFAULT 'pending'");
    a3EnsureColumnInTable($conn, 'contractor_documents', 'uploaded_at', 'TIMESTAMP NULL DEFAULT NULL');
    a3EnsureColumnInTable($conn, 'contractor_documents', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contractor_annexure3a_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        annexure3a_id INT NULL,
        vendor_code VARCHAR(50) NULL,
        customer_code VARCHAR(50) NULL,
        work_order_no VARCHAR(100) NULL,
        insurance_policy_no VARCHAR(255) NULL,
        insurance_validity DATE NULL,
        insurance_workers_count INT DEFAULT 0,
        status VARCHAR(30) NULL,
        reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_annexure3a (annexure3a_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'annexure3a_id', 'INT NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'vendor_code', 'VARCHAR(50) NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'customer_code', 'VARCHAR(50) NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'work_order_no', 'VARCHAR(100) NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'insurance_policy_no', 'VARCHAR(255) NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'insurance_validity', 'DATE NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'insurance_workers_count', 'INT DEFAULT 0');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'status', 'VARCHAR(30) NULL');
    a3EnsureColumnInTable($conn, 'contractor_annexure3a_history', 'reason', 'TEXT NULL');

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS application_workflow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NOT NULL UNIQUE,
        contractor_id INT NULL,
        current_stage VARCHAR(100) NULL,
        overall_status VARCHAR(50) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    a3EnsureColumnInTable($conn, 'application_workflow', 'application_id', 'VARCHAR(50) NULL');
    a3EnsureColumnInTable($conn, 'application_workflow', 'contractor_id', 'INT NULL');
    a3EnsureColumnInTable($conn, 'application_workflow', 'current_stage', 'VARCHAR(100) NULL');
    a3EnsureColumnInTable($conn, 'application_workflow', 'overall_status', 'VARCHAR(50) NULL');
    a3EnsureColumnInTable($conn, 'application_workflow', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
}

function a3EnsureUploadDir($dir) {
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        failJson('Upload folder create nahi ho pa raha. Linux server par uploads/contractor_docs permission check karein.');
    }
    if (!is_writable($dir)) {
        failJson('Upload folder writable nahi hai. Linux server par uploads/contractor_docs folder ko web server user ke liye writable karein.');
    }
}

a3EnsureSchema($conn);

if (!$is_customer_submission && empty($vendor_code)) {
    failJson('Contractor session (Vendor Code) missing');
}
if ($is_customer_submission && empty($customer_code)) {
    failJson('Customer session (Customer Code) missing');
}

$storage_code = $vendor_code !== '' ? $vendor_code : ('customer_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $customer_code ?: (string)$user_id));

$c_master = $vendor_code !== '' ? db_single($conn, "SELECT id FROM contractors WHERE vendor_code = ?", 's', [$vendor_code]) : null;
$contractor_id = $c_master ? $c_master['id'] : 0;
$edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;
$existing_a3_row = null;
$limited_existing_edit = false;
$approved_limited_edit = false;

if ($edit_id) {
    if ($is_customer_submission) {
        $existing_lock = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE id = ? AND customer_code = ?", 'is', [$edit_id, $customer_code]);
    } else {
        $existing_lock = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE id = ? AND vendor_code = ?", 'is', [$edit_id, $vendor_code]);
    }
    $existing_a3_row = $existing_lock;
    $existing_status = strtolower($existing_lock['status'] ?? '');
    $limited_existing_edit = in_array($existing_status, ['approved', 'pending', 'submitted', 'resubmitted', 'under_review', 'hold'], true);
    $approved_limited_edit = $existing_status === 'approved';
} elseif (!empty($work_order_no)) {
    if ($is_customer_submission) {
        $existing_submission = db_single($conn, "SELECT id, status FROM contractor_annexure3a WHERE customer_code = ? AND work_order_no = ? ORDER BY id DESC LIMIT 1", 'ss', [$customer_code, $work_order_no]);
    } else {
        $existing_submission = db_single($conn, "SELECT id, status FROM contractor_annexure3a WHERE vendor_code = ? AND work_order_no = ? ORDER BY id DESC LIMIT 1", 'ss', [$vendor_code, $work_order_no]);
    }
    if ($existing_submission) {
        $existing_status = strtolower($existing_submission['status'] ?? '');
        $edit_id = intval($existing_submission['id']);
        $existing_a3_row = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE id = ?", 'i', [$edit_id]);
        $limited_existing_edit = in_array($existing_status, ['approved', 'pending', 'submitted', 'resubmitted', 'under_review', 'hold'], true);
        $approved_limited_edit = $existing_status === 'approved';
    }
}

// Capture Basic + Annexure 2A style registration fields
$pin_code = $_POST['pin_code'] ?? '';
$work_awarding_department = trim($_POST['work_awarding_department'] ?? '');

// Capture Statutory Fields
$epf_registered_raw = $_POST['epf_registered'] ?? ($_POST['is_epf_registered'] ?? '0');
$is_epf_registered = ($epf_registered_raw === 'YES' || $epf_registered_raw === '1' || $epf_registered_raw === 1) ? 1 : 0;
$epf_code = $is_epf_registered ? trim($_POST['epf_code'] ?? '') : '';
$epf_account_no = $is_epf_registered ? trim($_POST['epf_account_no'] ?? '') : '';

$clean_reason_input = function($value) {
    $value = trim((string)$value);
    do {
        $old = $value;
        $value = preg_replace('/^(EPF Reason|ESI Reason|EC Policy Reason):\s*/i', '', $value);
        $value = trim($value);
    } while ($value !== $old);
    return $value;
};

$epf_non_registration_reason = $clean_reason_input($_POST['epf_non_registration_reason'] ?? '');

$esi_registered_raw = $_POST['esi_registered'] ?? ($_POST['is_esi_registered'] ?? '0');
$is_esi_registered = ($esi_registered_raw === 'YES' || $esi_registered_raw === '1' || $esi_registered_raw === 1) ? 1 : 0;
$esi_code = $is_esi_registered ? trim($_POST['esi_code'] ?? '') : '';
$ecp_covered = trim($_POST['ecp_covered'] ?? 'NO');
$esi_non_registration_reason = $clean_reason_input($_POST['esi_non_registration_reason'] ?? '');
$ecp_exemption_reason = $clean_reason_input($_POST['ecp_exemption_reason'] ?? '');
$epf_esi_exemption_reason = trim($_POST['epf_esi_exemption_reason'] ?? '');

// Capture Insurance & License
$insurance_policy_name = trim($_POST['insurance_policy_name'] ?? 'Employee Compensation Policy');
$insurance_policy_no = trim($_POST['insurance_policy_no'] ?? '');
$insurance_validity = normalizeDateField($_POST['insurance_validity'] ?? null, 'Insurance Validity Date', false);
$insurance_workers_count = intval($_POST['insurance_workers_count'] ?? 0);

$labour_license_no = trim($_POST['labour_license_no'] ?? '');
$labour_license_issued_by = trim($_POST['labour_license_issued_by'] ?? '');
$labour_license_issue_date = normalizeDateField($_POST['labour_license_issue_date'] ?? null, 'Labour License Issue Date', false);
$labour_license_expiry_date = normalizeDateField($_POST['labour_license_expiry_date'] ?? null, 'Labour License Expiry Date', false);

// Capture Wage Declaration
$wage_declaration = trim($_POST['wage_declaration'] ?? '');
$wage_category = trim($_POST['wage_category'] ?? '');
$salary_category = trim($_POST['salary_category'] ?? ($_POST['wage_category'] ?? ''));
$workers_proposed_to_be_engaged = max(0, intval($_POST['workers_proposed_to_be_engaged'] ?? $_POST['total_workers'] ?? 0));
$worker_categories = $_POST['worker_categories'] ?? [];
if (!is_array($worker_categories)) $worker_categories = [];
$worker_category = implode(',', array_map('trim', $worker_categories));

$skilled_workers = in_array('Skilled', $worker_categories, true) ? $workers_proposed_to_be_engaged : max(0, intval($_POST['skilled_workers'] ?? 0));
$semi_skilled_workers = (in_array('Semiskilled', $worker_categories, true) || in_array('Semi-Skilled', $worker_categories, true)) ? 0 : max(0, intval($_POST['semi_skilled_workers'] ?? 0));
$unskilled_workers = in_array('Unskilled', $worker_categories, true) ? 0 : max(0, intval($_POST['unskilled_workers'] ?? 0));
$total_workers = $workers_proposed_to_be_engaged ?: ($skilled_workers + $semi_skilled_workers + $unskilled_workers);
if ($insurance_workers_count < 1) {
    $insurance_workers_count = $total_workers;
}

$labour_license_appl_no = trim($_POST['labour_license_appl_no'] ?? '');
$labour_identification_no = trim($_POST['labour_identification_no'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$vendor_mob2 = trim($_POST['vendor_mob2'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

$ecps = [];
$ecp_numbers = $_POST['ecp_number'] ?? [];
$ecp_valid_froms = $_POST['ecp_valid_from'] ?? [];
$ecp_valid_tos = $_POST['ecp_valid_to'] ?? [];
$ecp_insurers = $_POST['ecp_insurance_company'] ?? [];
$ecp_workers = $_POST['ecp_workers'] ?? [];
if ($ecp_covered === 'YES' && is_array($ecp_numbers)) {
    for ($i = 0; $i < count($ecp_numbers); $i++) {
        if (trim($ecp_numbers[$i] ?? '') !== '' || trim($ecp_valid_froms[$i] ?? '') !== '' || trim($ecp_valid_tos[$i] ?? '') !== '' || trim($ecp_insurers[$i] ?? '') !== '' || trim((string)($ecp_workers[$i] ?? '')) !== '') {
            $ecps[] = [
                'ecp_number' => trim($ecp_numbers[$i] ?? ''),
                'ecp_valid_from' => trim($ecp_valid_froms[$i] ?? ''),
                'ecp_valid_to' => trim($ecp_valid_tos[$i] ?? ''),
                'insurance_company' => trim($ecp_insurers[$i] ?? ''),
                'workers_under_policy' => max(0, intval($ecp_workers[$i] ?? 0))
            ];
        }
    }
}
$ecp_details_json = $ecps ? json_encode($ecps, JSON_UNESCAPED_SLASHES) : null;
if ($ecps) {
    $insurance_policy_name = $ecps[0]['insurance_company'] ?: $insurance_policy_name;
    $insurance_policy_no = $ecps[0]['ecp_number'] ?: $insurance_policy_no;
    $insurance_validity = !empty($ecps[0]['ecp_valid_to']) ? normalizeDateField($ecps[0]['ecp_valid_to'], 'EC Policy Valid To', false) : $insurance_validity;
    if (($insurance_workers_count ?? 0) < 1) {
        $insurance_workers_count = max(0, intval($ecps[0]['workers_under_policy'] ?? 0));
    }
}

$reasonParts = [];
if (!$is_epf_registered && $epf_non_registration_reason !== '') $reasonParts[] = 'EPF Reason: ' . $epf_non_registration_reason;
if (!$is_esi_registered && $esi_non_registration_reason !== '') $reasonParts[] = 'ESI Reason: ' . $esi_non_registration_reason;
if ($ecp_covered === 'NO' && $ecp_exemption_reason !== '') $reasonParts[] = 'EC Policy Reason: ' . $ecp_exemption_reason;
if (!empty($reasonParts)) {
    $epf_esi_exemption_reason = implode("\n", $reasonParts);
}

if ($wage_declaration !== '' && $wage_declaration !== 'I declare to pay minimum wage as per government norms' && stripos($wage_declaration, 'minimum wage') === false) {
    $wage_declaration = 'I declare to pay minimum wage as per government norms';
}
if ($salary_category === '' && $wage_category !== '') {
    $salary_category = $wage_category;
}

if ($limited_existing_edit && $existing_a3_row) {
    $pin_code = $existing_a3_row['pin_code'] ?? $pin_code;
    $work_awarding_department = $existing_a3_row['work_awarding_department'] ?? $work_awarding_department;
    $is_epf_registered = intval($existing_a3_row['is_epf_registered'] ?? $is_epf_registered);
    $epf_code = $existing_a3_row['epf_code'] ?? $epf_code;
    $epf_account_no = $existing_a3_row['epf_account_no'] ?? $epf_account_no;
    $is_esi_registered = intval($existing_a3_row['is_esi_registered'] ?? $is_esi_registered);
    $esi_code = $existing_a3_row['esi_code'] ?? $esi_code;
    $work_order_no = $existing_a3_row['work_order_no'] ?? $work_order_no;
    $wage_declaration = $existing_a3_row['wage_declaration'] ?? $wage_declaration;
    $salary_category = $existing_a3_row['salary_category'] ?? $salary_category;
    $skilled_workers = intval($existing_a3_row['skilled_workers'] ?? $skilled_workers);
    $semi_skilled_workers = intval($existing_a3_row['semi_skilled_workers'] ?? $semi_skilled_workers);
    $unskilled_workers = intval($existing_a3_row['unskilled_workers'] ?? $unskilled_workers);
    $total_workers = intval($existing_a3_row['total_workers'] ?? $total_workers);
    $workers_proposed_to_be_engaged = intval($existing_a3_row['workers_proposed_to_be_engaged'] ?? $workers_proposed_to_be_engaged);
    $worker_category = $existing_a3_row['worker_category'] ?? $worker_category;
    $labour_license_appl_no = $existing_a3_row['labour_license_appl_no'] ?? $labour_license_appl_no;
    $labour_identification_no = $existing_a3_row['labour_identification_no'] ?? $labour_identification_no;
    $contact_person = $existing_a3_row['contact_person'] ?? $contact_person;
    $mobile = $existing_a3_row['mobile'] ?? $mobile;
    $vendor_mob2 = $existing_a3_row['vendor_mob2'] ?? $vendor_mob2;
    $remarks = $existing_a3_row['remarks'] ?? $remarks;

    $existing_reason = $existing_a3_row['epf_esi_exemption_reason'] ?? '';
    $reasonParts = [];
    if (!$is_epf_registered && preg_match('/EPF Reason:\s*(.*?)(?=\n[A-Z][A-Za-z ]+ Reason:|$)/s', $existing_reason, $m)) {
        $reasonParts[] = 'EPF Reason: ' . trim($m[1]);
    }
    if (!$is_esi_registered && preg_match('/ESI Reason:\s*(.*?)(?=\n[A-Z][A-Za-z ]+ Reason:|$)/s', $existing_reason, $m)) {
        $reasonParts[] = 'ESI Reason: ' . trim($m[1]);
    }
    if ($ecp_covered === 'NO' && $ecp_exemption_reason !== '') {
        $reasonParts[] = 'EC Policy Reason: ' . $ecp_exemption_reason;
    }
    $epf_esi_exemption_reason = $reasonParts ? implode("\n", $reasonParts) : $existing_reason;

    if (empty($ecps) && !empty($existing_a3_row['ecp_details_json'])) {
        $existing_ecps = json_decode($existing_a3_row['ecp_details_json'], true);
        if (is_array($existing_ecps)) {
            $ecps = $existing_ecps;
            $ecp_details_json = json_encode($ecps, JSON_UNESCAPED_SLASHES);
            if (!empty($ecps[0])) {
                $insurance_policy_name = $ecps[0]['insurance_company'] ?? $insurance_policy_name;
                $insurance_policy_no = $ecps[0]['ecp_number'] ?? $insurance_policy_no;
                $insurance_validity = !empty($ecps[0]['ecp_valid_to']) ? normalizeDateField($ecps[0]['ecp_valid_to'], 'EC Policy Valid To', false) : $insurance_validity;
                $insurance_workers_count = intval($ecps[0]['workers_under_policy'] ?? $insurance_workers_count);
            }
        }
    }
}

// Validation
$is_final_submit = in_array($action, ['submit', 'resubmit'], true);

if (!$limited_existing_edit && $is_final_submit && $is_epf_registered && empty($epf_code)) {
    failJson('EPF Establishment Code is mandatory if registered.');
}
if (!$limited_existing_edit && $is_final_submit && $is_esi_registered && empty($esi_code)) {
    failJson('ESI Establishment Code is mandatory if registered.');
}
if ($is_final_submit && !$is_esi_registered && $ecp_covered !== 'YES') {
    failJson('Either ESI or EC Policy is mandatory');
}
if ($is_final_submit && $ecp_covered === 'YES' && empty($ecps)) {
    failJson('Please add at least one Employee Compensation Policy row.');
}
if ($is_final_submit && $ecp_covered === 'YES') {
    foreach ($ecps as $policy) {
        if (empty($policy['ecp_number']) || empty($policy['ecp_valid_from']) || empty($policy['ecp_valid_to'])) {
            failJson('EC Policy Number, Valid From and Valid To are mandatory when EC Policy is Yes.');
        }
        if (intval($policy['workers_under_policy'] ?? 0) <= 0) {
            failJson('Please enter Number of Workers Under EC Policy.');
        }
    }
}
if (!$limited_existing_edit && $is_final_submit && $total_workers < 1) {
    failJson('At least one proposed worker is required.');
}
if (!$limited_existing_edit && $is_final_submit && empty($worker_categories)) {
    failJson('Please select at least one Category of Workmen.');
}
if (!$limited_existing_edit && $is_final_submit && (empty($contact_person) || !preg_match('/^[a-zA-Z\s]+$/', $contact_person))) {
    failJson('Name of Contact Person is mandatory and must contain alphabets only.');
}
if (!$limited_existing_edit && $is_final_submit && (empty($mobile) || !preg_match('/^[0-9]{10}$/', $mobile))) {
    failJson('Mobile Number must be exactly 10 digits.');
}
if (!$limited_existing_edit && $vendor_mob2 !== '' && !preg_match('/^[0-9]{10}$/', $vendor_mob2)) {
    failJson('Alternate Mobile Number must be exactly 10 digits.');
}
if (!$limited_existing_edit && $labour_identification_no !== '' && !preg_match('/^[0-9]+$/', $labour_identification_no)) {
    failJson('Labour Identification Number must contain digits only.');
}

$licenses = [];
$lic_nos = $_POST['license_no'] ?? [];
$lic_validities = $_POST['license_validity'] ?? [];
$lic_issued_bys = $_POST['license_issued'] ?? [];
$lic_issued_dates = $_POST['issued_date'] ?? [];
$lic_expiry_dates = $_POST['expiry_date'] ?? [];
$existing_license_files = $_POST['existing_license_file'] ?? [];
$upload_dir = "../../uploads/contractor_docs/" . $storage_code . "/";
a3EnsureUploadDir($upload_dir);
if (is_array($lic_nos)) {
    for ($i = 0; $i < count($lic_nos); $i++) {
        $file_path = $existing_license_files[$i] ?? '';
        if (isset($_FILES['license_file']['name'][$i]) && $_FILES['license_file']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['license_file']['name'][$i], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') failJson('Only PDF files are allowed for Labour License.');
            $file_name = 'labour_license_' . time() . '_' . $i . '.pdf';
            if (@move_uploaded_file($_FILES['license_file']['tmp_name'][$i], $upload_dir . $file_name)) {
                $file_path = "uploads/contractor_docs/" . $storage_code . "/" . $file_name;
            } else {
                failJson('Labour License PDF upload failed. Linux server par uploads/contractor_docs permission check karein.');
            }
        }
        $hasRow = trim($lic_nos[$i] ?? '') !== '' || trim($lic_validities[$i] ?? '') !== '' || trim($lic_issued_dates[$i] ?? '') !== '' || trim($lic_expiry_dates[$i] ?? '') !== '' || $file_path !== '';
        if ($hasRow) {
            $licenses[] = [
                'license_no' => trim($lic_nos[$i] ?? ''),
                'validity' => trim($lic_validities[$i] ?? ''),
                'license_issued' => trim($lic_issued_bys[$i] ?? ($lic_validities[$i] ?? '')),
                'issued_date' => trim($lic_issued_dates[$i] ?? ''),
                'expiry_date' => trim($lic_expiry_dates[$i] ?? ''),
                'file_path' => $file_path
            ];
        }
    }
}
$license_details_json = $licenses ? json_encode($licenses, JSON_UNESCAPED_SLASHES) : null;
if (!empty($licenses)) {
    $firstLicense = $licenses[0];
    $labour_license_no = $firstLicense['license_no'];
    $labour_license_issued_by = $firstLicense['license_issued'];
    $labour_license_issue_date = normalizeDateField($firstLicense['issued_date'] ?? null, 'Labour License Issue Date', false);
    $labour_license_expiry_date = normalizeDateField($firstLicense['expiry_date'] ?? null, 'Labour License Expiry Date', false);
} else {
    $labour_license_issue_date = null;
    $labour_license_expiry_date = null;
}
if ($is_final_submit && !empty($labour_license_issue_date) && !empty($labour_license_expiry_date) && strtotime($labour_license_expiry_date) <= strtotime($labour_license_issue_date)) {
    failJson('Labour License Expiry Date must be later than Issue Date.');
}

if ($limited_existing_edit && $existing_a3_row && empty($ecps)) {
    $insurance_policy_name = $existing_a3_row['insurance_policy_name'] ?? $insurance_policy_name;
    $insurance_policy_no = $existing_a3_row['insurance_policy_no'] ?? $insurance_policy_no;
    $insurance_validity = $existing_a3_row['insurance_validity'] ?? $insurance_validity;
    $insurance_workers_count = intval($existing_a3_row['insurance_workers_count'] ?? $insurance_workers_count);
}

if ($limited_existing_edit) {
    $record_status = in_array($action, ['submit', 'resubmit'], true) ? 'resubmitted' : strtolower($existing_a3_row['status'] ?? 'approved');
} else {
    $record_status = $action === 'draft' ? 'draft' : 'pending';
}

if ($edit_id) {
    // Update existing
    $owner_column = $is_customer_submission ? 'customer_code' : 'vendor_code';
    $owner_value = $is_customer_submission ? $customer_code : $vendor_code;
    $sql = "UPDATE contractor_annexure3a SET 
        pin_code = ?, work_awarding_department = ?, is_epf_registered = ?, epf_code = ?, epf_account_no = ?,
        is_esi_registered = ?, esi_code = ?, work_order_no = ?, 
        insurance_policy_name = ?, insurance_policy_no = ?, insurance_validity = ?, insurance_workers_count = ?,
        labour_license_no = ?, labour_license_issued_by = ?, labour_license_issue_date = ?, labour_license_expiry_date = ?,
        wage_declaration = ?, salary_category = ?, skilled_workers = ?, semi_skilled_workers = ?, unskilled_workers = ?, total_workers = ?,
        ecp_covered = ?, epf_esi_exemption_reason = ?, ecp_details_json = ?, workers_proposed_to_be_engaged = ?, worker_category = ?,
        license_details_json = ?, labour_license_appl_no = ?, labour_identification_no = ?, contact_person = ?, mobile = ?, vendor_mob2 = ?, remarks = ?,
        updated_by = ?, updated_at = NOW(), status = ?
        WHERE id = ? AND {$owner_column} = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        failJson('Unable to prepare Annexure 3A update: ' . $conn->error);
    }
    $stmt->bind_param(str_repeat('s', 38),
        $pin_code, $work_awarding_department, $is_epf_registered, $epf_code, $epf_account_no,
        $is_esi_registered, $esi_code, $work_order_no,
        $insurance_policy_name, $insurance_policy_no, $insurance_validity, $insurance_workers_count,
        $labour_license_no, $labour_license_issued_by, $labour_license_issue_date, $labour_license_expiry_date,
        $wage_declaration, $salary_category, $skilled_workers, $semi_skilled_workers, $unskilled_workers, $total_workers,
        $ecp_covered, $epf_esi_exemption_reason, $ecp_details_json, $workers_proposed_to_be_engaged, $worker_category,
        $license_details_json, $labour_license_appl_no, $labour_identification_no, $contact_person, $mobile, $vendor_mob2, $remarks,
        $user_id, $record_status, $edit_id, $owner_value
    );
    $success = $stmt->execute();
    $annexure3a_id = $edit_id;
} else {
    // Insert new
    $sql = "INSERT INTO contractor_annexure3a (
        vendor_code, customer_code, work_order_no, pin_code, work_awarding_department,
        is_epf_registered, epf_code, epf_account_no,
        is_esi_registered, esi_code, 
        insurance_policy_name, insurance_policy_no, insurance_validity, insurance_workers_count,
        labour_license_no, labour_license_issued_by, labour_license_issue_date, labour_license_expiry_date,
        wage_declaration, salary_category, skilled_workers, semi_skilled_workers, unskilled_workers, total_workers,
        ecp_covered, epf_esi_exemption_reason, ecp_details_json, workers_proposed_to_be_engaged, worker_category,
        license_details_json, labour_license_appl_no, labour_identification_no, contact_person, mobile, vendor_mob2, remarks,
        status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        failJson('Unable to prepare Annexure 3A insert: ' . $conn->error);
    }
    $stmt->bind_param(str_repeat('s', 38),
        $vendor_code, $customer_code, $work_order_no, $pin_code, $work_awarding_department,
        $is_epf_registered, $epf_code, $epf_account_no,
        $is_esi_registered, $esi_code,
        $insurance_policy_name, $insurance_policy_no, $insurance_validity, $insurance_workers_count,
        $labour_license_no, $labour_license_issued_by, $labour_license_issue_date, $labour_license_expiry_date,
        $wage_declaration, $salary_category, $skilled_workers, $semi_skilled_workers, $unskilled_workers, $total_workers,
        $ecp_covered, $epf_esi_exemption_reason, $ecp_details_json, $workers_proposed_to_be_engaged, $worker_category,
        $license_details_json, $labour_license_appl_no, $labour_identification_no, $contact_person, $mobile, $vendor_mob2, $remarks,
        $record_status, $user_id
    );
    $success = $stmt->execute();
    $annexure3a_id = $stmt->insert_id;
}

if ($success) {
    // Handle Document Uploads
    $upload_dir = "../../uploads/contractor_docs/" . $storage_code . "/";
    a3EnsureUploadDir($upload_dir);

    $doc_types = [
        'labour_license', 'insurance_policy', 'epf_challan', 'esi_challan', 
        'bank_details', 'pan', 'gst', 'agreement_copy'
    ];

    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    foreach ($doc_types as $type) {
        if (isset($_FILES[$type]) && $_FILES[$type]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$type];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed_extensions)) continue;
            if ($file['size'] > $max_size) continue;

            $file_name = $type . "_" . time() . "." . $ext;
            $dest = $upload_dir . $file_name;

            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                $db_path = "uploads/contractor_docs/" . $storage_code . "/" . $file_name;
                
                // Record in contractor_documents (Update if type already exists for this submission)
                $existing = db_single($conn, "SELECT id FROM contractor_documents WHERE annexure3a_id = ? AND doc_type = ?", 'is', [$annexure3a_id, $type]);
                if ($existing) {
                    db_execute($conn, "UPDATE contractor_documents SET file_path = ?, original_name = ?, status = 'pending', uploaded_at = NOW() WHERE id = ?", 'ssi', [$db_path, $file['name'], $existing['id']]);
                } else {
                    db_execute($conn, "INSERT INTO contractor_documents (contractor_id, annexure3a_id, doc_type, file_path, original_name, status) 
                        VALUES (?, ?, ?, ?, ?, 'pending')", 
                        'iisss', [$contractor_id, $annexure3a_id, $type, $db_path, $file['name']]);
                }
            } else {
                failJson(str_replace('_', ' ', strtoupper($type)) . ' upload failed. Linux server par uploads/contractor_docs permission check karein.');
            }
        }
    }

    // Update central workflow
    $app_no_row = $contractor_id ? db_single($conn, "SELECT application_no FROM contractors WHERE id = ?", 'i', [$contractor_id]) : null;
    if ($app_no_row && $app_no_row['application_no']) {
        $workflow_stage = $record_status === 'resubmitted' ? '3a_resubmitted' : '3a_submitted';
        db_execute($conn, "INSERT INTO application_workflow (application_id, contractor_id, current_stage, overall_status) 
            VALUES (?, ?, ?, 'pending') 
            ON DUPLICATE KEY UPDATE current_stage = VALUES(current_stage), overall_status = 'pending', updated_at = NOW()", 
            'sis', [$app_no_row['application_no'], $contractor_id, $workflow_stage]);
    }

    // Log Annexure 3A Submission History
    if (empty($customer_code) && $edit_id) {
        $existing_a3 = db_single($conn, "SELECT customer_code, work_order_no FROM contractor_annexure3a WHERE id = ?", 'i', [$edit_id]);
        if ($existing_a3) {
            $customer_code = $existing_a3['customer_code'];
            if (empty($work_order_no)) {
                $work_order_no = $existing_a3['work_order_no'];
            }
        }
    }
    db_execute($conn,
        "INSERT INTO contractor_annexure3a_history (annexure3a_id, vendor_code, customer_code, work_order_no, insurance_policy_no, insurance_validity, insurance_workers_count, status, reason) VALUES (?,?,?,?,?,?,?,?,?)",
        'isssssiss',
        [$annexure3a_id, $vendor_code, $customer_code, $work_order_no, $insurance_policy_name . " (" . $insurance_policy_no . ")", $insurance_validity, $insurance_workers_count, $record_status === 'resubmitted' ? 'resubmitted' : 'submitted', $record_status === 'resubmitted' ? 'Resubmitted EC / Labour License' : 'Submitted/Updated']
    );

    successJson([
        'success' => true,
        'message' => $action === 'draft' ? 'Annexure 3A draft saved successfully' : ($action === 'resubmit' ? 'Annexure 3A resubmitted successfully' : 'Annexure 3A and compliance documents submitted successfully'),
        'status' => $record_status,
        'id' => $annexure3a_id
    ]);
} else {
    $error = $stmt->error;
    if (stripos($error, 'Incorrect date value') !== false && stripos($error, 'insurance_validity') !== false) {
        failJson('Insurance Validity Date invalid hai. Please complete date select karein. Agar sirf year diya gaya hai to system usse year-end date me save karega.');
    }
    failJson('Execution error: ' . $error);
}
} catch (Throwable $e) {
    error_log('[save_annexure3a] Catch: ' . $e->getMessage());
    failJson('Server error while saving Annexure 3A: ' . $e->getMessage(), 500);
}
?>
