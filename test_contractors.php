<?php
require 'include/config.php';
$r = $conn->query("SELECT user_id, vendor_code, contractor_name FROM contractors WHERE vendor_code IN ('1100900', '1100909', '1100908')");
print_r($r->fetch_all(MYSQLI_ASSOC));
