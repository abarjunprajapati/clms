<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

function worker4a_json($payload, $statusCode = 200) {
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
    error_log('[SAVE_WORKER_4A] Uncaught: ' . $e->getMessage());
    worker4a_json(["success" => false, "message" => $e->getMessage()]);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[SAVE_WORKER_4A] Fatal: ' . $error['message']);
        worker4a_json(["success" => false, "message" => "Fatal server error: " . $error['message']]);
    }
});

try {
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/customer_portal_context.php';
require_once __DIR__ . '/../include/wage_settings.php';
require_once __DIR__ . '/../include/training_flow.php';
require_once __DIR__ . '/../include/age_range_mapping.php';
require_once __DIR__ . '/../include/payment_flow.php';
require_once __DIR__ . '/api_helper.php';

// include/session.php installs diagnostic handlers; restore JSON handlers for this API.
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    error_log('[SAVE_WORKER_4A] Uncaught: ' . $e->getMessage());
    worker4a_json(["success" => false, "message" => $e->getMessage()]);
});

clms_get_portal_contractor($conn);

function table_column_meta($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        throw new Exception("Invalid table name.");
    }

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$table`");
    if (!$result) {
        throw new Exception("Unable to read schema for $table: " . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    $cache[$table] = $columns;
    return $columns;
}

function table_columns($conn, $table) {
    return array_fill_keys(array_keys(table_column_meta($conn, $table)), true);
}

function filter_table_row($conn, $table, $row) {
    $meta = table_column_meta($conn, $table);
    $row = array_intersect_key($row, array_fill_keys(array_keys($meta), true));

    foreach ($row as $column => $value) {
        $type = strtolower((string)($meta[$column]['Type'] ?? ''));
        $isDateLike = strpos($type, 'date') !== false || strpos($type, 'time') !== false || strpos($type, 'timestamp') !== false;
        if ($isDateLike && ($value === '' || (is_string($value) && trim($value) === ''))) {
            $row[$column] = null;
        }
    }

    return $row;
}

function normalize_skill_category($skill) {
    $value = trim((string)$skill);
    // Truncate to prevent MySQL truncation error
    $value = substr($value, 0, 150);
    $normalized = strtolower(str_replace(['_', '-'], ' ', $value));
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    if ($normalized === 'semi skilled') return 'Semi Skilled';
    if ($normalized === 'skilled') return 'Skilled';
    if ($normalized === 'unskilled' || $normalized === 'un skilled') return 'Unskilled';

    return $value;
}

function table_has_auto_increment_id($conn, $table) {
    $meta = table_column_meta($conn, $table);
    return isset($meta['id']) && stripos($meta['id']['Extra'] ?? '', 'auto_increment') !== false;
}

function next_manual_id($conn, $table) {
    $safeTable = str_replace('`', '``', $table);
    $result = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `$safeTable`");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int)($row['next_id'] ?? 1);
}

function insert_table_row($conn, $table, $row) {
    $row = filter_table_row($conn, $table, $row);
    if (empty($row)) {
        throw new Exception("No compatible columns found for $table.");
    }

    $meta = table_column_meta($conn, $table);
    if (
        isset($meta['id']) &&
        (!isset($row['id']) || (string)$row['id'] === '' || (string)$row['id'] === '0') &&
        !table_has_auto_increment_id($conn, $table)
    ) {
        $row = ['id' => (string)next_manual_id($conn, $table)] + $row;
    }

    $cols = array_keys($row);
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $quotedCols = '`' . implode('`,`', $cols) . '`';
    $sql = "INSERT INTO `$table` ($quotedCols) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("$table insert prepare failed: " . $conn->error);
    }

    $values = array_values($row);
    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        if (stripos($error, "Duplicate entry '0'") !== false && isset($meta['id']) && !table_has_auto_increment_id($conn, $table)) {
            $row['id'] = (string)next_manual_id($conn, $table);
            return insert_table_row($conn, $table, $row);
        }
        throw new Exception("$table insert failed: " . $error);
    }

    $insertId = (int)$stmt->insert_id;
    if (!$insertId && isset($row['id'])) {
        $insertId = (int)$row['id'];
    }
    $stmt->close();
    return $insertId;
}

function update_table_row_by_id($conn, $table, $id, $row) {
    $row = filter_table_row($conn, $table, $row);
    unset($row['id']);
    if (empty($row)) return true;

    $sets = [];
    foreach (array_keys($row) as $col) {
        $sets[] = "`$col` = ?";
    }
    $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("$table update prepare failed: " . $conn->error);
    }

    $values = array_values($row);
    $values[] = $id;
    $types = str_repeat('s', count($row)) . 'i';
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("$table update failed: " . $error);
    }
    $stmt->close();
    return true;
}

function worker4a_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    return $result && mysqli_num_rows($result) > 0;
}

function worker4a_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function worker4a_contractor_select_expr($conn) {
    $columns = ['id', 'application_no', 'work_order_no', 'status', 'vendor_code', 'contractor_id', 'sap_code', 'user_id'];
    $parts = [];
    foreach ($columns as $column) {
        $safeColumn = str_replace('`', '``', $column);
        if (worker4a_column_exists($conn, 'contractors', $column)) {
            $parts[] = "`$safeColumn` AS `$safeColumn`";
        } else {
            $parts[] = "NULL AS `$safeColumn`";
        }
    }
    return implode(', ', $parts);
}

