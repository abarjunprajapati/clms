<?php
include 'include/config.php';

$sql = "CREATE TABLE IF NOT EXISTS annexure_3a (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    contractor_name VARCHAR(255),
    nature_of_work VARCHAR(255),
    category_of_work VARCHAR(255),

    establishment_code VARCHAR(100),
    pf_establishment_code VARCHAR(100),
    esi_establishment_code VARCHAR(100),

    address_line1 TEXT,
    address_line2 TEXT,
    state VARCHAR(100),
    district VARCHAR(100),
    pincode VARCHAR(10),

    contact_person_name VARCHAR(255),
    mobile_number VARCHAR(15),
    email VARCHAR(255),

    license_number VARCHAR(100),
    license_issue_date DATE,
    license_valid_upto DATE,

    max_workmen_allowed INT,
    supervisor_count INT,

    remarks TEXT,
    
    status VARCHAR(20) DEFAULT 'pending',
    rejection_reason TEXT,

    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Table annexure_3a created/exists successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

