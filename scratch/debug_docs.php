<?php
include 'include/config.php';
echo "Contractor ID 3:\n";
$res = $conn->query("SELECT id, vendor_code, vendor_name FROM contractors WHERE id = 3");
if ($res) print_r($res->fetch_assoc());
?>
