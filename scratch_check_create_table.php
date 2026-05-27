<?php
require_once 'include/config.php';
$res = $conn->query("SHOW CREATE TABLE sap_customer_master");
$row = $res->fetch_assoc();
print_r($row);
?>
