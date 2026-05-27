<?php
include 'd:/Xampp/htdocs/clms/include/config.php';
$u = ['1100908', 'SRI RAMBALAJI GASES PVT LTD', '9876543210', '9876543211', 'A', 'rambalaji@example.com', 'Plot No. 123, Industrial Area', '682001'];

mysqli_query($conn, "INSERT INTO sap_customer_master (customer_code, customer_name, Customer_MOB1, customer_MOB2, ACTIVE_IND, EMAIL_ADDRESS, Address, PIN, status) 
    VALUES ('$u[0]', '" . mysqli_real_escape_string($conn, $u[1]) . "', '$u[2]', '$u[3]', '$u[4]', '$u[5]', '" . mysqli_real_escape_string($conn, $u[6]) . "', '$u[7]', 'ACTIVE')
    ON DUPLICATE KEY UPDATE customer_name=VALUES(customer_name), Customer_MOB1=VALUES(Customer_MOB1), Address=VALUES(Address)");

echo "SUCCESS: SRI RAMBALAJI added to sap_customer_master\n";
?>
