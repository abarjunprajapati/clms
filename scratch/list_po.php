<?php
require_once __DIR__ . '/../include/config.php';

echo "=== ALL POs ===\n";
$res = $conn->query("SELECT id, po_number, vendor_code, vendor_name, release_status FROM sap_po_master");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "=== ALL PWOs ===\n";
$res = $conn->query("SELECT id, pwo_number, vendor_code, vessel FROM sap_pwo_master");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
