<?php
include 'include/config.php';

$queries = [
"CREATE TABLE IF NOT EXISTS worker_master (
    worker_id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT,
    vendor_code VARCHAR(50),
    department_id INT,
    work_order_id INT,
    acc_no VARCHAR(50),
    pass_no VARCHAR(50),
    pass_type VARCHAR(50),
    worker_type VARCHAR(50),
    trade VARCHAR(100),
    skill_category VARCHAR(50),
    qualification VARCHAR(100),
    aadhaar_no VARCHAR(20) UNIQUE,
    mobile_no VARCHAR(20),
    email VARCHAR(100),
    blood_group VARCHAR(10),
    dob DATE,
    photo VARCHAR(255),
    biometric_status VARCHAR(50) DEFAULT 'Pending',
    attendance_status VARCHAR(50) DEFAULT 'Inactive',
    safety_status VARCHAR(50) DEFAULT 'Pending',
    verification_status VARCHAR(50) DEFAULT 'Pending',
    worker_status VARCHAR(50) DEFAULT 'Draft',
    blocked_reason TEXT,
    blocked_at DATETIME NULL,
    blocked_by INT,
    deleted_at DATETIME NULL,
    deleted_by INT,
    delete_reason TEXT,
    created_by INT,
    updated_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (aadhaar_no),
    INDEX (acc_no),
    INDEX (contractor_id),
    INDEX (worker_status),
    INDEX (verification_status)
)",
"CREATE TABLE IF NOT EXISTS worker_qualifications (
    qualification_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    education_level VARCHAR(100),
    trade_name VARCHAR(100),
    skill_grade VARCHAR(50),
    certificate_no VARCHAR(100),
    institute_name VARCHAR(200),
    year_of_passing INT,
    experience_years INT,
    previous_company VARCHAR(200),
    valid_from DATE NULL,
    valid_to DATE NULL,
    verification_status VARCHAR(50) DEFAULT 'Pending',
    verified_by INT,
    verified_at DATETIME NULL,
    remarks TEXT
)",
"CREATE TABLE IF NOT EXISTS worker_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    document_type VARCHAR(100),
    document_number VARCHAR(100),
    document_path VARCHAR(255),
    expiry_date DATE NULL,
    verification_status VARCHAR(50) DEFAULT 'Pending',
    rejection_reason TEXT,
    reupload_requested BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at DATETIME NULL,
    uploaded_by INT,
    version_no INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_passes (
    pass_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    pass_status VARCHAR(50) DEFAULT 'Draft',
    acc_status VARCHAR(50) DEFAULT 'Not Generated',
    biometric_linked BOOLEAN DEFAULT FALSE,
    issued_by INT,
    approved_by INT,
    cancelled_by INT,
    cancel_reason TEXT,
    extended_till DATE NULL,
    reissued_from INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS safety_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(100),
    trainer VARCHAR(100),
    venue VARCHAR(100),
    capacity INT,
    start_time DATETIME,
    end_time DATETIME,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_safety (
    safety_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    batch_no INT,
    trainer_name VARCHAR(100),
    marks_obtained INT NULL,
    result VARCHAR(50) DEFAULT 'Pending',
    validity_date DATE NULL,
    rescheduled_count INT DEFAULT 0,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    attendance_date DATE,
    in_time TIME,
    out_time TIME,
    shift VARCHAR(50),
    device_id VARCHAR(50),
    location VARCHAR(100),
    sync_status VARCHAR(50),
    manual_override BOOLEAN DEFAULT FALSE,
    override_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (attendance_date)
)",
"CREATE TABLE IF NOT EXISTS worker_audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    module_name VARCHAR(100),
    action_type VARCHAR(50),
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(50),
    browser_info TEXT,
    remarks TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_block_history (
    block_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    block_type VARCHAR(50),
    reason TEXT,
    blocked_by INT,
    blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unblocked_by INT NULL,
    unblocked_at DATETIME NULL,
    remarks TEXT
)",
"CREATE TABLE IF NOT EXISTS worker_transfer_history (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    old_contractor_id INT,
    new_contractor_id INT,
    noc_document VARCHAR(255),
    approved_by INT,
    approved_at DATETIME NULL,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    type VARCHAR(50),
    message TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    sent_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_biometric_sync (
    sync_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    device_id VARCHAR(50),
    sync_type VARCHAR(50),
    sync_status VARCHAR(50) DEFAULT 'Pending',
    sync_response TEXT,
    synced_at DATETIME NULL,
    retry_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS worker_pass_print_logs (
    print_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT,
    pass_id INT,
    printed_by INT,
    printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    print_count INT DEFAULT 1,
    printer_name VARCHAR(100)
)"
];

foreach ($queries as $sql) {
    try {
        mysqli_query($conn, $sql);
        echo "Executed successfully.\n";
    } catch (Exception $e) {
        echo "Error executing query: " . $e->getMessage() . "\n";
    }
}
