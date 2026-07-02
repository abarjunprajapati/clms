<?php
require_once __DIR__ . '/../include/config.php';

echo "<h1>Database Migration Tool</h1>";

try {
    $conn->begin_transaction();

    // 1. Check if 'is_in_common_pool' exists in 'workmen'
    $res = $conn->query("SHOW COLUMNS FROM workmen LIKE 'is_in_common_pool'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE workmen ADD COLUMN is_in_common_pool TINYINT(4) DEFAULT 0 AFTER intended_unblock_reason");
        echo "<p>Added 'is_in_common_pool' to workmen table.</p>";
    } else {
        echo "<p>'is_in_common_pool' already exists in workmen.</p>";
    }

    // 2. Check if 'noc_issued_at' exists in 'workmen'
    $res = $conn->query("SHOW COLUMNS FROM workmen LIKE 'noc_issued_at'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE workmen ADD COLUMN noc_issued_at TIMESTAMP NULL AFTER is_in_common_pool");
        echo "<p>Added 'noc_issued_at' to workmen table.</p>";
    } else {
        echo "<p>'noc_issued_at' already exists in workmen.</p>";
    }
    
    // 3. Create noc_requests table if it doesn't exist, or update it
    $res = $conn->query("SHOW TABLES LIKE 'noc_requests'");
    if ($res->num_rows == 0) {
        $conn->query("
            CREATE TABLE noc_requests (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                workman_id INT(11) NOT NULL,
                requesting_contractor_id INT(11) NOT NULL,
                existing_contractor_id INT(11) NULL,
                noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending',
                welfare_user_id INT(11) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (workman_id) REFERENCES workmen(id),
                FOREIGN KEY (requesting_contractor_id) REFERENCES contractors(user_id)
            )
        ");
        echo "<p>Created 'noc_requests' table.</p>";
    } else {
        // Check if noc_status exists
        $res = $conn->query("SHOW COLUMNS FROM noc_requests LIKE 'noc_status'");
        if ($res->num_rows == 0) {
            $conn->query("ALTER TABLE noc_requests CHANGE status noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending'");
            echo "<p>Renamed 'status' to 'noc_status' in noc_requests.</p>";
        } else {
            // Update enum if needed
            $conn->query("ALTER TABLE noc_requests MODIFY noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending'");
            echo "<p>'noc_status' already exists in noc_requests, updated enum.</p>";
        }
    }

    $conn->commit();
    echo "<h2 style='color:green;'>Migration Completed Successfully!</h2>";
    echo "<p>You can now delete this file and continue testing.</p>";

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo "<h2 style='color:red;'>Migration Failed</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
