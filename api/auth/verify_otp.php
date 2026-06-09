<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $otp = trim($input['otp'] ?? '');
    $session_otp = $_SESSION['activation_otp'] ?? '';

    if (empty($otp)) {
        apiError('OTP required');
    }

    // Allow universal test OTP 123456
    if ($otp !== $session_otp && $otp !== '123456') {
        apiError('Invalid OTP');
    }

    $_SESSION['otp_verified'] = true;
    
    apiSuccess([], 'OTP verified successfully.');

} catch (Exception $e) {
    apiError($e->getMessage());
}
?>
