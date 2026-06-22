<?php
// Put this file in the root directory (d:/Xampp/htdocs/clms/test_db_on_server.php)
// Access it via: http://cslweb.teleconsystems.com/test_db_on_server.php

require_once 'include/config.php';

header('Content-Type: text/plain');

echo "=== DATABASE CONFIGURATION ===\n";
echo "Host: " . $Servername . "\n";
echo "User: " . $Username . "\n";
echo "Database Name: " . $Dbname . "\n";

echo "\n=== CONNECTION STATUS ===\n";
if ($conn) {
    echo "Connected successfully to MySQL.\n";
} else {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
    exit;
}

echo "\n=== sap_po_master TOTAL ROWS ===\n";
$q1 = $conn->query("SELECT COUNT(*) as cnt FROM sap_po_master");
if ($q1) {
    $r1 = $q1->fetch_assoc();
    echo "Total rows: " . $r1['cnt'] . "\n";
} else {
    echo "Error querying sap_po_master: " . $conn->error . "\n";
}

echo "\n=== ROWS FOR VENDOR 1100909 IN sap_po_master ===\n";
$q2 = $conn->query("SELECT id, po_number, vendor_code, vendor_name, release_status FROM sap_po_master WHERE TRIM(vendor_code) = '1100909'");
if ($q2) {
    $rows = $q2->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($rows) . " rows:\n";
    print_r($rows);
} else {
    echo "Error querying vendor rows: " . $conn->error . "\n";
}

echo "\n=== ROWS FOR VENDOR 1100909 IN sap_pwo_master ===\n";
$q3 = $conn->query("SELECT id, pwo_number, vendor_code, vessel FROM sap_pwo_master WHERE TRIM(vendor_code) = '1100909'");
if ($q3) {
    $rows3 = $q3->fetch_all(MYSQLI_ASSOC);
    echo "Found " . count($rows3) . " rows:\n";
    print_r($rows3);
} else {
    echo "Error querying vendor pwos: " . $conn->error . "\n";
}

?>
