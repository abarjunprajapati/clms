<?php
include __DIR__ . '/../include/config.php';

$fields_to_add = [
    'epf_registered' => "VARCHAR(10) DEFAULT NULL",
    'esi_registered' => "VARCHAR(10) DEFAULT NULL",
    'epf_esi_exemption_reason' => "TEXT DEFAULT NULL",
    'ecp_number' => "VARCHAR(100) DEFAULT NULL",
    'ecp_valid_from' => "DATE DEFAULT NULL",
    'ecp_valid_to' => "DATE DEFAULT NULL",
    'workers_ecp' => "INT(11) DEFAULT 0",
    'license_no' => "VARCHAR(100) DEFAULT NULL",
    'license_issued' => "VARCHAR(100) DEFAULT NULL",
    'issued_date' => "DATE DEFAULT NULL",
    'expiry_date' => "DATE DEFAULT NULL",
    'klwf_registration_no' => "VARCHAR(100) DEFAULT NULL",
    'contact_person' => "VARCHAR(100) DEFAULT NULL",
    'remarks' => "TEXT DEFAULT NULL"
];

echo "Adding missing columns to annexure2a table...\n";

foreach ($fields_to_add as $field => $definition) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM annexure2a LIKE '$field'");
    if ($check && $check->num_rows > 0) {
        echo "Column '$field' already exists.\n";
    } else {
        $sql = "ALTER TABLE annexure2a ADD COLUMN `$field` $definition";
        if ($conn->query($sql)) {
            echo "Successfully added column '$field'.\n";
        } else {
            echo "Error adding column '$field': " . $conn->error . "\n";
        }
    }
}

echo "Done.\n";
