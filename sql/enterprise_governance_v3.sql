-- Enterprise Governance Schema v3 (Full)
-- 100% PDF Compliance Layer

-- 1. Rule Engine (Policy Brain)
CREATE TABLE IF NOT EXISTS business_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    rule_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rule_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT,
    source_module VARCHAR(50), 
    condition_key VARCHAR(50), 
    operator VARCHAR(20), 
    threshold_value VARCHAR(100),
    FOREIGN KEY (rule_id) REFERENCES business_rules(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rule_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_id INT,
    target_module VARCHAR(50), 
    action_type VARCHAR(50), 
    FOREIGN KEY (rule_id) REFERENCES business_rules(id) ON DELETE CASCADE
);

-- 2. Workflow Engine
CREATE TABLE IF NOT EXISTS workflow_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_type VARCHAR(50), -- 'contractor_reg', 'pass_issue', etc.
    target_id INT, -- ID of the entity (contractor_id, workman_id, etc.)
    current_step_id INT,
    status ENUM('pending', 'approved', 'rejected', 'correction_required') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS workflow_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_instance_id INT,
    step_id INT,
    rejected_by INT,
    reason TEXT,
    correction_notes TEXT,
    resubmitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Immutable Audit Logs
-- Create if not exists, otherwise we'll handle the alter separately
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type VARCHAR(50),
    description TEXT,
    ip_address VARCHAR(45),
    hash_signature VARCHAR(255),
    previous_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. AMC / SLA Management
CREATE TABLE IF NOT EXISTS amc_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT,
    contract_number VARCHAR(100),
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'expired', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS amc_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT,
    severity ENUM('S1', 'S2', 'S3') DEFAULT 'S3',
    subject VARCHAR(255),
    description TEXT,
    status ENUM('open', 'in_progress', 'resolved', 'closed', 'paused') DEFAULT 'open',
    assigned_to INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ticket_pause_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT,
    pause_reason VARCHAR(100), 
    paused_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resumed_at TIMESTAMP NULL,
    total_duration_minutes INT DEFAULT 0
);

-- 5. Payment Governance
CREATE TABLE IF NOT EXISTS contractor_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT,
    invoice_number VARCHAR(100),
    invoice_date DATE,
    milestone_id INT,
    gross_amount DECIMAL(15,2),
    gst_amount DECIMAL(15,2) DEFAULT 0.00,
    tds_amount DECIMAL(15,2) DEFAULT 0.00,
    net_payable DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('pending', 'verified', 'approved', 'paid', 'held') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payment_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT,
    milestone_name VARCHAR(100),
    percentage DECIMAL(5,2),
    is_completed TINYINT(1) DEFAULT 0,
    completed_at DATE NULL
);

-- 6. API Security
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    token VARCHAR(500) NOT NULL,
    refresh_token VARCHAR(500),
    device_id VARCHAR(100),
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS api_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    device_id VARCHAR(100) UNIQUE,
    device_name VARCHAR(100),
    os_version VARCHAR(50),
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Temporary Workforce
CREATE TABLE IF NOT EXISTS temporary_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_name VARCHAR(100) NOT NULL,
    purpose VARCHAR(255),
    valid_from DATE,
    valid_to DATE,
    status ENUM('pending', 'approved', 'rejected', 'expired', 'blocked') DEFAULT 'pending',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Productivity Logs
CREATE TABLE IF NOT EXISTS productivity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT,
    workman_id INT,
    date DATE,
    hours_worked DECIMAL(5,2),
    output_units INT,
    efficiency_score INT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
