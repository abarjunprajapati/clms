<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = mysqli_query($conn, "SHOW TABLES");
echo "Tables in DB:\n";
while ($row = mysqli_fetch_row($res)) {
    echo "- " . $row[0] . "\n";
}

$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM sap_customer_master");
$row = mysqli_fetch_assoc($res);
echo "sap_customer_master count: " . $row['cnt'] . "\n";

$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM sap_vendor_master");
$row = mysqli_fetch_assoc($res);
echo "sap_vendor_master count: " . $row['cnt'] . "\n";
?>
