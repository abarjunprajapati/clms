<?php
require_once '../../include/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email_otp = $data['email_otp'] ?? '';

if (empty($email_otp)) {
    echo json_encode(['success' => false, 'message' => 'Email OTP is required']);
    exit;
}

if (!($_SESSION['mobile_verified'] ?? false)) {
    echo json_encode(['success' => false, 'message' => 'Please verify mobile OTP first.']);
    exit;
}

if ($email_otp != ($_SESSION['activation_email_otp'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid Email OTP.']);
    exit;
}

$_SESSION['otp_verified'] = true;

echo json_encode([
    'success' => true,
    'message' => 'Email verified. Please create your password.'
]);
