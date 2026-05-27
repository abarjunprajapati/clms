<?php
// Check if we can connect as root for DDL
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$queries = [
    // Update contractors table
    "ALTER TABLE contractors 
        MODIFY COLUMN status ENUM('active', 'inactive', 'blocked', 'suspended', 'pending', 'draft', 'approved', 'rejected') DEFAULT 'active',
        ADD COLUMN is_blocked TINYINT(1) DEFAULT 0,
        ADD COLUMN block_reason VARCHAR(255),
        ADD COLUMN block_remarks TEXT,
        ADD COLUMN blocked_by INT,
        ADD COLUMN blocked_at DATETIME,
        ADD COLUMN activated_by INT,
        ADD COLUMN activated_at DATETIME",
    
    // Update workmen table
    "ALTER TABLE workmen 
        ADD COLUMN blocked_source ENUM('contractor', 'safety', 'disciplinary', 'manual')",
    
    // Create contractor_block_history table
    "CREATE TABLE IF NOT EXISTS contractor_block_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        contractor_id INT,
        action_type ENUM('BLOCK', 'UNBLOCK'),
        reason VARCHAR(255),
        remarks TEXT,
        action_by INT,
        action_at DATETIME,
        ip_address VARCHAR(100),
        sync_status VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Create AttendanceSyncQueue table if not exists
    "CREATE TABLE IF NOT EXISTS attendance_sync_queue (
        id INT PRIMARY KEY AUTO_INCREMENT,
        entity_type VARCHAR(50),
        entity_id INT,
        action VARCHAR(50),
        payload TEXT,
        status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
        retry_count INT DEFAULT 0,
        last_error TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $query) {
    echo "Executing: $query\n";
    if (mysqli_query($conn, $query)) {
        echo "Success\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
