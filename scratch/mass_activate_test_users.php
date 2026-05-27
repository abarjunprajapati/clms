<?php
require_once __DIR__ . '/../include/config.php';

$codes = ['55065', '55090', '55104'];
$password = 'password123';
$hashed = password_hash($password, PASSWORD_BCRYPT);

foreach ($codes as $code) {
    // 1. Update SAP Master
    $sql1 = "UPDATE sap_customer_master SET 
             login_password = '$hashed', 
             is_password_created = 1, 
             status = 'ACTIVE',
             ACTIVE_IND = 'A',
             password_updated_at = NOW() 
             WHERE customer_code = '$code'";
    
    if (mysqli_query($conn, $sql1)) {
        echo "SUCCESS: Activated $code in SAP Master\n";
        
        // 2. Fetch details for syncing
        $sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);
        $name = $sap['customer_name'];
        $email = $sap['EMAIL_ADDRESS'] ?: $sap['email'] ?: ($code . '@clms.com');
        $mobile = $sap['Customer_MOB1'] ?: $sap['mobile'] ?: '';
        
        // 3. Sync with Users table
        $user = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$code]);
        if ($user) {
            mysqli_query($conn, "UPDATE users SET password = '$hashed', name = '$name', status = 'active' WHERE id = " . $user['id']);
            $user_id = $user['id'];
        } else {
            mysqli_query($conn, "INSERT INTO users (contractor_id, password, name, role, email, mobile, status) 
                               VALUES ('$code', '$hashed', '$name', 'contractor', '$email', '$mobile', 'active')");
            $user_id = mysqli_insert_id($conn);
        }
        
        // 4. Sync with Contractors table
        $cont = db_single($conn, "SELECT id FROM contractors WHERE vendor_code = ?", 's', [$code]);
        if ($cont) {
            mysqli_query($conn, "UPDATE contractors SET status = 'new' WHERE id = " . $cont['id']);
        } else {
            mysqli_query($conn, "INSERT INTO contractors (user_id, vendor_code, vendor_name, status) 
                               VALUES ($user_id, '$code', '$name', 'new')");
        }
        
    } else {
        echo "ERROR: Could not activate $code: " . mysqli_error($conn) . "\n";
    }
}

echo "\nAll test accounts activated with password: $password\n";
?>
