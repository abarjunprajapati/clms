<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SELECT * FROM sap_po_master LIMIT 5");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
