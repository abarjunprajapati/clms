<?php
require 'include/config.php';

$queries = [
    "ALTER TABLE compliance ADD COLUMN type VARCHAR(50) AFTER contractor_id",
    "ALTER TABLE compliance ADD COLUMN month_year VARCHAR(20) AFTER type",
    "ALTER TABLE compliance ADD COLUMN challan_number VARCHAR(100) AFTER month_year",
    "ALTER TABLE compliance ADD COLUMN amount DECIMAL(10,2) AFTER challan_number",
    "ALTER TABLE compliance ADD COLUMN file_path VARCHAR(255) AFTER amount",
    "ALTER TABLE compliance ADD COLUMN challan_worker_count INT DEFAULT 0 AFTER file_path",
    "ALTER TABLE compliance ADD COLUMN attendance_count INT DEFAULT 0 AFTER challan_worker_count",
    "ALTER TABLE compliance ADD COLUMN verification_remarks TEXT AFTER status",
    "ALTER TABLE compliance ADD COLUMN verified_by INT AFTER verification_remarks",
    "ALTER TABLE compliance ADD COLUMN verified_at TIMESTAMP NULL AFTER verified_by"
];

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        echo "Error: " . $conn->error . "\n";
    } else {
        echo "Success: $q\n";
    }
}
?>
