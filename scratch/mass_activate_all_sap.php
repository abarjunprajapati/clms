<?php
require_once __DIR__ . '/../include/config.php';

echo "=== MASS ACTIVATING ALL SAP CUSTOMERS ===\n";

$password = 'password123';
$hashed = password_hash($password, PASSWORD_BCRYPT);

$res = mysqli_query($conn, "SELECT * FROM sap_customer_master WHERE is_password_created = 0");
while ($sap = mysqli_fetch_assoc($res)) {
    $code = $sap['customer_code'];
    $name = $sap['customer_name'];
    $email = $sap['EMAIL_ADDRESS'] ?: $sap['email'] ?: ($code . '@clms.com');
    $mobile = $sap['Customer_MOB1'] ?: $sap['mobile'] ?: '';

    // 1. Update SAP Master
    mysqli_query($conn, "UPDATE sap_customer_master SET 
                         login_password = '$hashed', 
                         is_password_created = 1, 
                         status = 'ACTIVE',
                         ACTIVE_IND = 'A',
                         password_updated_at = NOW() 
                         WHERE customer_code = '$code'");
    
    // 2. Sync with Users
    $user = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$code]);
    if ($user) {
        mysqli_query($conn, "UPDATE users SET password = '$hashed', name = '$name', status = 'active' WHERE id = " . $user['id']);
        $user_id = $user['id'];
    } else {
        mysqli_query($conn, "INSERT INTO users (contractor_id, password, name, role, email, mobile, status) 
                             VALUES ('$code', '$hashed', '$name', 'contractor', '$email', '$mobile', 'active')");
        $user_id = mysqli_insert_id($conn);
    }
    
    // 3. Sync with Contractors
    $cont = db_single($conn, "SELECT id FROM contractors WHERE vendor_code = ?", 's', [$code]);
    if ($cont) {
        mysqli_query($conn, "UPDATE contractors SET status = 'new' WHERE id = " . $cont['id']);
    } else {
        mysqli_query($conn, "INSERT INTO contractors (user_id, vendor_code, vendor_name, status) 
                             VALUES ($user_id, '$code', '$name', 'new')");
    }
    
    echo "ACTIVATED: $code ($name)\n";
}

echo "\nAll SAP customers are now activated and synced with a default password: $password\n";
?>
