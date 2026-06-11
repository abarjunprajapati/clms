<?php
/**
 * Super Admin Schema Initialization
 * Creates all missing tables needed for Super Admin governance.
 * SAFE: Uses CREATE TABLE IF NOT EXISTS, will NOT destroy existing data.
 * Reuses existing tables: audit_logs, notifications, sap_sync_queue, contractor_blocks, worker_blocks, etc.
 */
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

$results = [];

// Helper: add column if missing
function addCol($conn, $table, $col, $def) {
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    if ($r && mysqli_num_rows($r) == 0) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$col` $def");
        return true;
    }
    return false;
}

// ============================================================
// 1. SYSTEM SETTINGS (grouped configs)
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    description VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['system_settings'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);

// Seed default settings
$defaults = [
    ['temp_pass_validity_days','7','pass','Temporary pass validity in days'],
    ['permanent_pass_validity_months','12','pass','Permanent pass validity in months'],
    ['max_pass_extensions','2','pass','Maximum pass extensions allowed'],
    ['training_pass_mark','60','training','Minimum pass mark for safety training'],
    ['training_max_attempts','3','training','Maximum training attempts allowed'],
    ['sap_endpoint','https://sap-demo.example.com/api','sap','SAP S/4 HANA API endpoint'],
    ['sap_auth_token','demo-token-xxx','sap','SAP authentication token'],
    ['sap_sync_enabled','1','sap','Enable/disable SAP synchronization'],
    ['sms_provider','fast2sms','sms','SMS service provider'],
    ['sms_api_key','YOUR_API_KEY','sms','SMS API key'],
    ['sms_enabled','0','sms','Enable/disable SMS notifications'],
    ['email_enabled','0','email','Enable/disable email notifications'],
    ['email_mailer','smtp','email','Email transport: smtp or mail'],
    ['email_smtp_host','smtp.gmail.com','email','SMTP server host'],
    ['email_smtp_port','587','email','SMTP server port'],
    ['email_smtp_secure','tls','email','SMTP encryption: tls, ssl, or none'],
    ['email_smtp_username','','email','SMTP username/email address'],
    ['email_smtp_password','','email','SMTP password or app password'],
    ['email_from','no-reply@clms.local','email','Email from address'],
    ['email_from_name','CLMS','email','Email from name'],
    ['session_timeout_minutes','30','security','Session timeout in minutes'],
    ['max_login_attempts','5','security','Maximum login attempts before lockout'],
    ['lockout_duration_minutes','15','security','Account lockout duration in minutes'],
    ['attendance_sync_interval','15','attendance','Attendance sync interval in minutes'],
    ['biometric_enabled','1','attendance','Enable biometric integration'],
    ['compliance_reminder_days','7','compliance','Days before compliance deadline to send reminder'],
    ['system_lockdown','0','emergency','System lockdown mode (0=off, 1=on)'],
    ['lockdown_message','System is under maintenance.','emergency','Message shown during lockdown'],
];
foreach($defaults as $d) {
    $key = $d[0]; $val = $d[1]; $grp = $d[2]; $desc = $d[3];
    $check = db_count($conn, "SELECT COUNT(*) c FROM system_settings WHERE setting_key=?", 's', [$key]);
    if($check == 0) {
        db_execute($conn, "INSERT INTO system_settings (setting_key, setting_value, setting_group, description) VALUES (?,?,?,?)", 'ssss', [$key,$val,$grp,$desc]);
    }
}
$results['system_settings_seed'] = 'OK';

// ============================================================
// 2. ROLE PERMISSIONS MATRIX (expanded)
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL,
    module VARCHAR(100) NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_create TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    can_approve TINYINT(1) DEFAULT 0,
    can_block TINYINT(1) DEFAULT 0,
    can_export TINYINT(1) DEFAULT 0,
    can_override TINYINT(1) DEFAULT 0,
    can_sync_sap TINYINT(1) DEFAULT 0,
    can_manage_settings TINYINT(1) DEFAULT 0,
    can_assign_roles TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_role_module (role_name, module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['role_permissions'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);

// Seed default permissions
$roles = ['super_admin','welfare_admin','welfare_user','safety_user','front_line_user','pass_user','contractor'];
$modules = ['dashboard','users','contractors','workmen','documents','training','gate_pass','compliance','attendance','reports','sap','settings','master_data','audit_logs','notifications','blocking'];
foreach($roles as $r) {
    foreach($modules as $m) {
        $check = db_count($conn, "SELECT COUNT(*) c FROM role_permissions WHERE role_name=? AND module=?", 'ss', [$r,$m]);
        if($check == 0) {
            $isSA = ($r == 'super_admin') ? 1 : 0;
            db_execute($conn, "INSERT INTO role_permissions (role_name, module, can_view, can_create, can_edit, can_delete, can_approve, can_block, can_export, can_override, can_sync_sap, can_manage_settings, can_assign_roles) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", 'ssiiiiiiiiiii', [$r,$m,$isSA,$isSA,$isSA,$isSA,$isSA,$isSA,$isSA,$isSA,$isSA,$isSA,$isSA]);
        }
    }
}
$results['role_permissions_seed'] = 'OK';

// ============================================================
// 3. SUPER ADMIN ACTIVITY LOGS (destructive action tracking)
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS super_admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    target_module VARCHAR(100),
    target_id INT,
    old_data TEXT,
    new_data TEXT,
    severity ENUM('info','warning','critical','emergency') DEFAULT 'info',
    ip_address VARCHAR(100),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_id (admin_id),
    KEY idx_action_type (action_type),
    KEY idx_severity (severity),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['super_admin_activity_logs'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);

// ============================================================
// 4. MASTER TABLES
// ============================================================
$masters = [
    "CREATE TABLE IF NOT EXISTS master_trades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trade_name VARCHAR(100) NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dept_name VARCHAR(100) NOT NULL,
        dept_code VARCHAR(20),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) NOT NULL,
        location_code VARCHAR(20),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        skill_level VARCHAR(50) NOT NULL,
        wage_multiplier DECIMAL(3,2) DEFAULT 1.00,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_pass_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL,
        validity_days INT DEFAULT 30,
        description VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_training_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL,
        duration_hours INT DEFAULT 8,
        pass_mark INT DEFAULT 60,
        description VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_compliance_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL,
        frequency ENUM('monthly','quarterly','annually') DEFAULT 'monthly',
        description VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_safety_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        risk_level ENUM('low','medium','high','critical') DEFAULT 'medium',
        description VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_document_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doc_type_name VARCHAR(100) NOT NULL,
        is_mandatory TINYINT(1) DEFAULT 1,
        description VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS master_contractor_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL,
        max_workers INT DEFAULT 100,
        description VARCHAR(255),
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach($masters as $i => $sq) {
    preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sq, $m);
    $tbl = $m[1] ?? "master_$i";
    $results[$tbl] = mysqli_query($conn, $sq) ? 'OK' : mysqli_error($conn);
}

// Seed master data
$seeds = [
    ['master_trades', 'trade_name', ['Welder','Electrician','Fitter','Plumber','Carpenter','Painter','Mason','Rigger','Helper','Scaffolder']],
    ['master_departments', 'dept_name', ['Mechanical','Electrical','Civil','Instrumentation','Production','Maintenance','Safety','Administration']],
    ['master_locations', 'location_name', ['Main Plant','Unit-1','Unit-2','Workshop','Store','Admin Block','Gate Area','Canteen']],
    ['master_skills', 'skill_level', ['Unskilled','Semi-Skilled','Skilled','Highly Skilled']],
    ['master_pass_types', 'type_name', ['Contractor Pass','Supervisor Pass','Workman Pass','Visitor Pass','Vehicle Pass']],
    ['master_training_types', 'type_name', ['Safety Induction','Fire Safety','Height Work','Confined Space','Electrical Safety','Chemical Handling']],
    ['master_compliance_types', 'type_name', ['ESI','EPF','KLWF','CLRA License','Insurance','Wage Register']],
    ['master_safety_categories', 'category_name', ['General Safety','Fire Safety','Electrical Safety','Height Safety','Chemical Safety','Confined Space']],
    ['master_document_types', 'doc_type_name', ['Aadhaar Card','PAN Card','Medical Fitness Certificate','Police Clearance','Bank Proof','Insurance','Training Certificate','Age Proof','Address Proof']],
    ['master_contractor_categories', 'category_name', ['A-Class (>500 workers)','B-Class (200-500)','C-Class (50-200)','D-Class (<50)']],
];
foreach($seeds as $s) {
    $tbl = $s[0]; $col = $s[1]; $vals = $s[2];
    foreach($vals as $v) {
        $cnt = db_count($conn, "SELECT COUNT(*) c FROM $tbl WHERE $col=?", 's', [$v]);
        if($cnt == 0) db_execute($conn, "INSERT INTO $tbl ($col) VALUES (?)", 's', [$v]);
    }
}
$results['master_seeds'] = 'OK';

// ============================================================
// 5. NOTIFICATION LOGS (delivery tracking)
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(100),
    recipient_name VARCHAR(100),
    channel ENUM('sms','email','push','system') DEFAULT 'system',
    type VARCHAR(50),
    subject VARCHAR(200),
    message TEXT,
    status ENUM('sent','delivered','failed','queued') DEFAULT 'queued',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_channel (channel),
    KEY idx_status (status),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['notification_logs'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);

// ============================================================
// 6. SAP INTEGRATION LOGS (enhanced with retry)
// Extending EXISTING sap_integration_log table
// ============================================================
addCol($conn, 'sap_integration_log', 'retry_count', "INT DEFAULT 0");
addCol($conn, 'sap_integration_log', 'last_retry_at', "TIMESTAMP NULL");
addCol($conn, 'sap_integration_log', 'reference_id', "VARCHAR(100)");
addCol($conn, 'sap_integration_log', 'sync_type', "VARCHAR(50) DEFAULT 'manual'");
$results['sap_integration_log_enhanced'] = 'OK';

// ============================================================
// 7. SYSTEM ERROR LOGS
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS system_error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    severity ENUM('info','warning','critical','error') DEFAULT 'info',
    message TEXT,
    source VARCHAR(100),
    stack_trace TEXT,
    resolved TINYINT(1) DEFAULT 0,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_severity (severity),
    KEY idx_resolved (resolved),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['system_error_logs'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);

// ============================================================
// 8. ATTENDANCE EXCEPTIONS (missing punch, mismatch, etc.)
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS attendance_exceptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT,
    exception_type ENUM('missing_punch','duplicate_punch','device_offline','acc_mismatch','biometric_failed','late_entry','early_exit') NOT NULL,
    description TEXT,
    exception_date DATE,
    device_id VARCHAR(50),
    status ENUM('open','resolved','escalated') DEFAULT 'open',
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_workman_id (workman_id),
    KEY idx_type (exception_type),
    KEY idx_status (status),
    KEY idx_date (exception_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['attendance_exceptions'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);

// ============================================================
// 9. ROLES TABLE — ensure it exists with descriptions
// ============================================================
$sql = "CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    is_system TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$results['roles'] = mysqli_query($conn, $sql) ? 'OK' : mysqli_error($conn);
addCol($conn, 'roles', 'is_system', "TINYINT(1) DEFAULT 1");
mysqli_query($conn, "ALTER TABLE users MODIFY role ENUM('contractor','welfare_admin','welfare_user','safety_user','front_line_user','pass_user','super_admin','execution_officer','execution') DEFAULT 'contractor'");

$roleDefs = [
    ['super_admin','Full system access. Highest authority with override and emergency control powers.',1],
    ['welfare_admin','Administrative control over welfare operations, user management, and policy configuration.',1],
    ['welfare_user','Operational desk for document verification, contractor approval, and pass issuance.',1],
    ['safety_user','Manages safety training sessions, conducts tests, and records training results.',1],
    ['front_line_user','Gate-level operations: entry/exit validation, pass scanning, and real-time monitoring.',1],
    ['pass_user','Issues temporary and permanent gate passes, manages ACC numbers and pass validity.',1],
    ['contractor','Contractor portal for registration, worker enrollment, training requests, and compliance uploads.',1],
];
foreach($roleDefs as $rd) {
    $cnt = db_count($conn, "SELECT COUNT(*) c FROM roles WHERE role_name=?", 's', [$rd[0]]);
    if($cnt == 0) {
        db_execute($conn, "INSERT INTO roles (role_name, description, is_system) VALUES (?,?,?)", 'ssi', [$rd[0],$rd[1],$rd[2]]);
    } else {
        db_execute($conn, "UPDATE roles SET description=? WHERE role_name=?", 'ss', [$rd[1],$rd[0]]);
    }
}
$results['roles_seed'] = 'OK';

// ============================================================
// 10. Ensure audit_logs has 'details' column
// ============================================================
addCol($conn, 'audit_logs', 'details', "TEXT AFTER remarks");
$results['audit_logs_details'] = 'OK';

echo json_encode(['success' => true, 'message' => 'Super Admin schema initialized successfully', 'results' => $results]);
