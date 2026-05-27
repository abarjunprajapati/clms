<?php
require_once '../../include/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$vendor_code = $data['vendor_code'] ?? '';

if (empty($vendor_code)) {
    echo json_encode(['success' => false, 'message' => 'Vendor Code is required']);
    exit;
}

// Determine type
$is_customer = false;

// 1. Validate against SAP Vendor Master FIRST (Prioritize Contractors)
$master = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ?", 's', [$vendor_code]);

if ($master) {
    $role = 'contractor';
    $is_customer = false;
} else {
    // 2. Validate against SAP Customer Master
    $master = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$vendor_code]);
    if ($master) {
        $role = 'customer';
        $is_customer = true;
    }
}

if (!$master) {
    echo json_encode(['success' => false, 'message' => 'Invalid Code. Please contact Admin.']);
    exit;
}

// 2. Check if Active in SAP
$active_ind = $master['ACTIVE_IND'] ?? $master['active_ind'] ?? '';
if ($active_ind !== 'A') {
    echo json_encode(['success' => false, 'message' => 'Your SAP profile is INACTIVE. Contact SAP Team for activation.']);
    exit;
}

// 3. Check if already activated
if ($is_customer) {
    if ($master['is_password_created']) {
        echo json_encode(['success' => false, 'message' => 'Customer account already activated. Please go to Login page.']);
        exit;
    }
} else {
    $existing = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$vendor_code]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Contractor account already activated. Please go to Login page.']);
        exit;
    }
}

$_SESSION['activation_role'] = $role;

// Generate Dual OTPs (Simulated)
$mobile_otp = rand(100000, 999999);
$email_otp = rand(100000, 999999);

$_SESSION['activation_mobile_otp'] = $mobile_otp;
$_SESSION['activation_email_otp'] = $email_otp;
$_SESSION['activation_vendor_code'] = $vendor_code;

$name = $master['customer_name'] ?? $master['vendor_name'] ?? 'Unknown';
$mobile_raw = $master['Customer_MOB1'] ?? $master['vendor_mob1'] ?? '';
$email_raw = $master['EMAIL_ADDRESS'] ?? $master['email_address'] ?? '';

$res_data = [
    'vendor_name' => $name,
    'mobile1' => $mobile_raw ? substr($mobile_raw, 0, 2) . '******' . substr($mobile_raw, -2) : 'N/A',
    'email' => $email_raw ? substr($email_raw, 0, 3) . '****@' . explode('@', $email_raw)[1] : 'N/A'
];

echo json_encode([
    'success' => true,
    'data' => $res_data,
    'mobile_otp_demo' => $mobile_otp,
    'email_otp_demo' => $email_otp,
    'message' => 'SAP Details Validated. OTPs sent to registered Mobile & Email.'
]);
?>
