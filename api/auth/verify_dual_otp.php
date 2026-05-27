<?php
require_once '../../include/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$mobile_otp = $data['mobile_otp'] ?? '';
$email_otp = $data['email_otp'] ?? '';

if (empty($mobile_otp) || empty($email_otp)) {
    echo json_encode(['success' => false, 'message' => 'Both OTPs are required']);
    exit;
}

if ($mobile_otp != ($_SESSION['activation_mobile_otp'] ?? '') || $email_otp != ($_SESSION['activation_email_otp'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP(s). Please try again.']);
    exit;
}

$_SESSION['otp_verified'] = true;

echo json_encode([
    'success' => true,
    'message' => 'OTPs verified successfully. Please create your password.'
]);
?>
