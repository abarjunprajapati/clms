<?php
require_once __DIR__ . '/include/config.php';

try {
    // 1. Modify `workmen` table
    $alter_workmen_1 = "ALTER TABLE workmen 
        ADD COLUMN block_status ENUM('none', 'blocked_contractor', 'blocked_temporary_welfare', 'blocked_permanent_welfare', 'blocked_system_idle') DEFAULT 'none',
        ADD COLUMN block_reason TEXT,
        ADD COLUMN blocked_by INT,
        ADD COLUMN blocked_at TIMESTAMP NULL,
        ADD COLUMN to_be_unblocked TINYINT DEFAULT 0,
        ADD COLUMN intended_unblock_reason TEXT";
    $conn->query($alter_workmen_1);
    echo "Workmen block columns added.\n";
} catch (Exception $e) {
    echo "Workmen block columns error: " . $e->getMessage() . "\n";
}

try {
    $alter_workmen_2 = "ALTER TABLE workmen 
        ADD COLUMN is_in_common_pool TINYINT DEFAULT 0,
        ADD COLUMN noc_issued_at TIMESTAMP NULL,
        ADD COLUMN last_punch_date DATE NULL";
    $conn->query($alter_workmen_2);
    echo "Workmen pool columns added.\n";
} catch (Exception $e) {
    echo "Workmen pool columns error: " . $e->getMessage() . "\n";
}

try {
    // 2. Modify `users` table for contractor block
    $alter_users = "ALTER TABLE users 
        ADD COLUMN block_status ENUM('none', 'blocked_welfare', 'blocked_system_idle') DEFAULT 'none',
        ADD COLUMN block_reason TEXT";
    $conn->query($alter_users);
    echo "Users block columns added.\n";
} catch (Exception $e) {
    echo "Users block columns error: " . $e->getMessage() . "\n";
}

try {
    // 3. Create `noc_requests` table
    $create_noc = "CREATE TABLE IF NOT EXISTS noc_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workman_id INT NOT NULL,
        requesting_contractor_id INT NOT NULL,
        existing_contractor_id INT,
        noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending',
        welfare_user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (workman_id) REFERENCES workmen(id),
        FOREIGN KEY (requesting_contractor_id) REFERENCES users(id)
    )";
    $conn->query($create_noc);
    echo "NOC requests table created.\n";
} catch (Exception $e) {
    echo "NOC requests table error: " . $e->getMessage() . "\n";
}

echo "Schema update complete!\n";
?>
