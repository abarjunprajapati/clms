<?php
include 'include/config.php';

$queries = [
    // Applications table (central tracking)
    "CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_no VARCHAR(50) UNIQUE,
        contractor_id INT,
        type ENUM('registration', 'enrollment', 'gate_pass') NOT NULL,
        current_status VARCHAR(50) DEFAULT 'draft',
        rejection_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Notifications table
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message TEXT,
        type VARCHAR(20) DEFAULT 'alert',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Audit Logs table
    "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255),
        module VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Missing columns in workmen
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS temp_id VARCHAR(50) AFTER id",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS acc_number VARCHAR(50) AFTER temp_id",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS biometric_status VARCHAR(20) DEFAULT 'pending' AFTER status",
    "ALTER TABLE workmen MODIFY COLUMN status ENUM('active','inactive','blocked','temp','trained','verified','acc_generated') DEFAULT 'temp'",

    // Missing columns in gate_pass_requests
    "ALTER TABLE gate_pass_requests ADD COLUMN IF NOT EXISTS pass_type ENUM('Contractor', 'Supervisor', 'Workmen') AFTER contractor_id",
    "ALTER TABLE gate_pass_requests ADD COLUMN IF NOT EXISTS rejection_reason TEXT AFTER status",

    // Missing columns in gate_passes (for lifecycle)
    "ALTER TABLE gate_passes ADD COLUMN IF NOT EXISTS valid_from DATE AFTER status",
    "ALTER TABLE gate_passes ADD COLUMN IF NOT EXISTS valid_to DATE AFTER valid_from",
    "ALTER TABLE gate_passes ADD COLUMN IF NOT EXISTS extended_until DATE AFTER valid_to",

    // Training Schedule table (if not exists or needs update)
    "CREATE TABLE IF NOT EXISTS training_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_date DATE,
        session_time TIME,
        location VARCHAR(255),
        capacity INT,
        enrolled_count INT DEFAULT 0,
        status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Success: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error: " . $conn->error . " in query: " . substr($sql, 0, 50) . "...\n";
    }
}
echo "Migration completed.\n";

