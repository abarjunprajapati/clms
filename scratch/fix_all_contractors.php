<?php
require_once __DIR__ . '/../include/config.php';

echo "=== FIXING ALL CONTRACTORS ===\n";

$res = mysqli_query($conn, "SELECT * FROM users WHERE role = 'contractor' AND contractor_id REGEXP '^[0-9]{5,7}$'");
while ($user = mysqli_fetch_assoc($res)) {
    $code = $user['contractor_id'];
    $name = $user['name'];
    $pass = $user['password'];
    $email = $user['email'];
    $mobile = $user['mobile'];

    $sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);

    if ($sap) {
        // Update existing SAP record
        mysqli_query($conn, "UPDATE sap_customer_master SET 
                             login_password = '$pass', 
                             is_password_created = 1, 
                             status = 'ACTIVE',
                             ACTIVE_IND = 'A',
                             password_updated_at = NOW() 
                             WHERE customer_code = '$code'");
        echo "FIXED: $code (Updated existing SAP record)\n";
    } else {
        // Insert missing SAP record
        mysqli_query($conn, "INSERT INTO sap_customer_master 
                             (customer_code, customer_name, EMAIL_ADDRESS, Customer_MOB1, login_password, is_password_created, status, ACTIVE_IND, password_updated_at) 
                             VALUES ('$code', '$name', '$email', '$mobile', '$pass', 1, 'ACTIVE', 'A', NOW())");
        echo "FIXED: $code (Inserted missing SAP record)\n";
    }
}

echo "\nAll contractors in 'users' table are now synced and activated in 'sap_customer_master'.\n";
?>
