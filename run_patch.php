<?php
require_once __DIR__ . '/include/config.php';

$queries = [
    // Add workflow columns
    "ALTER TABLE annexure2a ADD COLUMN IF NOT EXISTS workflow_status VARCHAR(50) DEFAULT 'submitted'",
    "ALTER TABLE annexure2a ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'submitted'",
    "ALTER TABLE annexure2a ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE annexure2a ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    
    // Application Logs Table
    "CREATE TABLE IF NOT EXISTS application_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50),
        status VARCHAR(50),
        action_by VARCHAR(50),
        role VARCHAR(50),
        remarks TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Training Sessions
    "CREATE TABLE IF NOT EXISTS training_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(50) UNIQUE,
        application_id VARCHAR(50),
        venue VARCHAR(255),
        date DATE,
        time VARCHAR(20),
        trainer VARCHAR(100),
        capacity INT DEFAULT 50,
        enrolled_count INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Training Results
    "CREATE TABLE IF NOT EXISTS training_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50),
        workman_id INT,
        training_session_id VARCHAR(50),
        attendance_status VARCHAR(20) DEFAULT 'present',
        result VARCHAR(20) DEFAULT 'pending',
        theory_score INT DEFAULT 0,
        practical_score INT DEFAULT 0,
        total_score INT DEFAULT 0,
        certificate_no VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Workmen table updates
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS marital_status VARCHAR(20) AFTER gender",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS esic_number VARCHAR(50) AFTER aadhaar",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS uan_number VARCHAR(50) AFTER esic_number",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS mobile VARCHAR(15) AFTER uan_number",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS acc_number VARCHAR(50) AFTER temp_id",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS ifsc VARCHAR(20) AFTER acc_number",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS education VARCHAR(100) AFTER district",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS nature_of_work VARCHAR(300) AFTER department",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS allowance DECIMAL(10,2) DEFAULT 0.00 AFTER wage_rate",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS work_location VARCHAR(300) AFTER nature_of_work",
    "ALTER TABLE workmen MODIFY wage_type ENUM('daily','weekly','monthly') DEFAULT 'daily'",

    // Gate Passes
    "CREATE TABLE IF NOT EXISTS gate_passes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50),
        workman_id INT,
        pass_type VARCHAR(20),
        pass_no VARCHAR(50),
        status VARCHAR(30) DEFAULT 'pending',
        acc_number VARCHAR(50),
        valid_from DATE,
        valid_till DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Application Workflow (if used instead of annexure2a directly)
    "CREATE TABLE IF NOT EXISTS application_workflow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id VARCHAR(50) UNIQUE,
        current_stage VARCHAR(50) DEFAULT 'draft',
        workflow_status VARCHAR(50) DEFAULT 'draft',
        training_status VARCHAR(50) DEFAULT 'pending',
        gatepass_status VARCHAR(50) DEFAULT 'pending',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $sql) {
    try {
        db_execute($conn, $sql);
        echo "Executed: " . substr($sql, 0, 50) . "...\n";
    } catch (Exception $e) {
        // Ignore duplicate column errors
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
echo "Database Patch Complete!\n";

