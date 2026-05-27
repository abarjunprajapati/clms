-- Execution Officer Module Migration (PDF-Correct Version)

-- 1. Table for Formal Escalations
CREATE TABLE IF NOT EXISTS execution_escalations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT NOT NULL,
    escalation_type VARCHAR(100), -- e.g., 'Safety Violation', 'Unauthorized Access'
    contractor_id BIGINT,
    workman_id BIGINT,
    severity ENUM('low','medium','high','critical') DEFAULT 'medium',
    remarks TEXT,
    escalated_to VARCHAR(50), -- 'welfare', 'safety', 'admin'
    status ENUM('open','in_progress','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table for Productivity Tracking
CREATE TABLE IF NOT EXISTS execution_productivity_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    contractor_id BIGINT NOT NULL,
    work_order_id BIGINT,
    total_workers INT DEFAULT 0,
    active_workers INT DEFAULT 0,
    idle_workers INT DEFAULT 0,
    attendance_percent DECIMAL(5,2) DEFAULT 0.00,
    productivity_score DECIMAL(5,2) DEFAULT 0.00,
    log_date DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table for Attendance Exceptions
CREATE TABLE IF NOT EXISTS attendance_exceptions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    workman_id BIGINT,
    exception_type VARCHAR(100), -- 'Ghost Attendance', 'No Deployment', 'Blocked Worker'
    exception_date DATE DEFAULT (CURRENT_DATE),
    remarks TEXT,
    status ENUM('open','resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Recommendations table (for Reassignments)
CREATE TABLE IF NOT EXISTS execution_recommendations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT NOT NULL,
    workman_id BIGINT NOT NULL,
    current_location VARCHAR(100),
    recommended_location VARCHAR(100),
    reason TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
