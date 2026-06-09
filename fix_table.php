<?php
require_once 'include/config.php';

$queries = [
    "ALTER TABLE productivity_reports ADD COLUMN IF NOT EXISTS report_date DATE AFTER contractor_id",
    "ALTER TABLE productivity_reports ADD COLUMN IF NOT EXISTS dept_id INT AFTER report_date",
    "ALTER TABLE productivity_reports ADD COLUMN IF NOT EXISTS work_description TEXT AFTER dept_id",
    "ALTER TABLE productivity_reports ADD COLUMN IF NOT EXISTS output_unit VARCHAR(50) AFTER work_description",
    "ALTER TABLE productivity_reports ADD COLUMN IF NOT EXISTS output_qty DECIMAL(10,2) DEFAULT 0 AFTER output_unit",
    "ALTER TABLE productivity_reports ADD COLUMN IF NOT EXISTS manpower_deployed INT DEFAULT 0 AFTER output_qty"
];

foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
