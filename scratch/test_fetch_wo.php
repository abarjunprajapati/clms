<?php
include 'include/config.php';

echo "Testing db_fetch_all for sap_customer_master:\n";
$customers = db_fetch_all($conn, "SELECT customer_code, customer_name FROM sap_customer_master ORDER BY customer_name");
echo "Count: " . count($customers) . "\n";
foreach($customers as $c) {
    echo "- Code: {$c['customer_code']} | Name: {$c['customer_name']}\n";
}

echo "\nTesting db_fetch_all for sap_vendor_master:\n";
$vendors = db_fetch_all($conn, "SELECT vendor_code, vendor_name FROM sap_vendor_master ORDER BY vendor_name");
echo "Count: " . count($vendors) . "\n";
foreach($vendors as $v) {
    echo "- Code: {$v['vendor_code']} | Name: {$v['vendor_name']}\n";
}
?>
