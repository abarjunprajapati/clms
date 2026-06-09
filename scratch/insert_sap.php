<?php
include '../include/config.php';

$vendor_code = '1100909';
$name = 'TEST CONTRACTOR 1100909';

// Check if exists
$res = $conn->query("SELECT * FROM sap_customer_master WHERE customer_code = '$vendor_code'");
if ($res->num_rows == 0) {
    $conn->query("INSERT INTO sap_customer_master (customer_code, customer_name, ACTIVE_IND, status, email, EMAIL_ADDRESS, Customer_MOB1) 
                  VALUES ('$vendor_code', '$name', 'A', 'ACTIVE', 'test@example.com', 'test@example.com', '9876543210')");
    echo "Inserted 1100909";
} else {
    echo "Already exists";
}
?>
