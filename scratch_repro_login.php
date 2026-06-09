<?php
require_once 'include/config.php';
$username = '1100908';
$password = '123456';

$sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$username]);
if (!$sap) {
    echo "FAIL: User not found\n";
} else if (!password_verify($password, $sap['login_password'])) {
    echo "FAIL: Password verify failed\n";
    echo "Input Password: $password\n";
    echo "DB Hash: " . $sap['login_password'] . "\n";
} else {
    echo "SUCCESS: Login would work\n";
}
?>
