<?php
require_once __DIR__ . '/../include/config.php';
$res = mysqli_query($conn, "DESCRIBE sap_customer_master");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>
