<?php
/**
 * Safe DB Cleanup: Trimming contractor_id/vendor_code
 * PHASE 1 - AUTH CORE
 */
require_once __DIR__ . '/../include/config.php';

echo "<pre>Starting Safe DB Cleanup...\n";

// 1. BACKUP TABLES
echo "Step 1: Creating Backups...\n";
$conn->query("CREATE TABLE IF NOT EXISTS users_backup AS SELECT * FROM users");
$conn->query("CREATE TABLE IF NOT EXISTS sap_vendor_master_backup AS SELECT * FROM sap_vendor_master");
$conn->query("CREATE TABLE IF NOT EXISTS sap_customer_master_backup AS SELECT * FROM sap_customer_master");
echo "Backups created.\n\n";

// 2. CHECK FOR BROKEN ROWS
function checkBroken($conn, $table, $column) {
    echo "Checking table '$table' for whitespace in '$column'...\n";
    $sql = "SELECT id, $column FROM $table WHERE $column != TRIM($column)";
    $result = $conn->query($sql);
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        echo " - ID: {$row['id']} | Value: '{$row[$column]}'\n";
        $count++;
    }
    echo "Total broken rows in $table: $count\n\n";
    return $count;
}

$broken_users = checkBroken($conn, 'users', 'contractor_id');
$broken_vendors = checkBroken($conn, 'sap_vendor_master', 'vendor_code');
$broken_customers = checkBroken($conn, 'sap_customer_master', 'customer_code');

// 3. UPDATE ONLY BROKEN ROWS
if ($broken_users > 0) {
    echo "Fixing users table...\n";
    $conn->query("UPDATE users SET contractor_id = TRIM(contractor_id) WHERE contractor_id != TRIM(contractor_id)");
}

if ($broken_vendors > 0) {
    echo "Fixing sap_vendor_master table...\n";
    $conn->query("UPDATE sap_vendor_master SET vendor_code = TRIM(vendor_code) WHERE vendor_code != TRIM(vendor_code)");
}

if ($broken_customers > 0) {
    echo "Fixing sap_customer_master table...\n";
    $conn->query("UPDATE sap_customer_master SET customer_code = TRIM(customer_code) WHERE customer_code != TRIM(customer_code)");
}

echo "Cleanup Complete.\n</pre>";
