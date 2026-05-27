<?php
/**
 * CLMS Compliance Uploads Schema Migration
 * Creates compliance_uploads table for ECR/ESI/KLWF verification.
 */

require_once __DIR__ . '/../include/config.php';

echo "🚀 Creating compliance_uploads table...\n";

// 1. CREATE TABLE
$create_sql = "
CREATE TABLE IF NOT EXISTS compliance_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    upload_type ENUM('ecr', 'esi', 'klwf') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    month_year VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    INDEX idx_contractor (contractor_id),
    INDEX idx_month_type (month_year, upload_type),
    INDEX idx_status (status),
    UNIQUE KEY unique_upload (contractor_id, upload_type, month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if (mysqli_query($conn, $create_sql)) {
    echo "✅ Table 'compliance_uploads' created/verified.\n";
} else {
    echo "❌ Table creation failed: " . mysqli_error($conn) . "\n";
    exit(1);
}

// 2. ADD COLUMNS TO WORKMEN if missing (compliance_status)
$check_col = mysqli_query($conn, "
    SELECT COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='workmen' AND COLUMN_NAME='compliance_status'
");
if (mysqli_num_rows($check_col) == 0) {
    $alter_sql = "ALTER TABLE workmen 
        ADD COLUMN compliance_status ENUM('pending', 'verified', 'non_compliant') DEFAULT 'pending' AFTER training_status,
        ADD COLUMN last_compliance_month VARCHAR(7) DEFAULT NULL AFTER compliance_status";
    if (mysqli_query($conn, $alter_sql)) {
        echo "✅ Added compliance_status columns to workmen.\n";
    } else {
        echo "⚠️ Failed to add compliance columns: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "ℹ️ compliance_status column already exists.\n";
}

// 3. ADD TO CONTRACTORS if missing
$check_c_col = mysqli_query($conn, "
    SELECT COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='contractors' AND COLUMN_NAME='compliance_status'
");
if (mysqli_num_rows($check_c_col) == 0) {
    $alter_c_sql = "ALTER TABLE contractors 
        ADD COLUMN compliance_status ENUM('pending', 'verified', 'non_compliant') DEFAULT 'pending' AFTER status";
    if (mysqli_query($conn, $alter_c_sql)) {
        echo "✅ Added compliance_status to contractors.\n";
    } else {
        echo "⚠️ Failed to add contractor compliance column.\n";
    }
}

echo "\n Compliance schema migration COMPLETE.\n";
echo "Run: php database/create_compliance_schema.php\n";
?>


