<?php
/**
 * init_schema.php
 * Database Schema - Creates all required tables for CLMS
 * Run this to initialize/update the database
 * Access: Direct PHP or via API call
 */

require_once __DIR__ . '/../include/config.php';

$conn = null;

// Re-use existing connection if available
if (function_exists('db_connect')) {
    $conn = db_connect();
} else {
    // Create new connection using config
    $Servername = "127.0.0.1";
    $Username = "root";
    $Password = "";
    $Dbname = "new_clms";
    $conn = mysqli_connect($Servername, $Username, $Password, $Dbname);
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Starting schema initialization...\n\n";

// ============================================
// 1. CORE TABLES
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL UNIQUE,
    contractor_id INT,
    workflow_status VARCHAR(50) DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_workflow_status (workflow_status),
    KEY idx_contractor_id (contractor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ applications table created/updated\n";

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200),
    email VARCHAR(200) UNIQUE,
    mobile VARCHAR(20),
    role ENUM('contractor', 'welfare_admin', 'welfare_user', 'safety_user', 'front_line_user', 'pass_user', 'super_admin') DEFAULT 'contractor',
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contractor_id (contractor_id),
    KEY idx_email (email),
    KEY idx_role (role),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ users table created/updated\n";

// Update existing users table if needed
$alterSql = "ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS contractor_id VARCHAR(50) NOT NULL UNIQUE AFTER id,
    ADD COLUMN IF NOT EXISTS mobile VARCHAR(20) AFTER email,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS reset_expiry DATETIME DEFAULT NULL AFTER reset_token,
    ADD COLUMN IF NOT EXISTS reset_attempts INT DEFAULT 0 AFTER reset_expiry,
    MODIFY COLUMN role ENUM('contractor', 'welfare_admin', 'welfare_user', 'safety_user', 'front_line_user', 'pass_user', 'super_admin') DEFAULT 'contractor',
    ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active',
    ADD KEY IF NOT EXISTS idx_contractor_id (contractor_id),
    ADD KEY IF NOT EXISTS idx_email (email),
    ADD KEY IF NOT EXISTS idx_role (role),
    ADD KEY IF NOT EXISTS idx_status (status)";
$conn->query($alterSql);
echo "✓ users table altered if needed\n";

// ============================================
// PASSWORD RESET TOKENS
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id VARCHAR(50) NOT NULL,
    email VARCHAR(200) NOT NULL,
    token VARCHAR(255) NOT NULL,
    otp VARCHAR(10),
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_token (token),
    KEY idx_contractor_id (contractor_id),
    KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ password_resets table created/updated\n";


// ============================================
// 2. CONTRACTOR REGISTRATION (Contractor Registration)
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS annexure2a (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    ref_id VARCHAR(50),
    contractor_id INT,
    contractor_name VARCHAR(200),
    proprietor_name VARCHAR(200),
    pan VARCHAR(20),
    gst VARCHAR(30),
    contract_no VARCHAR(100),
    project_name VARCHAR(300),
    work_location VARCHAR(300),
    category_work VARCHAR(200),
    deployment_date DATE,
    labour_validity DATE,
    contract_value DECIMAL(15,2),
    contract_start DATE,
    contract_end DATE,
    state_name VARCHAR(100),
    office_address TEXT,
    pin_code VARCHAR(10),
    mobile VARCHAR(20),
    email VARCHAR(100),
    epf_code VARCHAR(50),
    esic_code VARCHAR(50),
    labour_license VARCHAR(100),
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    ifsc VARCHAR(20),
    workflow_status VARCHAR(30) DEFAULT 'submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_application_id (application_id),
    KEY idx_ref_id (ref_id),
    KEY idx_contractor_id (contractor_id),
    KEY idx_workflow_status (workflow_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ annexure2a table created/updated\n";


// ============================================
// 3. CONTRACTOR INFO (ANNEXURE 3A)
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS annexure3a (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT,
    sub_contractor_name VARCHAR(200),
    sub_contractor_work VARCHAR(200),
    sub_contract_value DECIMAL(15,2),
    sub_registration_no VARCHAR(50),
    sub_workmen_strength INT,
    sub_contact_person VARCHAR(200),
    insurance_policy_no VARCHAR(100),
    insurance_provider VARCHAR(200),
    insurance_validity_from DATE,
    insurance_validity_to DATE,
    sum_insured DECIMAL(15,2),
    work_zone_primary VARCHAR(200),
    work_zone_secondary VARCHAR(200),
    access_gate VARCHAR(100),
    working_hours VARCHAR(50),
    special_requirements TEXT,
    declaration TINYINT(1) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_application_id (application_id),
    KEY idx_contractor_id (contractor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ annexure3a table created/updated\n";


// ============================================
// 4. WORKMEN / PERSONNEL TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS workmen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temp_id VARCHAR(50),
    acc_number VARCHAR(50),
    application_no VARCHAR(50) NOT NULL,
    contractor_id INT,
    name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100),
    dob DATE,
    gender VARCHAR(10),
    marital_status VARCHAR(20),
    aadhaar VARCHAR(20) UNIQUE,
    esic_number VARCHAR(50),
    uan_number VARCHAR(50),
    mobile VARCHAR(15),
    email VARCHAR(100),
    permanent_address TEXT,
    present_address TEXT,
    state VARCHAR(50),
    district VARCHAR(50),
    education VARCHAR(150),
    skill VARCHAR(150),
    trade VARCHAR(150),
    department VARCHAR(150),
    nature_of_work VARCHAR(300),
    work_location VARCHAR(300),
    daily_wage_rate DECIMAL(10,2) DEFAULT 0.00,
    wage_type ENUM('daily','weekly','monthly') DEFAULT 'daily',
    allowance DECIMAL(10,2) DEFAULT 0.00,
    region VARCHAR(100),
    pincode VARCHAR(10),
    nationality VARCHAR(50) DEFAULT 'Indian',
    blood_group VARCHAR(10),
    qualification VARCHAR(100),
    experience VARCHAR(100),
    bank_account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    safety_training_attended TINYINT(1) DEFAULT 0,
    safety_training_date DATE,
    safety_induction TINYINT(1) DEFAULT 0,
    medical_fitness_file VARCHAR(255),
    police_clearance_file VARCHAR(255),
    insurance_policy_file VARCHAR(255),
    qualification_file VARCHAR(255),
    experience_file VARCHAR(255),
    photo VARCHAR(255),
    status ENUM('draft','pending','active','inactive','blocked','temp','trained','verified','acc_generated','temporary_issued','permanent_issued') DEFAULT 'active',
    biometric_status VARCHAR(20) DEFAULT 'pending',
    training_status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_application_no (application_no),
    KEY idx_contractor_id (contractor_id),
    KEY idx_aadhaar (aadhaar),
    KEY idx_temp_id (temp_id),
    KEY idx_training_status (training_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ workmen table created/updated\n";


// ============================================
// 5. SUPERVISORS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS supervisors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT,
    name VARCHAR(200) NOT NULL,
    designation VARCHAR(100),
    aadhar VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    qualification VARCHAR(200),
    experience VARCHAR(50),
    temp_id VARCHAR(50),
    training_status VARCHAR(30) DEFAULT 'pending',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_application_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ supervisors table created/updated\n";


// ============================================
// 6. REPRESENTATIVES TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS representatives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT,
    name VARCHAR(200) NOT NULL,
    designation VARCHAR(100),
    aadhar VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    authority_level VARCHAR(20) DEFAULT 'Partial',
    temp_id VARCHAR(50),
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_application_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ representatives table created/updated\n";


// ============================================
// 7. TRAINING SESSIONS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS training_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(200),
    venue VARCHAR(300),
    trainer_name VARCHAR(200),
    date DATE,
    time VARCHAR(20),
    capacity INT DEFAULT 50,
    enrolled_count INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_date (date),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ training_sessions table created/updated\n";


// ============================================
// 8. TRAINING RESULTS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS training_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    workman_id INT NOT NULL,
    training_session_id VARCHAR(50),
    attendance_status VARCHAR(20) DEFAULT 'present',
    result VARCHAR(20) DEFAULT 'pending',
    theory_score INT DEFAULT 0,
    practical_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    certificate_no VARCHAR(50),
    recorded_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_application_id (application_id),
    KEY idx_workman_id (workman_id),
    KEY idx_session (training_session_id),
    KEY idx_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ training_results table created/updated\n";


// ============================================
// 9. GATE PASS REQUESTS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS gate_pass_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_no VARCHAR(50) NOT NULL,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT,
    gate_name VARCHAR(100),
    shift_name VARCHAR(50),
    access_zone VARCHAR(100),
    from_date DATE,
    to_date DATE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_request_no (request_no),
    KEY idx_application_id (application_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ gate_pass_requests table created/updated\n";


// ============================================
// 10. GATE PASS REQUEST WORKERS
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS gate_pass_request_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    workman_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    gatepass_no VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_request_id (request_id),
    KEY idx_workman_id (workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ gate_pass_request_workers table created/updated\n";


// ============================================
// 11. APPLICATION WORKFLOW TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS application_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT NULL,
    current_stage VARCHAR(30) DEFAULT 'submitted',
    pio_status VARCHAR(20) DEFAULT 'pending',
    welfare_status VARCHAR(20) DEFAULT 'pending',
    aoc_status VARCHAR(20) DEFAULT 'pending',
    final_status VARCHAR(20) DEFAULT 'pending',
    training_status VARCHAR(20) DEFAULT 'pending',
    gatepass_status VARCHAR(20) DEFAULT 'pending',
    overall_status VARCHAR(20) DEFAULT 'pending',
    remarks TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_application_workflow (application_id),
    KEY idx_current_stage (current_stage),
    KEY idx_overall_status (overall_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ application_workflow table created/updated\n";


// ============================================
// 12. REMARKS HISTORY
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS remarks_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    remark TEXT,
    created_by VARCHAR(50),
    action_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_remarks_app_id (application_id),
    KEY idx_action_type (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ remarks_history table created/updated\n";


// ============================================
// 13. PERMANENT GATE PASSES
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS permanent_gate_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_no VARCHAR(50) NOT NULL,
    worker_id INT NOT NULL,
    application_id VARCHAR(50),
    contractor_id INT,
    valid_from DATE,
    valid_till DATE,
    qr_code VARCHAR(100),
    status VARCHAR(20) DEFAULT 'active',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_pass_no (pass_no),
    KEY idx_worker_id (worker_id),
    KEY idx_application_id (application_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ permanent_gate_passes table created/updated\n";


// ============================================
// 14. NOTIFICATIONS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    application_id VARCHAR(50),
    title VARCHAR(200),
    message TEXT,
    type VARCHAR(30) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_application_id (application_id),
    KEY idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ notifications table created/updated\n";


// ============================================
// 15. CONTRACTORS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS contractors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sap_code VARCHAR(50),
    name VARCHAR(200),
    pan VARCHAR(20),
    gst VARCHAR(30),
    contract_no VARCHAR(100),
    project_name VARCHAR(300),
    work_location VARCHAR(300),
    contract_start DATE,
    contract_end DATE,
    contract_value DECIMAL(15,2),
    status VARCHAR(20) DEFAULT 'active',
    labour_license VARCHAR(100),
    epf_code VARCHAR(50),
    esic_code VARCHAR(50),
    mobile VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    state VARCHAR(100),
    pin_code VARCHAR(10),
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    ifsc VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_sap_code (sap_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ contractors table created/updated\n";


// ============================================
// 16. DOCUMENTS TABLE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    doc_name VARCHAR(100),
    doc_type VARCHAR(50),
    file_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_docs_app_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ documents table created/updated\n";


// ============================================
// 18. SAP SYNC QUEUE
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS sap_sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    payload JSON,
    sync_status ENUM('pending', 'in_progress', 'success', 'failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    last_error TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sync_status (sync_status),
    KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ sap_sync_queue table created/updated\n";


// ============================================
// 19. PASS HISTORY & EXTENSIONS
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS temporary_pass_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    pass_no VARCHAR(50),
    old_valid_to DATE,
    new_valid_to DATE,
    extended_by INT,
    approved_by INT,
    extension_reason TEXT,
    extension_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_workman_id (workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ temporary_pass_history table created/updated\n";

$sql = "
CREATE TABLE IF NOT EXISTS pass_extensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    workman_id INT NOT NULL,
    requested_validity DATE,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_app_id (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ pass_extensions table created/updated\n";


// ============================================
// 20. BLOCKING HISTORY
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS contractor_block_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    action ENUM('block', 'unblock') NOT NULL,
    reason TEXT,
    action_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contractor_id (contractor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ contractor_block_history table created/updated\n";

$sql = "
CREATE TABLE IF NOT EXISTS worker_block_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    action ENUM('temporary_block', 'permanent_block', 'unblock') NOT NULL,
    reason TEXT,
    action_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_workman_id (workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ worker_block_history table created/updated\n";


// ============================================
// 21. ACC RETURN LOGS
// ============================================

$sql = "
CREATE TABLE IF NOT EXISTS acc_return_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    acc_no VARCHAR(50),
    return_date DATE,
    received_by INT,
    condition_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_workman_id (workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ acc_return_logs table created/updated\n";


// Helper function to add column if not exists
function addColumnIfNotExists($conn, $table, $column, $definition) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "  + Added column $column to $table\n";
    }
}

// 22. DOCUMENT VERIFICATIONS (GRANULAR)
$sql = "
CREATE TABLE IF NOT EXISTS document_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'reupload_required') DEFAULT 'pending',
    remarks TEXT,
    verified_by INT,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_app_doc (application_id, document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
$conn->query($sql);
echo "✓ document_verifications table ensured\n";

// 23. NOC & TRANSFERS
$sql = "CREATE TABLE IF NOT EXISTS noc_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    from_contractor_id INT NOT NULL,
    to_contractor_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reason TEXT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);
echo "✓ noc_requests table ensured\n";

// 25. COMPLIANCE & ATTENDANCE
$sql = "CREATE TABLE IF NOT EXISTS compliance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    compliance_type VARCHAR(100),
    expiry_date DATE,
    alert_level INT DEFAULT 0,
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);
echo "✓ compliance_alerts table ensured\n";

// 26. AUDIT LOGS - ENSURE REMARKS
$sql = "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    module VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    remarks TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);
addColumnIfNotExists($conn, 'audit_logs', 'old_value', 'TEXT AFTER module');
addColumnIfNotExists($conn, 'audit_logs', 'new_value', 'TEXT AFTER old_value');
addColumnIfNotExists($conn, 'audit_logs', 'remarks', 'TEXT AFTER new_value');
echo "✓ audit_logs table ensured\n";

// Ensure other missing columns in core tables if any
addColumnIfNotExists($conn, 'workmen', 'biometric_status', "ENUM('pending', 'completed') DEFAULT 'pending'");
addColumnIfNotExists($conn, 'workmen', 'training_status', "ENUM('pending', 'passed', 'failed') DEFAULT 'pending'");
addColumnIfNotExists($conn, 'workmen', 'acc_card_number', "VARCHAR(50) AFTER acc_number");

// Contractors table column additions
addColumnIfNotExists($conn, 'contractors', 'epf_esi_exemption_reason', "TEXT DEFAULT NULL");

// Annexure2A table column additions
addColumnIfNotExists($conn, 'annexure2a', 'epf_registered', "VARCHAR(10) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'esi_registered', "VARCHAR(10) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'epf_esi_exemption_reason', "TEXT DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'ecp_number', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'ecp_valid_from', "DATE DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'ecp_valid_to', "DATE DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'workers_ecp', "INT(11) DEFAULT 0");
addColumnIfNotExists($conn, 'annexure2a', 'license_no', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'license_issued', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'issued_date', "DATE DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'expiry_date', "DATE DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'klwf_registration_no', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'contact_person', "VARCHAR(100) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'remarks', "TEXT DEFAULT NULL");


echo "\n✓ Schema initialization complete!\n";

// Close connection
$conn->close();
echo "Database connection closed.\n";
?>

