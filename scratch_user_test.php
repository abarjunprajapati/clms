<?php
require_once 'd:/Xampp/htdocs/clms/include/config.php';
$res = mysqli_query($conn, "SELECT * FROM users WHERE contractor_id='1100908'");
print_r(mysqli_fetch_assoc($res));

$res2 = mysqli_query($conn, "SELECT * FROM sap_customer_master WHERE customer_code='1100908'");
print_r(mysqli_fetch_assoc($res2));
?>
