<?php
require_once '../../include/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$mobile_otp = $data['mobile_otp'] ?? '';

if (empty($mobile_otp)) {
    echo json_encode(['success' => false, 'message' => 'Mobile OTP is required']);
    exit;
}

if ($mobile_otp != ($_SESSION['activation_mobile_otp'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid Mobile OTP.']);
    exit;
}

$_SESSION['mobile_verified'] = true;

echo json_encode([
    'success' => true,
    'message' => 'Mobile verified. Now verify your Email OTP.'
]);