function worker4a_get_contractor_row($conn, $data) {
    if (!worker4a_table_exists($conn, 'contractors')) {
        return null;
    }

    $select = worker4a_contractor_select_expr($conn);
    $postedId = (int)($data['contractor_id'] ?? 0);
    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    $sessionVendor = trim((string)($_SESSION['contractor_id'] ?? ($_SESSION['vendor_code'] ?? '')));
    $workOrderNo = trim((string)($data['work_order_no'] ?? ''));

    if ($postedId > 0) {
        $row = db_single($conn, "SELECT $select FROM contractors WHERE id = ? LIMIT 1", 'i', [$postedId]);
        if ($row) {
            $rowUserId = (int)($row['user_id'] ?? 0);
            $rowVendorValues = array_filter([
                trim((string)($row['vendor_code'] ?? '')),
                trim((string)($row['contractor_id'] ?? '')),
                trim((string)($row['sap_code'] ?? '')),
            ]);
            $vendorMatches = $sessionVendor !== '' && in_array($sessionVendor, $rowVendorValues, true);
            $userMatches = $sessionUserId > 0 && $rowUserId === $sessionUserId;
            $workMatches = $workOrderNo !== '' && trim((string)($row['work_order_no'] ?? '')) === $workOrderNo;
            if ($userMatches || $vendorMatches || $workMatches) {
                return $row;
            }
        }
    }

    if ($sessionVendor !== '') {
        $where = [];
        $params = [];
        $types = '';
        foreach (['vendor_code', 'contractor_id', 'sap_code'] as $column) {
            if (worker4a_column_exists($conn, 'contractors', $column)) {
                $where[] = "`$column` = ?";
                $params[] = $sessionVendor;
                $types .= 's';
            }
        }
        if ($where) {
            $row = db_single($conn, "SELECT $select FROM contractors WHERE " . implode(' OR ', $where) . " ORDER BY id DESC LIMIT 1", $types, $params);
            if ($row) return $row;
        }
    }

    if ($sessionUserId > 0 && worker4a_column_exists($conn, 'contractors', 'user_id')) {
        $row = db_single($conn, "SELECT $select FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$sessionUserId]);
        if ($row) return $row;
    }

    if ($workOrderNo !== '' && worker4a_column_exists($conn, 'contractors', 'work_order_no')) {
        return db_single($conn, "SELECT $select FROM contractors WHERE work_order_no = ? ORDER BY id DESC LIMIT 1", 's', [$workOrderNo]);
    }

    return null;
}

function worker4a_limit_type_from_value($value) {
    $raw = strtolower(trim((string)$value));
    if (strpos($raw, 'supervisor') !== false) return 'Supervisor';
    if (strpos($raw, 'representative') !== false) return 'Representative';
    if (strpos($raw, 'contractor') !== false) return 'Contractor';
    return 'Workman';
}

function worker4a_status_counts_for_limit($status) {
    $status = strtolower(trim((string)$status));
    return !in_array($status, ['draft', 'rejected', 'removed', 'inactive', 'deleted', 'blocked'], true);
}

function worker4a_ensure_column($conn, $table, $column, $definition) {
    if (worker4a_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    if (!mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
        throw new Exception("DB column `$table.$column` missing and auto-create failed: " . mysqli_error($conn));
    }
}

function worker4a_ensure_schema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS workmen (
        id INT NOT NULL,
        contractor_id INT NULL,
        name VARCHAR(200) NULL,
        aadhaar VARCHAR(20) NULL,
        status ENUM('draft','pending','active','inactive','blocked','temp','trained','verified','acc_generated','temporary_issued','permanent_issued') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $workmenColumns = [
        'application_no' => 'VARCHAR(50) NULL',
        'work_order_no' => 'VARCHAR(100) NULL',
        'project_name' => 'VARCHAR(150) NULL',
        'contractor_id' => 'INT NULL',
        'name' => 'VARCHAR(200) NULL',
        'father_name' => 'VARCHAR(200) NULL',
        'dob' => 'DATE NULL',
        'gender' => 'VARCHAR(20) NULL',
        'marital_status' => 'VARCHAR(30) NULL',
        'nationality' => "VARCHAR(100) NULL DEFAULT 'Indian'",
        'aadhaar' => 'VARCHAR(20) NULL',
        'mobile' => 'VARCHAR(20) NULL',
        'emergency_contact' => 'VARCHAR(20) NULL',
        'permanent_address' => 'TEXT NULL',
        'present_address' => 'TEXT NULL',
        'state' => 'VARCHAR(100) NULL',
        'district' => 'VARCHAR(100) NULL',
        'pincode' => 'VARCHAR(20) NULL',
        'region' => 'VARCHAR(100) NULL',
        'pwd_status' => 'VARCHAR(10) NULL',
        'passport_no' => 'VARCHAR(50) NULL',
        'driving_licence_no' => 'VARCHAR(50) NULL',
        'email' => 'VARCHAR(150) NULL',
        'contact_email' => 'VARCHAR(150) NULL',
        'dcate' => 'VARCHAR(100) NULL',
        'blood_group' => 'VARCHAR(10) NULL',
        'education' => 'VARCHAR(150) NULL',
        'skill' => 'VARCHAR(150) NULL',
        'skill_category' => 'VARCHAR(150) NULL',
        'role_type' => 'VARCHAR(150) NULL',
        'trade' => 'VARCHAR(150) NULL',
        'department' => 'VARCHAR(150) NULL',
        'nature_of_work' => 'VARCHAR(200) NULL',
        'bank_account' => 'VARCHAR(50) NULL',
        'ifsc' => 'VARCHAR(20) NULL',
        'pf_no' => 'VARCHAR(50) NULL',
        'epf_registered_worker' => 'VARCHAR(10) NULL',
        'esi_registered_worker' => 'VARCHAR(10) NULL',
        'uan_number' => 'VARCHAR(50) NULL',
        'esic_number' => 'VARCHAR(50) NULL',
        'experience' => 'VARCHAR(50) NULL',
        'certified_wage_rate' => 'VARCHAR(100) NULL',
        'safety_language' => 'VARCHAR(50) NULL',
        'training_approval_doc' => 'VARCHAR(255) NULL',
        'executing_officer_code' => 'VARCHAR(50) NULL',
        'executing_officer_name' => 'VARCHAR(200) NULL',
        'executing_officer_id' => 'BIGINT NULL',
        'execution_training_status' => "VARCHAR(30) DEFAULT 'pending'",
        'execution_training_remarks' => 'TEXT NULL',
        'execution_training_reviewed_by' => 'BIGINT NULL',
        'execution_training_reviewed_at' => 'DATETIME NULL',
        'photo' => 'VARCHAR(255) NULL',
        'education_doc' => 'VARCHAR(255) NULL',
        'educational_doc' => 'VARCHAR(255) NULL',
        'bank_doc' => 'VARCHAR(255) NULL',
        'gatepass_doc' => 'VARCHAR(255) NULL',
        'skill_cert_doc' => 'VARCHAR(255) NULL',
        'medical_doc' => 'VARCHAR(255) NULL',
        'police_doc' => 'VARCHAR(255) NULL',
        'insurance_doc' => 'VARCHAR(255) NULL',
        'aadhaar_doc' => 'VARCHAR(255) NULL',
        'signature_doc' => 'VARCHAR(255) NULL',
        'status' => "ENUM('draft','pending','active','inactive','blocked','temp','trained','verified','acc_generated','temporary_issued','permanent_issued') DEFAULT 'pending'",
        'training_status' => "VARCHAR(50) DEFAULT 'pending'",
        'worker_type' => 'VARCHAR(50) NULL',
        'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
        'source' => 'VARCHAR(50) NULL',
        'temp_id' => 'VARCHAR(50) NULL',
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($workmenColumns as $column => $definition) {
        worker4a_ensure_column($conn, 'workmen', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NULL,
        document_type VARCHAR(100) NULL,
        file_path VARCHAR(255) NULL,
        status VARCHAR(30) DEFAULT 'pending',
        uploaded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        'workman_id' => 'INT NULL',
        'document_type' => 'VARCHAR(100) NULL',
        'file_path' => 'VARCHAR(255) NULL',
        'status' => "VARCHAR(30) DEFAULT 'pending'",
        'uploaded_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        worker4a_ensure_column($conn, 'documents', $column, $definition);
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS application_workflow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) NULL,
        contractor_id INT NULL,
        current_stage VARCHAR(100) NULL,
        overall_status VARCHAR(50) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        'application_id' => 'VARCHAR(50) NULL',
        'contractor_id' => 'INT NULL',
        'current_stage' => 'VARCHAR(100) NULL',
        'overall_status' => 'VARCHAR(50) NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        worker4a_ensure_column($conn, 'application_workflow', $column, $definition);
    }
}

function worker4a_ensure_upload_dir($dir) {
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        throw new Exception("Upload folder create nahi ho pa raha. Linux server par uploads/workers permission check karein.");
    }
    if (!is_writable($dir)) {
        throw new Exception("Upload folder writable nahi hai. Linux server par uploads/workers folder permission check karein.");
    }
}

function worker4a_validate_dob_age($dob) {
    global $conn;

    $dob = trim((string)$dob);
    if ($dob === '') return;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        throw new Exception("Date of Birth year must be 4 digits.");
    }
    $birth = DateTime::createFromFormat('Y-m-d', $dob);
    $errors = DateTime::getLastErrors();
    $warningCount = is_array($errors) ? (int)($errors['warning_count'] ?? 0) : 0;
    $errorCount = is_array($errors) ? (int)($errors['error_count'] ?? 0) : 0;
    if (!$birth || $warningCount > 0 || $errorCount > 0) {
        throw new Exception("Invalid Date of Birth.");
    }
    $today = new DateTime('today');
    $age = (int)$birth->diff($today)->y;
    $range = clms_get_active_age_range($conn);
    $minAge = (int)($range['min_age'] ?? 18);
    $maxAge = (int)($range['max_age'] ?? 60);
    if ($age < $minAge) {
        throw new Exception("Worker age is below {$minAge} years. Registration is not allowed.");
    }
    if ($age > $maxAge) {
        throw new Exception("Worker age is above {$maxAge} years. Registration is not allowed.");
    }
}

function worker4a_validate_upload_file($key, $file) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return;
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $size = (int)($file['size'] ?? 0);
    $tmp = $file['tmp_name'] ?? '';
    $mime = $tmp && is_file($tmp) ? (mime_content_type($tmp) ?: '') : '';

    if ($key === 'photo') {
        if (!in_array($ext, ['jpg', 'jpeg'], true) || ($mime !== '' && $mime !== 'image/jpeg')) {
            throw new Exception("Photo must be JPG/JPEG only.");
        }
        if ($size > 2 * 1024 * 1024) {
            throw new Exception("Photo size must be 2 MB or less.");
        }
        return;
    }

    if ($ext !== 'pdf' || ($mime !== '' && $mime !== 'application/pdf')) {
        throw new Exception("Documents must be PDF only.");
    }
    if ($size > 5 * 1024 * 1024) {
        throw new Exception("Document size must be 5 MB or less.");
    }
}

