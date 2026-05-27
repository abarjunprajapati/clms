<?php
require_once 'include/config.php';
$username = '1100908';
$sap = db_single($conn, "SELECT id, customer_code, login_password, is_password_created FROM sap_customer_master WHERE customer_code = ?", 's', [$username]);
echo "SAP Master:\n";
print_r($sap);

$user = db_single($conn, "SELECT id, contractor_id, role, password FROM users WHERE contractor_id = ?", 's', [$username]);
echo "\nUsers Table:\n";
print_r($user);
?>
