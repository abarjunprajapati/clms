<?php
/**
 * API: Send OTP
 * Sends a 6-digit OTP to the contractor's registered mobile.
 */
header('Content-Type: application/json');
require_once '../include/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$contractor_code = $data['contractor_code'] ?? '';

if (empty($contractor_code)) {
    echo json_encode(['success' => false, 'error' => 'Contractor code required']);
    exit;
}

// Fetch mobile from DB
$stmt = $conn->prepare("SELECT mobile FROM contractors WHERE vendor_code = ?");
$stmt->bind_param("s", $contractor_code);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Contractor not found']);
    exit;
}

$otp = rand(100000, 999999);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Store OTP in session or temporary table
session_start();
$_SESSION['login_otp'] = $otp;
$_SESSION['otp_expiry'] = $expiry;
$_SESSION['otp_contractor_code'] = $contractor_code;

// Placeholder for SMS gateway
// sendSMS($user['mobile'], "Your CLMS verification code is: $otp");

echo json_encode(['success' => true, 'message' => 'OTP sent to ' . substr($user['mobile'], 0, 2) . '******' . substr($user['mobile'], -2)]);

