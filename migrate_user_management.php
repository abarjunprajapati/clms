<?php
/**
 * Schema Migration: Add must_change_password column to users table
 * Run this once to add the required column for first-time password setup
 */
require_once __DIR__ . '/include/config.php';

echo "<pre>\n";
echo "=== CLMS Schema Migration: must_change_password ===\n\n";

// 1. Add must_change_password column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
if ($result && $result->num_rows === 0) {
    $sql = "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) DEFAULT 0 AFTER status";
    if ($conn->query($sql)) {
        echo "✓ Added 'must_change_password' column to users table\n";
    } else {
        echo "✗ Failed to add column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'must_change_password' column already exists\n";
}

// 2. Ensure login_logs table exists
$sql = "CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    identifier VARCHAR(100),
    ip_address VARCHAR(45),
    status ENUM('success', 'failed') DEFAULT 'failed',
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_status (status),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if ($conn->query($sql)) {
    echo "✓ login_logs table ensured\n";
} else {
    echo "✗ login_logs error: " . $conn->error . "\n";
}

// 3. Update stored procedure to include must_change_password
$conn->query("DROP PROCEDURE IF EXISTS sp_authenticate_user");
$sp = "
CREATE PROCEDURE sp_authenticate_user(IN p_contractor_id VARCHAR(50))
BEGIN
    SELECT id, contractor_id, name, email, mobile, role, password, status, must_change_password
    FROM users
    WHERE contractor_id = p_contractor_id AND status = 'active'
    LIMIT 1;
END
";
if ($conn->query($sp)) {
    echo "✓ Stored procedure sp_authenticate_user updated (includes must_change_password)\n";
} else {
    echo "✗ SP error: " . $conn->error . "\n";
}

// 4. Ensure audit_logs table has required columns
$sql = "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    module VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    remarks TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);
echo "✓ audit_logs table ensured\n";

echo "\n=== Migration Complete! ===\n";
echo "</pre>";

$conn->close();
?>
