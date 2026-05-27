<?php
/**
 * Forgot Password API
 * Generates OTP and sends it to the registered mobile number
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = getApiInput();
    $id = trim($input['contractor_id'] ?? '');

    if (empty($id)) {
        apiError('Contractor / Customer ID is required', 400);
    }

    $is_customer = false;
    $user = null;
    $table = '';
    $id_col = '';

    // Priority 1: Check users table (Contractors / Internal Users)
    $user = db_single($conn, "SELECT contractor_id as id, mobile, status FROM users WHERE contractor_id = ? OR email = ?", 'ss', [$id, $id]);

    if ($user) {
        $table = 'users';
        $id_col = 'contractor_id';
        if ($user['status'] !== 'active') {
            apiError('Account is inactive', 403);
        }
    } else {
        // Priority 2: Check sap_customer_master
        $user = db_single($conn, "SELECT customer_code as id, mobile, status FROM sap_customer_master WHERE customer_code = ?", 's', [$id]);
        if ($user) {
            $is_customer = true;
            $table = 'sap_customer_master';
            $id_col = 'customer_code';
            if (strtolower($user['status']) !== 'active') {
                apiError('Customer account is inactive', 403);
            }
        }
    }

    if (!$user) {
        apiError('User not found', 404);
    }

    $mobile = trim($user['mobile'] ?? '');
    if (empty($mobile)) {
        apiError('Registered mobile number not available', 500);
    }

    $otp = (string) generateOtp(6);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $sql = "UPDATE $table SET reset_token = ?, reset_expiry = ?, reset_attempts = 0 WHERE $id_col = ?";
    db_execute($conn, $sql, 'sss', [$otp, $expiresAt, $user['id']]);

    $message = "Your CLMS password reset OTP is $otp. It expires in 10 minutes.";
    $smsResult = sendSMS($mobile, $message);
    
    if (empty($smsResult['success'])) {
        if (defined('SMS_DEV_MODE') && SMS_DEV_MODE) {
            apiSuccess([
                'id' => $id,
                'sent_to' => maskMobile($mobile),
                'otp_debug' => $otp,
                'sms_debug' => $smsResult['message'] ?? 'SMS not configured'
            ], 'DEV MODE: OTP not sent by SMS. Use otp_debug to complete testing.');
        }
        apiError('Failed to send OTP to mobile. ' . ($smsResult['message'] ?? 'Please try again later.'), 500);
    }

    apiSuccess([
        'id' => $id,
        'sent_to' => maskMobile($mobile)
    ], 'OTP sent to the registered mobile number.');

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
