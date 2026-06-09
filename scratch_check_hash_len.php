<?php
require_once 'include/config.php';
$row = db_single($conn, "SELECT login_password, LENGTH(login_password) as len FROM sap_customer_master WHERE customer_code = '1100908'");
echo "Hash: " . $row['login_password'] . "\n";
echo "Length: " . $row['len'] . "\n";
?>
