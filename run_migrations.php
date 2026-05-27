<?php
require_once 'include/config.php';

$sql = [
    "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT,
        action_type VARCHAR(50),
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS workflow_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT,
        action_type VARCHAR(50),
        user_id INT,
        old_status VARCHAR(50),
        new_status VARCHAR(50),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "ALTER TABLE audit_log ADD COLUMN IF NOT EXISTS action_type VARCHAR(50)",
    "ALTER TABLE workflow_logs ADD COLUMN IF NOT EXISTS action_type VARCHAR(50)"
];

foreach ($sql as $query) {
    try {
        if (mysqli_query($conn, $query)) {
            echo "Success: $query\n";
        } else {
            echo "Error: " . mysqli_error($conn) . " on query: $query\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . " on query: $query\n";
    }
}
?>

