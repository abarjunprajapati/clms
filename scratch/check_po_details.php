<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "CHECKING SAP_PO_MASTER FOR VENDOR 1100908:\n";
$rows = db_fetch_all($conn, "SELECT id, po_number, vendor_code, release_status FROM sap_po_master WHERE vendor_code = '1100908'");
foreach ($rows as $row) {
    print_r($row);
}
