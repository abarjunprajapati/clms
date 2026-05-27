<?php
/**
 * DB Cleanup - Remove Active Customers
 */
include __DIR__ . '/include/config.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line for safety.");
}

echo "Cleaning up active customers from sap_customer_master...\n";

$sql = "DELETE FROM sap_customer_master WHERE ACTIVE_IND = 'A'";
if (mysqli_query($conn, $sql)) {
    echo "Successfully removed " . mysqli_affected_rows($conn) . " active customers.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Also check for orphaned user accounts with role 'customer' just in case
$sql2 = "DELETE FROM users WHERE role = 'customer'";
mysqli_query($conn, $sql2);
echo "Cleaned up orphaned customer user accounts: " . mysqli_affected_rows($conn) . "\n";

echo "Done.\n";
