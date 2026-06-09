<?php
$c = new mysqli('localhost', 'root', '', 'new_clms');
if($c->connect_error) die($c->connect_error);

echo "USERS TABLE SCHEMA:\n";
$res = $c->query('DESCRIBE users');
while($row = $res->fetch_assoc()) {
    echo "{$row['Field']} ({$row['Type']})\n";
}

echo "\nDISTINCT ROLES IN USERS TABLE:\n";
$res = $c->query('SELECT DISTINCT role FROM users');
while($row = $res->fetch_assoc()) {
    echo "- {$row['role']}\n";
}

echo "\nSAP VENDOR MASTER SCHEMA (First 10 columns):\n";
$res = $c->query('DESCRIBE sap_vendor_master');
$i = 0;
while($row = $res->fetch_assoc()) {
    if ($i++ > 10) break;
    echo "{$row['Field']} ({$row['Type']})\n";
}

echo "\nCONTRACTORS TABLE SCHEMA:\n";
$res = $c->query('DESCRIBE contractors');
while($row = $res->fetch_assoc()) {
    echo "{$row['Field']} ({$row['Type']})\n";
}
?>
