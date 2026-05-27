<?php
require_once 'include/config.php';

$sql = "CREATE TABLE IF NOT EXISTS workflow_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT,
  current_status VARCHAR(50) DEFAULT 'draft',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table workflow_status created successfully or already exists.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

