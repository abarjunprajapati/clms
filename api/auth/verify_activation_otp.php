<?php
require_once '../../include/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$otp = $data['otp'] ?? '';
$password = $data['password'] ?? '';
$code = $_SESSION['activation_vendor'] ?? '';
$type = $_SESSION['activation_type'] ?? 'vendor';

if (!$code || $otp != ($_SESSION['activation_otp'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// 1. Get SAP data based on type
if ($type === 'customer') {
    $sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);
    $name = $sap['customer_name'] ?? 'Project/Customer';
    $email = $sap['email'] ?? ($code . '@sap-customer.com');
    $mobile = $sap['mobile'] ?? '';
    $address = $sap['address'] ?? '';
} else {
    $sap = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ?", 's', [$code]);
    $name = $sap['vendor_name'] ?? $sap['contractor_name'] ?? 'Contractor';
    $email = $sap['email_address'] ?? ($code . '@sap-vendor.com');
    $mobile = $sap['vendor_mob1'] ?? '';
    $address = $sap['address'] ?? '';
}

// 2. Create User
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$success = db_execute($conn, 
    "INSERT INTO users (contractor_id, password, name, role, email, mobile, status) VALUES (?, ?, ?, 'contractor', ?, ?, 'active')",
    'sssss', [$code, $hashed_password, $name, $email, $mobile]
);

if ($success) {
    $new_user_id = mysqli_insert_id($conn);

    // 3. Auto-insert into contractors (Annexure 2A) table
    if ($type === 'vendor') {
        db_execute($conn, 
            "INSERT INTO contractors (
                user_id, contractor_name, vendor_code, vendor_name,
                mobile, email, address, status, sap_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', 'A')",
            'issssss', [
                $new_user_id, $name, $code, $sap['vendor_name'] ?? '',
                $mobile, $email, $address
            ]
        );
    } else {
        // Customer based contractor registration
        db_execute($conn, 
            "INSERT INTO contractors (
                user_id, contractor_name, vendor_code, mobile, email, address, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'draft')",
            'isssss', [$new_user_id, $name, $code, $mobile, $email, $address]
        );
    }

    unset($_SESSION['activation_otp']);
    unset($_SESSION['activation_vendor']);
    unset($_SESSION['activation_type']);
    
    db_execute($conn, "INSERT INTO sap_logs (activity, status) VALUES (?, ?)", 'ss', ["$type $code activated and auto-registered in CLMS", "SUCCESS"]);
    
    echo json_encode(['success' => true, 'message' => 'Account activated successfully! Redirecting to login...']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create account. Please contact admin.']);
}
?>
