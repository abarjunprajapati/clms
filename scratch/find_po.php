<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SELECT * FROM sap_po_master WHERE vendor_code LIKE '%1100908%' OR po_number LIKE '%1100908%'");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
