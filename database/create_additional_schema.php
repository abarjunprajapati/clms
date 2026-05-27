<?php
/**
 * CLMS Additional Schema Migration (Step 3)
 * muster_rolls, worker_transfers, blocks tables.
 */

require_once __DIR__ . '/../include/config.php';

echo "🚀 Creating additional tables...\n";

// 1. MUSTER ROLLS
$muster_sql = "
CREATE TABLE IF NOT EXISTS muster_rolls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    application_id VARCHAR(50),
    month_year VARCHAR(7) NOT NULL,
    file_path VARCHAR(255),
    total_workers INT DEFAULT 0,
    actual_present INT DEFAULT 0,
    status ENUM('pending', 'verified') DEFAULT 'pending',
    verified_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contractor_month (contractor_id, month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (mysqli_query($conn, $muster_sql)) {
    echo "✅ muster_rolls table OK.\n";
}

// 2. WORKER TRANSFERS (NOC)
$transfer_sql = "
CREATE TABLE IF NOT EXISTS worker_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    from_contractor INT,
    to_contractor INT,
    noc_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workman_id) REFERENCES workmen(id),
    INDEX idx_workman (workman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (mysqli_query($conn, $transfer_sql)) {
    echo "✅ worker_transfers table OK.\n";
}

// 3. UNIFIED BLOCKS
$blocks_sql = "
CREATE TABLE IF NOT EXISTS blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('contractor', 'worker') NOT NULL,
    entity_id INT NOT NULL,
    block_reason TEXT NOT NULL,
    blocked_by INT,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unblocked_at TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (mysqli_query($conn, $blocks_sql)) {
    echo "✅ blocks table OK.\n";
}

echo "\n🎉 Additional schema COMPLETE.\n";
?>


