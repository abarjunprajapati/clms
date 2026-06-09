<?php
require_once 'd:/Xampp/htdocs/clms/include/config.php';
$res = mysqli_query($conn, "SELECT vendor_code FROM sap_vendor_master WHERE vendor_code IN ('1100914', '1100909')");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
