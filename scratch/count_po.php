<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SELECT COUNT(*) c FROM sap_po_master");
$row = mysqli_fetch_assoc($res);
echo "Total POs: " . $row['c'] . "\n";

$res = mysqli_query($conn, "SELECT * FROM sap_po_master WHERE po_number = '1100908' OR vendor_code = '1100908'");
if (mysqli_num_rows($res) == 0) {
    echo "No matching POs found.\n";
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
}
