<?php
include 'include/config.php';

$queries = [
    "ALTER TABLE contractors MODIFY COLUMN contractor_name VARCHAR(150) NOT NULL",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS pan_no VARCHAR(20) AFTER pan",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS gst_no VARCHAR(20) AFTER gst",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS work_awarding_department VARCHAR(100) AFTER vendor_code",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS nature_of_work VARCHAR(255) AFTER work_awarding_department",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS work_order_no VARCHAR(100) AFTER nature_of_work",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS work_start_date DATE AFTER work_order_no",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS work_end_date DATE AFTER work_start_date",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS epf_registered VARCHAR(10) AFTER pf",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS epf_code VARCHAR(50) AFTER epf_registered",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS esi_registered VARCHAR(10) AFTER esic",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS esi_code VARCHAR(50) AFTER esi_registered",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS wage_declaration VARCHAR(100) AFTER esi_code",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS ecp_number VARCHAR(100) AFTER wage_declaration",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS ecp_valid_from DATE AFTER ecp_number",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS ecp_valid_to DATE AFTER ecp_valid_from",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS workers_ecp INT AFTER ecp_valid_to",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS workers_proposed INT AFTER workers_ecp",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS worker_category VARCHAR(100) AFTER workers_proposed",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS license_issued VARCHAR(100) AFTER license_no",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS issued_date DATE AFTER license_issued",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS expiry_date DATE AFTER issued_date",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS klwf_registration_no VARCHAR(100) AFTER expiry_date",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS remarks TEXT AFTER klwf_registration_no",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS labour_identification_no VARCHAR(100) AFTER remarks",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS contact_person_name VARCHAR(100) AFTER labour_identification_no",
    "ALTER TABLE contractors ADD COLUMN IF NOT EXISTS license_file VARCHAR(255) AFTER contact_person_name",
    "CREATE INDEX idx_user_id ON contractors(user_id)",
    "CREATE INDEX idx_work_order ON contractors(work_order_no)"
];

foreach ($queries as $sql) {
    try {
        if ($conn->query($sql)) {
            echo "Success: $sql\n";
        } else {
            echo "Error: " . $conn->error . " | Query: $sql\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . " | Query: $sql\n";
    }
}
?>

