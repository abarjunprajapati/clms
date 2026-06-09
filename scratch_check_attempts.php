<?php
require_once 'include/config.php';
$row = db_single($conn, "SELECT login_attempts, last_login FROM sap_customer_master WHERE customer_code = '1100908'");
print_r($row);
?>
