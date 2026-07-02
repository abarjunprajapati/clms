<?php
require_once 'include/config.php';
echo "=== Tables with 'depart' ===\n";
$r = mysqli_query($conn, "SHOW TABLES LIKE '%depart%'");
while($row = mysqli_fetch_array($r)) echo $row[0]."\n";

echo "\n=== Tables with 'dept' ===\n";
$r = mysqli_query($conn, "SHOW TABLES LIKE '%dept%'");
while($row = mysqli_fetch_array($r)) echo $row[0]."\n";

echo "\n=== purchasing_group in sap_po_master ===\n";
$r = mysqli_query($conn, "SELECT DISTINCT purchasing_group FROM sap_po_master WHERE purchasing_group IS NOT NULL AND purchasing_group != '' LIMIT 20");
if($r) while($row = mysqli_fetch_array($r)) echo $row[0]."\n";
else echo "Table/col not found\n";

echo "\n=== department in work_orders ===\n";
$r = mysqli_query($conn, "SELECT DISTINCT department FROM work_orders WHERE department IS NOT NULL AND department != '' LIMIT 20");
if($r) while($row = mysqli_fetch_array($r)) echo $row[0]."\n";
else echo "Table not found\n";

echo "\n=== All tables ===\n";
$r = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_array($r)) echo $row[0]."\n";
