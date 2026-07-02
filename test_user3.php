<?php
require 'include/config.php';
$r = $conn->query("SELECT * FROM sap_customer_master WHERE id = 3");
if ($r) print_r($r->fetch_all(MYSQLI_ASSOC));
else echo $conn->error;
