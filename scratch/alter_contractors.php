<?php
require 'include/config.php';

// First, check if columns exist, if not add/change them
$queries = [
    "ALTER TABLE contractors CHANGE COLUMN contractor_code vendor_code VARCHAR(100) NULL",
    "ALTER TABLE contractors CHANGE COLUMN company_name contractor_name VARCHAR(255) NULL",
    "ALTER TABLE contractors ADD COLUMN contractor_type ENUM('Company', 'Individual') NULL AFTER contractor_name",
    "ALTER TABLE contractors CHANGE COLUMN pan_number pan VARCHAR(20) NULL",
    "ALTER TABLE contractors CHANGE COLUMN gst_number gst VARCHAR(20) NULL",
    "ALTER TABLE contractors ADD COLUMN esic VARCHAR(50) NULL AFTER gst",
    "ALTER TABLE contractors ADD COLUMN pf VARCHAR(50) NULL AFTER esic",
    "ALTER TABLE contractors CHANGE COLUMN license_number license_no VARCHAR(100) NULL",
    "ALTER TABLE contractors ADD COLUMN valid_from DATE NULL AFTER license_no",
    "ALTER TABLE contractors ADD COLUMN valid_to DATE NULL AFTER valid_from"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Success: $q\n";
    } else {
        echo "Error or already done for $q: " . $conn->error . "\n";
    }
}