function worker4a_lookup_execution_officer($conn, $code) {
    $code = strtoupper(trim((string)$code));
    if ($code === '') return null;

    if (worker4a_table_exists($conn, 'users')) {
        $where = ["contractor_id = ?"];
        if (worker4a_column_exists($conn, 'users', 'employee_code')) {
            $where[] = "employee_code = ?";
        }
        $sql = "SELECT id, name, contractor_id"
             . (worker4a_column_exists($conn, 'users', 'employee_code') ? ", employee_code" : ", contractor_id AS employee_code")
             . " FROM users WHERE role = 'execution_officer' AND (" . implode(' OR ', $where) . ") LIMIT 1";
        $params = array_fill(0, count($where), $code);
        $user = db_single($conn, $sql, str_repeat('s', count($params)), $params);
        if ($user) {
            return [
                'id' => (int)$user['id'],
                'employee_code' => $user['employee_code'] ?: $user['contractor_id'],
                'name' => $user['name'] ?? '',
            ];
        }
    }

    if (worker4a_table_exists($conn, 'execution_officers')) {
        $officer = db_single($conn, "SELECT id, employee_code, name FROM execution_officers WHERE employee_code = ? LIMIT 1", 's', [$code]);
        if ($officer) {
            return [
                'id' => (int)$officer['id'],
                'employee_code' => $officer['employee_code'],
                'name' => $officer['name'] ?? '',
            ];
        }
    }

    foreach (['sap_employee_master', 'sap_employees', 'employee_master', 'sqlserver_employee_master'] as $table) {
        if (!worker4a_table_exists($conn, $table)) continue;
        $codeCol = worker4a_column_exists($conn, $table, 'employee_code') ? 'employee_code' : (worker4a_column_exists($conn, $table, 'e_code') ? 'e_code' : '');
        if ($codeCol === '') continue;
        $nameExpr = worker4a_column_exists($conn, $table, 'name') ? 'name' : (worker4a_column_exists($conn, $table, 'employee_name') ? 'employee_name' : "''");
        $safeTable = str_replace('`', '``', $table);
        $safeCodeCol = str_replace('`', '``', $codeCol);
        $row = db_single($conn, "SELECT `$safeCodeCol` AS employee_code, $nameExpr AS name FROM `$safeTable` WHERE `$safeCodeCol` = ? LIMIT 1", 's', [$code]);
        if ($row) {
            return [
                'id' => 0,
                'employee_code' => $row['employee_code'],
                'name' => $row['name'] ?? '',
            ];
        }
    }

    return null;
}

