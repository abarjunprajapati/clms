<?php 
require 'include/config.php';
$r = mysqli_query($conn, 'SELECT * FROM sap_customer_master WHERE customer_code="55066"');
print_r(mysqli_fetch_assoc($r));
