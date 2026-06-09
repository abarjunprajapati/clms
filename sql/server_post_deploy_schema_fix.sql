-- CLMS Server Post-Deployment Schema Fix
-- Run this after importing backup.sql and sql/enterprise_governance_v3.sql.
-- This file is intentionally idempotent for ADD COLUMN operations.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

DELIMITER $$

DROP PROCEDURE IF EXISTS clms_add_column_if_missing $$
CREATE PROCEDURE clms_add_column_if_missing(
    IN p_table_name VARCHAR(128),
    IN p_column_name VARCHAR(128),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @sql_text = CONCAT(
            'ALTER TABLE `',
            REPLACE(p_table_name, '`', '``'),
            '` ADD COLUMN `',
            REPLACE(p_column_name, '`', '``'),
            '` ',
            p_column_definition
        );
        PREPARE stmt FROM @sql_text;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS clms_create_index_if_missing $$
CREATE PROCEDURE clms_create_index_if_missing(
    IN p_table_name VARCHAR(128),
    IN p_index_name VARCHAR(128),
    IN p_index_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND INDEX_NAME = p_index_name
    ) THEN
        SET @sql_text = CONCAT(
            'ALTER TABLE `',
            REPLACE(p_table_name, '`', '``'),
            '` ADD INDEX `',
            REPLACE(p_index_name, '`', '``'),
            '` ',
            p_index_definition
        );
        PREPARE stmt FROM @sql_text;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- --------------------------------------------------------
-- Core users and access
-- --------------------------------------------------------

CALL clms_add_column_if_missing('users', 'employee_code', 'VARCHAR(50) NULL');
CALL clms_add_column_if_missing('users', 'contractor_id', 'VARCHAR(50) NULL');
CALL clms_add_column_if_missing('users', 'mobile', 'VARCHAR(20) NULL');
CALL clms_add_column_if_missing('users', 'reset_token', 'VARCHAR(255) NULL');
CALL clms_add_column_if_missing('users', 'reset_expiry', 'DATETIME NULL');
CALL clms_add_column_if_missing('users', 'reset_attempts', 'INT DEFAULT 0');

ALTER TABLE users
    MODIFY role ENUM(
        'contractor',
        'customer',
        'welfare_admin',
        'welfare_user',
        'safety_user',
        'front_line_user',
        'frontline',
        'pass_user',
        'pass_issuer',
        'super_admin',
        'admin',
        'execution_officer',
        'execution'
    ) DEFAULT 'contractor';

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    permission VARCHAR(100) NOT NULL,
    allowed TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_permission (role, permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS super_admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    module VARCHAR(100) NULL,
    target_id INT NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    severity VARCHAR(20) DEFAULT 'info',
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Settings and masters
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('system_settings', 'setting_key', 'VARCHAR(100) NOT NULL UNIQUE');
CALL clms_add_column_if_missing('system_settings', 'setting_value', 'TEXT NULL');
CALL clms_add_column_if_missing('system_settings', 'setting_group', 'VARCHAR(50) DEFAULT ''general''');
CALL clms_add_column_if_missing('system_settings', 'description', 'TEXT NULL');
CALL clms_add_column_if_missing('system_settings', 'updated_by', 'INT NULL');
CALL clms_add_column_if_missing('system_settings', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CREATE TABLE IF NOT EXISTS master_training_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    duration_hours INT DEFAULT 8,
    pass_mark INT DEFAULT 60,
    description VARCHAR(255) NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_venue_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_code VARCHAR(30) NULL,
    venue_name VARCHAR(300) NOT NULL,
    seats INT NOT NULL DEFAULT 35,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_venue_name (venue_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('training_venue_masters', 'venue_code', 'VARCHAR(30) NULL');
CALL clms_add_column_if_missing('training_venue_masters', 'seats', 'INT NOT NULL DEFAULT 35');

CREATE TABLE IF NOT EXISTS safety_instructor_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_code VARCHAR(30) NULL,
    instructor_name VARCHAR(150) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_instructor_name (instructor_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_language_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_name VARCHAR(80) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_training_language (language_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_fee_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_source VARCHAR(20) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_training_fee_source (fee_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_class_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_token VARCHAR(6) NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    training_date DATE NOT NULL,
    venue_id INT NULL,
    venue_name VARCHAR(300) NOT NULL,
    capacity INT NOT NULL DEFAULT 35,
    language_id INT NULL,
    language_name VARCHAR(80) NOT NULL,
    session_name VARCHAR(20) NOT NULL,
    time_from TIME NULL,
    time_to TIME NULL,
    training_type_id INT NULL,
    training_type VARCHAR(100) NOT NULL,
    instructor_id INT NULL,
    instructor_name VARCHAR(150) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_training_batch_token (batch_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_batch_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    training_request_id INT NOT NULL,
    workman_id INT NOT NULL,
    ticked TINYINT(1) NOT NULL DEFAULT 1,
    attempt_no INT NOT NULL DEFAULT 1,
    status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
    created_at DATETIME NULL,
    UNIQUE KEY uq_batch_workman (batch_id, workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO training_language_masters (language_name, status, sort_order, created_at, updated_at)
VALUES
    ('Malayalam', 'active', 10, NOW(), NOW()),
    ('English', 'active', 20, NOW(), NOW()),
    ('Kannada', 'active', 30, NOW(), NOW()),
    ('Tamil', 'active', 40, NOW(), NOW()),
    ('Hindi', 'active', 50, NOW(), NOW());

INSERT IGNORE INTO training_fee_masters (fee_source, amount, status, created_at, updated_at)
VALUES
    ('PWO', 100.00, 'active', NOW(), NOW()),
    ('PO', 0.00, 'active', NOW(), NOW()),
    ('SO', 0.00, 'active', NOW(), NOW());

CREATE TABLE IF NOT EXISTS master_nationalities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nationality VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_nationality (nationality)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS master_religions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    religion VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_religion (religion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS master_state_districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_name VARCHAR(120) NOT NULL,
    district_name VARCHAR(120) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_state_district (state_name, district_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO master_nationalities (nationality, status, created_at, updated_at)
VALUES ('Indian', 'active', NOW(), NOW());

-- --------------------------------------------------------
-- Contractor and document verification
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS contractor_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NULL,
    annexure3a_id INT NULL,
    doc_type VARCHAR(100) NULL,
    file_path VARCHAR(255) NULL,
    original_name VARCHAR(255) NULL,
    status VARCHAR(30) DEFAULT 'pending',
    remarks TEXT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_contractor (contractor_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('contractor_documents', 'annexure3a_id', 'INT NULL');
CALL clms_add_column_if_missing('contractor_documents', 'original_name', 'VARCHAR(255) NULL');
CALL clms_add_column_if_missing('contractor_documents', 'remarks', 'TEXT NULL');
CALL clms_add_column_if_missing('contractor_documents', 'updated_at', 'TIMESTAMP NULL DEFAULT NULL');
ALTER TABLE contractor_documents MODIFY status VARCHAR(30) DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NULL,
    document_type VARCHAR(255) NULL,
    document_name VARCHAR(255) NULL,
    file_path VARCHAR(500) NULL,
    status VARCHAR(30) DEFAULT 'pending',
    remarks TEXT NULL,
    gate_pass_request_id INT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    verified_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('documents', 'remarks', 'TEXT NULL');
CALL clms_add_column_if_missing('documents', 'gate_pass_request_id', 'INT NULL');
CALL clms_add_column_if_missing('documents', 'verified_by', 'INT NULL');
CALL clms_add_column_if_missing('documents', 'verified_at', 'DATETIME NULL');
ALTER TABLE documents MODIFY document_type VARCHAR(255) NULL;
ALTER TABLE documents MODIFY status VARCHAR(30) DEFAULT 'pending';
CALL clms_create_index_if_missing('documents', 'idx_documents_workman', '(workman_id)');
CALL clms_create_index_if_missing('documents', 'idx_documents_gatepass_request', '(gate_pass_request_id)');

CREATE TABLE IF NOT EXISTS document_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NULL,
    document_id INT NULL,
    document_type VARCHAR(255) NULL,
    status VARCHAR(50) DEFAULT 'pending',
    remarks TEXT NULL,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('document_verifications', 'status', 'VARCHAR(50) DEFAULT ''pending''');

CREATE TABLE IF NOT EXISTS verification_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NULL,
    checklist_item VARCHAR(255) NULL,
    status VARCHAR(50) DEFAULT 'pending',
    remarks TEXT NULL,
    checked_by INT NULL,
    checked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Workflow and application status
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS application_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT NULL,
    current_stage VARCHAR(50) DEFAULT 'submitted',
    welfare_status VARCHAR(50) DEFAULT 'pending',
    safety_status VARCHAR(50) DEFAULT 'pending',
    pass_status VARCHAR(50) DEFAULT 'pending',
    acc_status VARCHAR(50) DEFAULT 'pending',
    final_status VARCHAR(50) DEFAULT 'pending',
    overall_status VARCHAR(50) DEFAULT 'submitted',
    remarks TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application_workflow_app (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workflow_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NULL,
    action VARCHAR(80) NULL,
    user_id INT NULL,
    user_role VARCHAR(50) NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS remarks_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NULL,
    action VARCHAR(80) NULL,
    remarks TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Worker enrolment
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS workmen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_no VARCHAR(50) NULL,
    contractor_id INT NULL,
    name VARCHAR(150) NULL,
    aadhaar VARCHAR(20) NULL,
    mobile VARCHAR(20) NULL,
    status VARCHAR(50) DEFAULT 'pending',
    training_status VARCHAR(50) DEFAULT 'pending',
    safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING',
    execution_training_status VARCHAR(30) DEFAULT 'pending',
    nationality VARCHAR(100) NULL DEFAULT 'Indian',
    acc_number VARCHAR(50) NULL,
    temp_pass_no VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('workmen', 'training_status', 'VARCHAR(50) DEFAULT ''pending''');
CALL clms_add_column_if_missing('workmen', 'safety_training_status', 'VARCHAR(50) DEFAULT ''PENDING_TRAINING''');
CALL clms_add_column_if_missing('workmen', 'execution_training_status', 'VARCHAR(30) DEFAULT ''pending''');
CALL clms_add_column_if_missing('workmen', 'safety_language', 'VARCHAR(50) NULL');
CALL clms_add_column_if_missing('workmen', 'nationality', 'VARCHAR(100) NULL DEFAULT ''Indian''');
CALL clms_add_column_if_missing('workmen', 'pass_issuer_verified', 'TINYINT(1) DEFAULT 0');
CALL clms_add_column_if_missing('workmen', 'temp_pass_status', 'TINYINT(1) DEFAULT 0');
CALL clms_add_column_if_missing('workmen', 'temp_pass_no', 'VARCHAR(50) NULL');
CALL clms_add_column_if_missing('workmen', 'temp_valid_from', 'DATE NULL');
CALL clms_add_column_if_missing('workmen', 'temp_valid_to', 'DATE NULL');
CALL clms_add_column_if_missing('workmen', 'acc_number', 'VARCHAR(50) NULL');
CALL clms_add_column_if_missing('workmen', 'acc_card_number', 'VARCHAR(50) NULL');
CALL clms_add_column_if_missing('workmen', 'valid_from', 'DATE NULL');
CALL clms_add_column_if_missing('workmen', 'valid_to', 'DATE NULL');
ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING';
ALTER TABLE workmen MODIFY COLUMN execution_training_status VARCHAR(30) DEFAULT 'pending';

-- --------------------------------------------------------
-- Training
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS training_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NULL,
    contractor_id INT NULL,
    requested_by INT NULL,
    training_type VARCHAR(100) DEFAULT 'Safety Induction',
    status VARCHAR(50) DEFAULT 'pending',
    remarks TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('training_requests', 'training_type', 'VARCHAR(100) DEFAULT ''Safety Induction''');
CALL clms_add_column_if_missing('training_requests', 'reviewed_by', 'INT NULL');
CALL clms_add_column_if_missing('training_requests', 'reviewed_at', 'DATETIME NULL');
ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE training_requests MODIFY COLUMN training_type VARCHAR(100) DEFAULT 'Safety Induction';

CREATE TABLE IF NOT EXISTS training_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_type VARCHAR(100) DEFAULT 'Safety Induction',
    venue VARCHAR(255) NULL,
    trainer_name VARCHAR(150) NULL,
    schedule_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    capacity INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE training_schedule MODIFY COLUMN training_type VARCHAR(100) DEFAULT 'Safety Induction';

CREATE TABLE IF NOT EXISTS training_session_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    workman_id INT NOT NULL,
    attendance_status VARCHAR(30) DEFAULT 'pending',
    result_status VARCHAR(30) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session_worker (session_id, workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NULL,
    workman_id INT NULL,
    written_marks DECIMAL(5,2) DEFAULT 0,
    practical_marks DECIMAL(5,2) DEFAULT 0,
    total_marks DECIMAL(5,2) DEFAULT 0,
    result VARCHAR(30) DEFAULT 'pending',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Gate pass, temporary pass, ACC and transfer
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS gate_pass_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_no VARCHAR(50) NULL,
    contractor_id INT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gate_pass_request_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    workman_id INT NOT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_gate_pass_request_worker (request_id, workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gate_pass_requests MODIFY status VARCHAR(30) DEFAULT 'pending';
ALTER TABLE gate_pass_request_workers MODIFY status VARCHAR(30) DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS gate_pass_document_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type VARCHAR(255) NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS temporary_pass_validities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    validity_days INT NOT NULL DEFAULT 7,
    validity_from_date DATE NOT NULL,
    validity_to_date DATE NOT NULL DEFAULT '9999-12-31',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO temporary_pass_validities
    (id, validity_days, validity_from_date, validity_to_date, status, created_at, updated_at)
VALUES
    (1, 7, CURDATE(), '9999-12-31', 'active', NOW(), NOW());

CREATE TABLE IF NOT EXISTS acc_attendance_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acc_number VARCHAR(50) NOT NULL,
    worker_id INT NOT NULL,
    attendance_device_id VARCHAR(100) NULL,
    status VARCHAR(30) DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_acc_worker (acc_number, worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS acc_return_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    acc_no VARCHAR(50) DEFAULT NULL,
    return_date DATE DEFAULT NULL,
    received_by INT DEFAULT NULL,
    condition_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permanent_gate_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_no VARCHAR(50) NOT NULL UNIQUE,
    worker_id INT NULL,
    application_id VARCHAR(50) NULL,
    contractor_id INT NULL,
    valid_from DATE NULL,
    valid_till DATE NULL,
    status VARCHAR(30) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS worker_transfer_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    from_contractor_id INT NOT NULL,
    to_contractor_id INT DEFAULT NULL,
    noc_id INT DEFAULT NULL,
    noc_reference VARCHAR(100) DEFAULT NULL,
    transfer_type VARCHAR(20) DEFAULT 'noc',
    status VARCHAR(20) DEFAULT 'completed',
    approved_by INT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS biometric_aadhaar_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    aadhaar VARCHAR(20) NULL,
    biometric_ref VARCHAR(100) NULL,
    captured_by INT NULL,
    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bio_workman (workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Compliance, wage and pass policy
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS certified_wage_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    wage_from_date DATE NOT NULL,
    wage_to_date DATE NOT NULL DEFAULT '9999-12-31',
    wage_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS age_range_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_age INT NOT NULL DEFAULT 18,
    max_age INT NOT NULL DEFAULT 60,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    effective_from DATE NOT NULL,
    effective_to DATE NOT NULL DEFAULT '9999-12-31',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO age_range_mappings
    (id, min_age, max_age, status, effective_from, effective_to, created_at, updated_at)
VALUES
    (1, 18, 60, 'active', CURDATE(), '9999-12-31', NOW(), NOW());

CREATE TABLE IF NOT EXISTS labour_license_thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threshold_value INT NOT NULL DEFAULT 20,
    threshold_from_date DATE NOT NULL,
    threshold_to_date DATE NOT NULL DEFAULT '9999-12-31',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pass_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_type VARCHAR(100) NOT NULL,
    max_allowed INT NOT NULL DEFAULT 0,
    rule VARCHAR(150) NOT NULL DEFAULT 'Fixed',
    description TEXT DEFAULT NULL,
    ratio_per_workmen INT DEFAULT NULL,
    override_allowed TINYINT(1) NOT NULL DEFAULT 1,
    current_count INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL clms_add_column_if_missing('pass_limits', 'rule', 'VARCHAR(150) NOT NULL DEFAULT ''Fixed''');
CALL clms_add_column_if_missing('pass_limits', 'description', 'TEXT DEFAULT NULL');
CALL clms_add_column_if_missing('pass_limits', 'ratio_per_workmen', 'INT DEFAULT NULL');
CALL clms_add_column_if_missing('pass_limits', 'override_allowed', 'TINYINT(1) NOT NULL DEFAULT 1');
CALL clms_add_column_if_missing('pass_limits', 'current_count', 'INT DEFAULT 0');
CALL clms_add_column_if_missing('pass_limits', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CREATE TABLE IF NOT EXISTS training_payment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    gst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) DEFAULT 'pending',
    payment_reference VARCHAR(100) NULL,
    gateway_order_id VARCHAR(100) NULL,
    gateway_payment_id VARCHAR(100) NULL,
    remarks TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_payment_request_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_request_id INT NOT NULL,
    workman_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payment_worker (payment_request_id, workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- SAP and notification support
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS sap_integration_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NULL,
    entity_id VARCHAR(100) NULL,
    action VARCHAR(100) NULL,
    payload JSON NULL,
    status VARCHAR(30) DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sap_sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    payload JSON NULL,
    status VARCHAR(30) DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(150) NULL,
    recipient_name VARCHAR(150) NULL,
    channel VARCHAR(30) NULL,
    type VARCHAR(50) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role_target VARCHAR(50) NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    type VARCHAR(50) DEFAULT 'info',
    related_id VARCHAR(100) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Final cleanup
-- --------------------------------------------------------

DROP PROCEDURE IF EXISTS clms_add_column_if_missing;
DROP PROCEDURE IF EXISTS clms_create_index_if_missing;
