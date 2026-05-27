-- Final Completion for Execution Officer Module

-- 1. Observations Table
CREATE TABLE IF NOT EXISTS execution_observations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT NOT NULL,
    contractor_id BIGINT NOT NULL,
    workman_id BIGINT,
    work_order_id BIGINT,
    observation_type VARCHAR(100),
    remarks TEXT,
    severity ENUM('low', 'medium', 'high') DEFAULT 'low',
    action_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Deployment Map Table
CREATE TABLE IF NOT EXISTS execution_worker_deployments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT NOT NULL,
    workman_id BIGINT NOT NULL,
    contractor_id BIGINT NOT NULL,
    department_id BIGINT,
    work_order_id BIGINT,
    shift VARCHAR(10),
    location_details VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    deployed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_deployment (workman_id, status)
);

-- 3. Execution Notifications
CREATE TABLE IF NOT EXISTS execution_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    recipient_role VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Audit Logs
CREATE TABLE IF NOT EXISTS execution_audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    action VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id BIGINT,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Officer to Contractor Mapping
CREATE TABLE IF NOT EXISTS execution_officer_contractors (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT NOT NULL,
    contractor_id BIGINT NOT NULL,
    work_order_id BIGINT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (execution_officer_id, contractor_id, work_order_id)
);
