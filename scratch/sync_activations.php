<?php
require_once __DIR__ . '/../include/config.php';

// Activate all sap_customer_master entries that have a corresponding user
$res = mysqli_query($conn, "SELECT s.customer_code, u.password 
                            FROM sap_customer_master s 
                            JOIN users u ON s.customer_code = u.contractor_id 
                            WHERE s.is_password_created = 0");

while ($row = mysqli_fetch_assoc($res)) {
    $code = $row['customer_code'];
    $pass = $row['password'];
    mysqli_query($conn, "UPDATE sap_customer_master SET 
                         login_password = '$pass', 
                         is_password_created = 1, 
                         status = 'ACTIVE',
                         password_updated_at = NOW() 
                         WHERE customer_code = '$code'");
    echo "Activated $code from users table sync.\n";
}
?>