function worker4a_upsert_workflow($conn, $application_no, $contractor_id) {
    if (!worker4a_table_exists($conn, 'application_workflow')) return;

    $existing = null;
    if (worker4a_column_exists($conn, 'application_workflow', 'application_id')) {
        $safeApp = mysqli_real_escape_string($conn, $application_no);
        $result = mysqli_query($conn, "SELECT * FROM application_workflow WHERE application_id = '$safeApp' LIMIT 1");
        $existing = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    }

    $wfRow = [
        'application_id' => $application_no,
        'contractor_id' => $contractor_id,
        'current_stage' => 'enrolment_done',
        'overall_status' => 'enrolment_done',
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($existing && isset($existing['id'])) {
        update_table_row_by_id($conn, 'application_workflow', (int)$existing['id'], $wfRow);
    } elseif ($existing && worker4a_column_exists($conn, 'application_workflow', 'application_id')) {
        $updates = filter_table_row($conn, 'application_workflow', $wfRow);
        unset($updates['application_id']);
        $sets = [];
        foreach (array_keys($updates) as $column) {
            $sets[] = "`$column` = ?";
        }
        if ($sets) {
            $sql = "UPDATE application_workflow SET " . implode(', ', $sets) . " WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $values = array_values($updates);
                $values[] = $application_no;
                $stmt->bind_param(str_repeat('s', count($values)), ...$values);
                $stmt->execute();
                $stmt->close();
            }
        }
    } else {
        insert_table_row($conn, 'application_workflow', $wfRow);
    }
}

