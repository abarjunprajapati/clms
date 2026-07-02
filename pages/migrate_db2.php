<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../include/config.php';

echo "<h1>Robust Migration Tool</h1>";

try {
    // 1. Check if 'is_in_common_pool' exists in 'workmen'
    $res = $conn->query("SHOW COLUMNS FROM workmen LIKE 'is_in_common_pool'");
    if ($res->num_rows == 0) {
        if ($conn->query("ALTER TABLE workmen ADD COLUMN is_in_common_pool TINYINT(4) DEFAULT 0")) {
            echo "<p style='color:green;'>SUCCESS: Added 'is_in_common_pool' to workmen table.</p>";
        } else {
            echo "<p style='color:red;'>FAILED to add 'is_in_common_pool': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:blue;'>'is_in_common_pool' already exists in workmen.</p>";
    }

    // 2. Check if 'noc_issued_at' exists in 'workmen'
    $res = $conn->query("SHOW COLUMNS FROM workmen LIKE 'noc_issued_at'");
    if ($res->num_rows == 0) {
        if ($conn->query("ALTER TABLE workmen ADD COLUMN noc_issued_at TIMESTAMP NULL")) {
            echo "<p style='color:green;'>SUCCESS: Added 'noc_issued_at' to workmen table.</p>";
        } else {
            echo "<p style='color:red;'>FAILED to add 'noc_issued_at': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:blue;'>'noc_issued_at' already exists in workmen.</p>";
    }
    
    // 3. Create noc_requests table if it doesn't exist, or update it
    $res = $conn->query("SHOW TABLES LIKE 'noc_requests'");
    if ($res->num_rows == 0) {
        $sql = "
            CREATE TABLE noc_requests (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                workman_id INT(11) NOT NULL,
                requesting_contractor_id INT(11) NOT NULL,
                existing_contractor_id INT(11) NULL,
                noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending',
                welfare_user_id INT(11) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        if ($conn->query($sql)) {
            echo "<p style='color:green;'>SUCCESS: Created 'noc_requests' table.</p>";
        } else {
            echo "<p style='color:red;'>FAILED to create 'noc_requests': " . $conn->error . "</p>";
        }
    } else {
        // Check if noc_status exists
        $res = $conn->query("SHOW COLUMNS FROM noc_requests LIKE 'noc_status'");
        if ($res->num_rows == 0) {
            if ($conn->query("ALTER TABLE noc_requests CHANGE status noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending'")) {
                echo "<p style='color:green;'>SUCCESS: Renamed 'status' to 'noc_status' in noc_requests.</p>";
            } else {
                echo "<p style='color:red;'>FAILED to rename 'status': " . $conn->error . "</p>";
            }
        } else {
            if ($conn->query("ALTER TABLE noc_requests MODIFY noc_status ENUM('pending', 'approved_by_contractor', 'rejected_by_contractor', 'forcefully_released', 'approved_by_welfare', 'rejected_by_welfare') DEFAULT 'pending'")) {
                echo "<p style='color:blue;'>SUCCESS: 'noc_status' updated in noc_requests.</p>";
            } else {
                echo "<p style='color:red;'>FAILED to update 'noc_status': " . $conn->error . "</p>";
            }
        }
        
        // Ensure existing_contractor_id exists
        $res = $conn->query("SHOW COLUMNS FROM noc_requests LIKE 'existing_contractor_id'");
        if ($res->num_rows == 0) {
            if ($conn->query("ALTER TABLE noc_requests ADD COLUMN existing_contractor_id INT(11) NULL AFTER requesting_contractor_id")) {
                echo "<p style='color:green;'>SUCCESS: Added 'existing_contractor_id' to noc_requests.</p>";
            } else {
                echo "<p style='color:red;'>FAILED to add 'existing_contractor_id': " . $conn->error . "</p>";
            }
        }
    }

    echo "<h3>Migration Check Completed!</h3>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Migration Fatal Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
