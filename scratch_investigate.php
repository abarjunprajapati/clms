<?php
include 'include/config.php';

echo "=== ALL WORKMEN (Limit 10) ===\n";
$workmen = db_fetch_all($conn, "SELECT id, name, vendor_code, application_status, status FROM workmen ORDER BY id DESC LIMIT 10");
print_r($workmen);

echo "\n=== ALL CONTRACTORS (Limit 10) ===\n";
$contractors = db_fetch_all($conn, "SELECT id, vendor_name, vendor_code, status FROM contractors ORDER BY id DESC LIMIT 10");
print_r($contractors);

echo "\n=== master_religions ===\n";
$religions = db_fetch_all($conn, "SELECT * FROM master_religions LIMIT 5");
print_r($religions);

?>
