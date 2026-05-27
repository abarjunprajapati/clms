<?php
require 'include/config.php';

$queries = [
    "ALTER TABLE workmen CHANGE COLUMN aadhaar_number aadhaar VARCHAR(20) NULL",
    "ALTER TABLE workmen CHANGE COLUMN skill_category skill VARCHAR(100) NULL",
    "ALTER TABLE workmen ADD COLUMN training_status VARCHAR(50) DEFAULT 'pending' AFTER status"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Success: $q\n";
    } else {
        echo "Error or already done for $q: " . $conn->error . "\n";
    }
}

