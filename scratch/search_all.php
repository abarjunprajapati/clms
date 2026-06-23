<?php
require_once __DIR__ . '/../include/config.php';

echo "=== Searching sap_vendor_master ===\n";
$res = $conn->query("SELECT * FROM sap_vendor_master WHERE vendor_name LIKE '%Mathew%' OR vendor_name LIKE '%Joseph%' OR vendor_code = '2108290'");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "=== Searching sap_pwo_master ===\n";
$res = $conn->query("SELECT * FROM sap_pwo_master WHERE vessel LIKE '%Mathew%' OR vessel LIKE '%Joseph%'");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