function worker4a_ensure_training_request($conn, $workman_id, $contractor_id, $requested_by = 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        training_type VARCHAR(100) NULL,
        requested_date DATE NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        remarks TEXT NULL,
        source VARCHAR(30) NULL,
        requested_by INT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        'training_type' => 'VARCHAR(100) NULL',
        'requested_date' => 'DATE NULL',
        'preferred_date' => 'DATE NULL',
        'preferred_shift' => "VARCHAR(20) DEFAULT 'morning'",
        'remarks' => 'TEXT NULL',
        'source' => 'VARCHAR(30) NULL',
        'requested_by' => 'INT NULL',
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        worker4a_ensure_column($conn, 'training_requests', $column, $definition);
    }

    $existing = db_single(
        $conn,
        "SELECT id FROM training_requests WHERE workman_id = ? AND status IN ('welfare_pending','pending','scheduled','contractor_confirmed','passed') ORDER BY id DESC LIMIT 1",
        'i',
        [$workman_id]
    );
    if ($existing) return;

    insert_table_row($conn, 'training_requests', [
        'workman_id' => $workman_id,
        'contractor_id' => $contractor_id,
        'training_type' => 'Safety Induction',
        'requested_date' => date('Y-m-d'),
        'preferred_date' => null,
        'preferred_shift' => 'morning',
        'remarks' => 'Auto-created after Executing Officer approval/document validation. Waiting for Welfare check.',
        'source' => 'enrolment',
        'requested_by' => $requested_by,
        'status' => 'welfare_pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

worker4a_ensure_schema($conn);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests are allowed.");
    }

    $data = $_POST;

    $action = strtolower(trim($data['action'] ?? 'submit'));

    if ($action !== 'draft' && (empty($data['name']) || empty($data['aadhaar']))) {
        throw new Exception("Name and Aadhaar Number are mandatory.");
    }
    if ($action !== 'draft') {
        worker4a_validate_dob_age($data['dob'] ?? '');
    }
    $executingOfficerCode = strtoupper(trim((string)($data['executing_officer_code'] ?? '')));
    $executingOfficer = null;
    if ($action !== 'draft') {
        if ($executingOfficerCode === '') {
            throw new Exception("Executing Officer E-Code is mandatory.");
        }
        $executingOfficer = worker4a_lookup_execution_officer($conn, $executingOfficerCode);
        if (!$executingOfficer) {
            throw new Exception("Executing Officer E-Code User Master/SAP/SQL Server mein nahi mila.");
        }
    } elseif ($executingOfficerCode !== '') {
        $executingOfficer = worker4a_lookup_execution_officer($conn, $executingOfficerCode);
    }

    // ========== ANNEXURE 5/A: PASS LIMIT VALIDATION ==========
    require_once __DIR__ . '/../include/pass_limit_validator.php';
    $pass_type_raw = $data['pass_type'] ?? 'Workman';
    // Normalize pass_type to match Annexure 5/A categories
    $limit_type = 'Workman';
    if (stripos($pass_type_raw, 'supervisor') !== false) $limit_type = 'Supervisor';
    elseif (stripos($pass_type_raw, 'representative') !== false) $limit_type = 'Representative';
    elseif (stripos($pass_type_raw, 'contractor') !== false) $limit_type = 'Contractor';

    $contractor_row = worker4a_get_contractor_row($conn, $data);
    $editing_worker_id = (int)($data['worker_id'] ?? 0);

    // ========== END ANNEXURE 5/A VALIDATION ==========

    $upload_dir = '../uploads/workers/';
    worker4a_ensure_upload_dir($upload_dir);

    $uploaded_files = [
        'photo' => '',
        'signature' => '',
        'aadhaar_doc' => '',
        'medical_doc' => '',
        'police_doc' => '',
        'insurance_doc' => '',
        'education_doc' => '',
        'bank_doc' => '',
        'gatepass_doc' => '',
        'skill_cert_doc' => ''
        ,'training_approval_doc' => ''
    ];
    $new_uploaded_files = $uploaded_files;

    foreach ($uploaded_files as $key => &$path) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            worker4a_validate_upload_file($key, $_FILES[$key]);
            $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
            $filename = $key . '_' . uniqid() . '.' . $ext;
            $target = $upload_dir . $filename;
            if (@move_uploaded_file($_FILES[$key]['tmp_name'], $target)) {
                $path = $filename;
                $new_uploaded_files[$key] = $filename;
            } else {
                throw new Exception("File upload failed for $key. Linux server par uploads/workers permission check karein.");
            }
        }
    }
    unset($path);

    if (!$contractor_row) {
        throw new Exception("Contractor record not found for this enrollment.");
    }

    $source_val = $data['source'] ?? 'MANUAL';
    $contractor_id = (int)$contractor_row['id'];
    $trade = $data['nature_of_work'] ?? '';
    $skill = $data['skill_category'] ?? '';
    $workmen_skill_category = normalize_skill_category($skill);
    if ($action !== 'draft') {
        if (($data['epf_registered_worker'] ?? '') === 'YES' && trim($data['pf_no'] ?? '') === '') {
            throw new Exception("UAN Number is mandatory when EPF Registered is Yes.");
        }
        if (($data['esi_registered_worker'] ?? '') === 'YES' && trim($data['esi_no'] ?? '') === '') {
            throw new Exception("ESI Number is mandatory when ESI Registered is Yes.");
        }
        if (trim($data['certified_wage_rate'] ?? '') === '') {
            throw new Exception("Certified Wage Rate is mandatory.");
        }
        $categoryWageRule = clms_get_active_certified_wage_for_category($conn, $workmen_skill_category);
        $minimumCertifiedWage = $categoryWageRule ? (float)$categoryWageRule['wage_rate'] : clms_get_minimum_certified_wage($conn);
        $submittedWage = clms_parse_wage_amount($data['certified_wage_rate'] ?? '');
        if ($submittedWage === null) {
            throw new Exception("Please enter a valid Certified Wage Rate.");
        }
        if ($minimumCertifiedWage > 0 && $submittedWage < $minimumCertifiedWage) {
            throw new Exception("Certified Wage Rate cannot be less than " . number_format($minimumCertifiedWage, 2) . " for " . ($workmen_skill_category ?: 'selected category') . ".");
        }
        if ($categoryWageRule) {
            $data['certified_wage_rate'] = number_format((float)$categoryWageRule['wage_rate'], 2, '.', '');
        }
        if (trim($data['safety_language'] ?? '') === '') {
            throw new Exception("Language Preferred for Safety Induction is mandatory.");
        }
        if (trim($data['pwd_status'] ?? '') === '') {
            throw new Exception("PWD Status is mandatory.");
        }
    }
    $application_row = db_single(
        $conn,
        "SELECT application_id FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1",
        'i',
        [$contractor_id]
    );
    $application_no = $contractor_row['application_no'] ?: ($application_row['application_id'] ?? ('APP-' . $contractor_id));
    $worker_type = 'Workmen Pass';
    if (strtolower($limit_type) === 'supervisor') $worker_type = 'Supervisor Pass';
    elseif (strtolower($limit_type) === 'representative') $worker_type = 'Representative Pass';
    elseif (strtolower($limit_type) === 'contractor') $worker_type = 'Contractor Pass';

    $workman_row = [
        'application_no' => $application_no,
        'work_order_no' => $data['work_order_no'] ?? '',
        'project_name' => $data['project_name'] ?? '',
        'contractor_id' => $contractor_id,
        'name' => $data['name'] ?? '',
        'father_name' => $data['father_name'] ?? '',
        'dob' => trim((string)($data['dob'] ?? '')) !== '' ? $data['dob'] : null,
        'gender' => $data['gender'] ?? '',
        'marital_status' => $data['marital_status'] ?? '',
        'nationality' => $data['nationality'] ?? 'Indian',
        'aadhaar' => $data['aadhaar'] ?? '',
        'mobile' => $data['mobile'] ?? '',
        'emergency_contact' => $data['emergency_contact'] ?? '',
        'permanent_address' => $data['permanent_address'] ?? '',
        'present_address' => $data['present_address'] ?? '',
        'state' => $data['state'] ?? '',
        'district' => $data['district'] ?? '',
        'pincode' => $data['pincode'] ?? '',
        'region' => $data['region'] ?? '',
        'pwd_status' => $data['pwd_status'] ?? '',
        'passport_no' => $data['passport_no'] ?? '',
        'driving_licence_no' => $data['driving_licence_no'] ?? '',
        'email' => $data['email'] ?? ($data['contact_email'] ?? ''),
        'contact_email' => $data['contact_email'] ?? '',
        'dcate' => $data['dcate'] ?? '',
        'blood_group' => $data['blood_group'] ?? '',
        'education' => $data['education'] ?? '',
        'skill' => $skill,
        'skill_category' => $workmen_skill_category,
        'role_type' => $workmen_skill_category,
        'trade' => $trade,
        'department' => $data['department'] ?? '',
        'nature_of_work' => $data['nature_of_work'] ?? '',
        'bank_account' => $data['bank_account'] ?? '',
        'ifsc' => $data['ifsc'] ?? '',
        'pf_no' => $data['pf_no'] ?? '',
        'epf_registered_worker' => $data['epf_registered_worker'] ?? '',
        'esi_registered_worker' => $data['esi_registered_worker'] ?? '',
        'uan_number' => $data['uan_number'] ?? '',
        'esic_number' => $data['esi_no'] ?? '',
        'experience' => $data['experience'] ?? '',
        'certified_wage_rate' => $data['certified_wage_rate'] ?? '',
        'safety_language' => $data['safety_language'] ?? '',
        'training_approval_doc' => $uploaded_files['training_approval_doc'],
        'executing_officer_code' => $executingOfficer['employee_code'] ?? $executingOfficerCode,
        'executing_officer_name' => $executingOfficer['name'] ?? ($data['executing_officer_name'] ?? ''),
        'executing_officer_id' => $executingOfficer['id'] ?? null,
        'photo' => $uploaded_files['photo'],
        'education_doc' => $uploaded_files['education_doc'],
        'educational_doc' => $uploaded_files['education_doc'],
        'bank_doc' => $uploaded_files['bank_doc'],
        'gatepass_doc' => $uploaded_files['gatepass_doc'],
        'skill_cert_doc' => $uploaded_files['skill_cert_doc'],
        'medical_doc' => $uploaded_files['medical_doc'],
        'police_doc' => $uploaded_files['police_doc'],
        'insurance_doc' => $uploaded_files['insurance_doc'],
        'aadhaar_doc' => $uploaded_files['aadhaar_doc'],
        'signature_doc' => $uploaded_files['signature'],
        'status' => $action === 'draft' ? 'draft' : 'pending',
        'training_status' => 'pending',
        'worker_type' => $worker_type,
        'safety_training_status' => 'PENDING_TRAINING',
        'source' => $source_val
    ];

    $existing_workman = null;
    if ($editing_worker_id) {
        $existing_workman = db_single(
            $conn,
            "SELECT * FROM workmen WHERE id = ? AND contractor_id = ? LIMIT 1",
            'ii',
            [$editing_worker_id, $contractor_id]
        );
        if (!$existing_workman) {
            throw new Exception("Worker not found or not allowed to edit.");
        }
    } else {
        $existing_workman = db_single(
            $conn,
            "SELECT * FROM workmen WHERE aadhaar = ? AND contractor_id = ? ORDER BY id DESC LIMIT 1",
            'si',
            [$data['aadhaar'], $contractor_id]
        );
    }

    if ($action !== 'draft') {
        $existingLimitType = $existing_workman
            ? worker4a_limit_type_from_value($existing_workman['worker_type'] ?? ($existing_workman['pass_type'] ?? ''))
            : '';
        $existingStatus = $existing_workman['status'] ?? '';
        $alreadyCountsInSameLimit = $existing_workman
            && $existingLimitType === $limit_type
            && worker4a_status_counts_for_limit($existingStatus);

        if (!$alreadyCountsInSameLimit) {
            validatePassLimit($conn, $contractor_id, $limit_type, 1, false);
        }
    }

    if ($existing_workman) {
        $existingExecutionStatus = strtolower(trim((string)($existing_workman['execution_training_status'] ?? '')));
        if ($action !== 'draft' && $existingExecutionStatus === 'rejected' && empty($new_uploaded_files['training_approval_doc'])) {
            throw new Exception("Executing Officer ne request reject ki hai. Corrected Training Approval document dobara upload karein.");
        }

        $file_map = [
            'photo' => 'photo',
            'education_doc' => 'education_doc',
            'bank_doc' => 'bank_doc',
            'gatepass_doc' => 'gatepass_doc',
            'skill_cert_doc' => 'skill_cert_doc',
            'medical_doc' => 'medical_doc',
            'police_doc' => 'police_doc',
            'insurance_doc' => 'insurance_doc',
            'aadhaar_doc' => 'aadhaar_doc',
            'signature' => 'signature_doc',
            'training_approval_doc' => 'training_approval_doc'
        ];
        foreach ($file_map as $uploadKey => $column) {
            if (($uploaded_files[$uploadKey] ?? '') === '' && !empty($existing_workman[$column])) {
                $uploaded_files[$uploadKey] = $existing_workman[$column];
            }
        }
    }

    $workman_row['photo'] = $uploaded_files['photo'];
    $workman_row['education_doc'] = $uploaded_files['education_doc'];
    $workman_row['educational_doc'] = $uploaded_files['education_doc'];
    $workman_row['bank_doc'] = $uploaded_files['bank_doc'];
    $workman_row['gatepass_doc'] = $uploaded_files['gatepass_doc'];
    $workman_row['skill_cert_doc'] = $uploaded_files['skill_cert_doc'];
    $workman_row['medical_doc'] = $uploaded_files['medical_doc'];
    $workman_row['police_doc'] = $uploaded_files['police_doc'];
    $workman_row['insurance_doc'] = $uploaded_files['insurance_doc'];
    $workman_row['aadhaar_doc'] = $uploaded_files['aadhaar_doc'];
    $workman_row['signature_doc'] = $uploaded_files['signature'];
    $workman_row['training_approval_doc'] = $uploaded_files['training_approval_doc'];
    if ($action !== 'draft') {
        if (!empty($uploaded_files['training_approval_doc'])) {
            $workman_row['execution_training_status'] = 'approved';
            $workman_row['execution_training_remarks'] = 'Auto-approved because Training Attendance Approval document is attached.';
            $workman_row['execution_training_reviewed_by'] = (int)($executingOfficer['id'] ?? 0);
            $workman_row['execution_training_reviewed_at'] = date('Y-m-d H:i:s');
        } else {
            $workman_row['execution_training_status'] = 'pending_eo';
        }
    }
    if (!empty($new_uploaded_files['training_approval_doc'])) {
        $workman_row['execution_training_remarks'] = 'Auto-approved because Training Attendance Approval document is attached.';
        $workman_row['execution_training_reviewed_by'] = (int)($executingOfficer['id'] ?? 0);
        $workman_row['execution_training_reviewed_at'] = date('Y-m-d H:i:s');
    }

    if ($existing_workman) {
        $workman_id_new = (int)$existing_workman['id'];
        update_table_row_by_id($conn, 'workmen', $workman_id_new, $workman_row);
    } else {
        $workman_id_new = insert_table_row($conn, 'workmen', $workman_row);
    }

    if ($action !== 'draft' && !empty($uploaded_files['training_approval_doc'])) {
        clms_training_auto_approve_attached_document(
            $conn,
            $workman_id_new,
            (int)($executingOfficer['id'] ?? 0),
            'Auto-approved because Training Attendance Approval document is attached.'
        );
    }

    $temp_id = '';
    $paymentRequest = null;
    if ($action !== 'draft') {
        $temp_id = "TEMP-" . str_pad($workman_id_new, 6, "0", STR_PAD_LEFT);
        update_table_row_by_id($conn, 'workmen', $workman_id_new, ['temp_id' => $temp_id]);
        $paymentRequest = clms_create_training_payment_request(
            $conn,
            $contractor_id,
            [$workman_id_new],
            (int)($_SESSION['user_id'] ?? 0),
            'enrolment'
        );
    } else {
        update_table_row_by_id($conn, 'workmen', $workman_id_new, ['temp_id' => null]);
    }

    if ($action !== 'draft') {
        if (($contractor_row['status'] ?? '') === 'approved' && worker4a_table_exists($conn, 'annexure2a')) {
            $annexureUpdates = [];
            if (worker4a_column_exists($conn, 'annexure2a', 'workflow_status')) {
                $annexureUpdates[] = "workflow_status = 'enrolment_done'";
            }
            if (worker4a_column_exists($conn, 'annexure2a', 'updated_at')) {
                $annexureUpdates[] = "updated_at = NOW()";
            }
            if ($annexureUpdates && worker4a_column_exists($conn, 'annexure2a', 'application_id')) {
                $statusClause = worker4a_column_exists($conn, 'annexure2a', 'workflow_status')
                    ? " AND workflow_status IN ('submitted','verified','approved','enrolment_done')"
                    : "";
                $sql = "UPDATE annexure2a SET " . implode(', ', $annexureUpdates) . " WHERE application_id = ?" . $statusClause;
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('s', $application_no);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        worker4a_upsert_workflow($conn, $application_no, $contractor_id);

        // Save worker docs to documents table for welfare verification
        if ($workman_id_new) {
            $doc_type_map = [
                'photo' => 'Photo',
                'aadhaar_doc' => 'Aadhaar Card',
                'education_doc' => 'Education Certificate',
                'training_approval_doc' => 'Training Attendance Approval'
            ];

            $documentFiles = (($existing_workman['status'] ?? '') === 'draft') ? $uploaded_files : $new_uploaded_files;
            foreach ($documentFiles as $key => $file) {
                if ($file) {
                    $doc_type_name = $doc_type_map[$key] ?? $key;
                    $db_file_path = "../../uploads/workers/" . $file;
                    db_execute(
                        $conn,
                        "INSERT INTO documents (workman_id, document_type, file_path, status, uploaded_at)
                         VALUES (?, ?, ?, 'pending', NOW())",
                        'iss',
                        [$workman_id_new, $doc_type_name, $db_file_path]
                    );
                }
            }
        }
    }

    $notificationDebug = [];
    if ($action !== 'draft') {
        $contractorUser = null;
        if (!empty($_SESSION['user_id'])) {
            $contractorUser = db_single($conn, "SELECT name, email, mobile FROM users WHERE id = ? LIMIT 1", 'i', [(int)$_SESSION['user_id']]);
        }
        $workerName = trim((string)($data['name'] ?? 'Worker'));
        $subject = 'CLMS Worker Enrolment Submitted';
        $message = "Dear User,\n\n"
            . "Worker enrolment has been submitted successfully.\n"
            . "Worker: $workerName\n"
            . "Application: $application_no\n"
            . "Temporary ID: $temp_id\n\n"
            . "This is an automated message.";

        $workerMobile = trim((string)($data['mobile'] ?? ''));
        $workerEmail = trim((string)($data['email'] ?? ($data['contact_email'] ?? '')));
        $contractorEmail = trim((string)($contractorUser['email'] ?? ''));

        $notificationDebug['worker_sms'] = $workerMobile !== ''
            ? sendSMS($workerMobile, "CLMS enrolment submitted for $workerName. Temp ID: $temp_id. Application: $application_no.")
            : ['success' => false, 'message' => 'Worker mobile not available'];
        $notificationDebug['worker_email'] = $workerEmail !== ''
            ? sendEmailNotification($workerEmail, $subject, $message, 'worker_enrolment', $workerName)
            : ['success' => false, 'message' => 'Worker email not available'];
        $notificationDebug['contractor_email'] = $contractorEmail !== ''
            ? sendEmailNotification($contractorEmail, $subject, $message, 'worker_enrolment', $contractorUser['name'] ?? '')
            : ['success' => false, 'message' => 'Contractor email not available'];
        $notificationDebug['demo_email'] = sendDemoEmailNotification(
            'CLMS Demo Worker Enrolment',
            $message . "\n\nDemo copy requested for: arjunprajapati8595@gmail.com",
            'worker_enrolment_demo'
        );
    }

    worker4a_json([
        "success" => true,
        "message" => $action === 'draft' ? "Draft saved successfully." : "Worker enrolled successfully.",
        "worker_id" => $workman_id_new,
        "workman_id" => $workman_id_new,
        "temp_id" => $temp_id,
        "payment" => $paymentRequest ? [
            "payment_ref" => $paymentRequest['payment_ref'],
            "amount" => $paymentRequest['total_amount'],
            "payment_link" => $paymentRequest['payment_link'],
            "link_expires_at" => $paymentRequest['link_expires_at'],
        ] : null,
        "notification_debug" => $notificationDebug
    ]);

} catch (Throwable $e) {
    error_log('[SAVE_WORKER_4A] ' . $e->getMessage());
    worker4a_json([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
