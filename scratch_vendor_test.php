<?php
require_once 'd:/Xampp/htdocs/clms/include/config.php';
$res = mysqli_query($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code='1100908'");
print_r(mysqli_fetch_assoc($res));
?>
