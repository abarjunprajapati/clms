<?php
require_once 'include/config.php';

$sql = [
    // 1. Update workmen table
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS welfare_user_verified TINYINT DEFAULT 0",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS pass_issuer_verified TINYINT DEFAULT 0",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS biometric_status ENUM('pending', 'completed') DEFAULT 'pending'",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS is_blocked TINYINT DEFAULT 0",
    "ALTER TABLE workmen MODIFY COLUMN status ENUM('pending', 'verified', 'temporary_issued', 'acc_generated', 'permanent_active', 'expired', 'rejected', 'reupload_pending') DEFAULT 'pending'",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS worker_type ENUM('contractor', 'supervisor', 'workman') DEFAULT 'workman'",

    // 2. Create workman_documents table
    "CREATE TABLE IF NOT EXISTS workman_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        doc_type VARCHAR(100) NOT NULL,
        file_path VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        remarks TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(workman_id)
    )",

    // 3. Create pass_history table
    "CREATE TABLE IF NOT EXISTS pass_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        pass_type ENUM('temporary', 'permanent') NOT NULL,
        valid_from DATE,
        valid_to DATE,
        extended_from DATE,
        extended_to DATE,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(workman_id)
    )",

    // 4. Create notifications table if not exists (for NotificationEngine)
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'alert',
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($sql as $query) {
    try {
        if (mysqli_query($conn, $query)) {
            echo "Success: " . substr($query, 0, 50) . "...\n";
        } else {
            echo "Error: " . mysqli_error($conn) . " | Query: " . $query . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . " | Query: " . $query . "\n";
    }
}

