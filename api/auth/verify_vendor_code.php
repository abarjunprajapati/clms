<?php
require_once '../../include/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = trim($data['vendor_code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vendor Code is required']);
    exit;
}

// 1. Check SAP Vendor Master
$sap = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ?", 's', [$code]);

if (!$sap) {
    echo json_encode(['success' => false, 'message' => 'Vendor Code not found in SAP records.']);
    exit;
}

// 2. Check if already activated
$existing = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$code]);
if ($existing) {
    echo json_encode(['success' => false, 'message' => 'This account is already activated. Please login.']);
    exit;
}

// 3. Generate Dual OTPs
$mobile_otp = rand(100000, 999999);
$email_otp = rand(100000, 999999);

$_SESSION['activation_mobile_otp'] = $mobile_otp;
$_SESSION['activation_email_otp'] = $email_otp;
$_SESSION['activation_vendor_code'] = $code;

// SAP Data to return
$vendor_data = [
    'vendor_name' => $sap['vendor_name'] ?? $sap['contractor_name'] ?? 'N/A',
    'mobile1' => $sap['vendor_mob1'] ?? 'N/A',
    'mobile2' => $sap['vendor_mob2'] ?? 'N/A',
    'email' => $sap['email_address'] ?? 'N/A',
    'address' => $sap['address'] ?? 'N/A',
    'status' => $sap['active_ind'] === 'A' ? 'Active' : 'Inactive',
    'msme_type' => $sap['msme_type'] ?? 'N/A'
];

// In real app, send actual SMS/Email here
db_execute($conn, "INSERT INTO sap_logs (activity, status) VALUES (?, ?)", 'ss', [
    "Dual OTPs generated for $code: Mobile($mobile_otp), Email($email_otp)", "SUCCESS"
]);

echo json_encode([
    'success' => true,
    'data' => $vendor_data,
    'mobile_otp_demo' => $mobile_otp, // FOR DEMO
    'email_otp_demo' => $email_otp,   // FOR DEMO
    'message' => 'Vendor details fetched and OTPs sent to registered Mobile & Email.'
]);
?>
