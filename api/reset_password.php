<?php
/**
 * Reset Password API
 * Verifies OTP and updates password
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = getApiInput();

    $id = trim($input['contractor_id'] ?? '');
    $otp = trim($input['otp'] ?? '');
    $newPassword = $input['new_password'] ?? '';

    if (empty($id)) {
        apiError('Contractor / Customer ID is required', 400);
    }
    if (empty($otp)) {
        apiError('OTP is required', 400);
    }
    if (empty($newPassword) || strlen($newPassword) < 6) {
        apiError('New password must be at least 6 characters', 400);
    }

    $is_customer = false;
    $user = null;
    $table = '';
    $id_col = '';
    $pass_col = '';

    // Priority 1: Check users table
    $user = db_single($conn, "SELECT contractor_id as id, reset_token, reset_expiry, reset_attempts, status FROM users WHERE contractor_id = ? LIMIT 1", 's', [$id]);

    if ($user) {
        $table = 'users';
        $id_col = 'contractor_id';
        $pass_col = 'password';
    } else {
        // Priority 2: Check sap_customer_master
        $user = db_single($conn, "SELECT customer_code as id, reset_token, reset_expiry, reset_attempts, status FROM sap_customer_master WHERE customer_code = ?", 's', [$id]);
        if ($user) {
            $is_customer = true;
            $table = 'sap_customer_master';
            $id_col = 'customer_code';
            $pass_col = 'login_password';
        }
    }

    if (!$user) {
        apiError('User not found', 404);
    }

    if (strtolower($user['status']) !== 'active') {
        apiError('Account is inactive', 403);
    }

    if (empty($user['reset_token']) || empty($user['reset_expiry'])) {
        apiError('No active OTP found. Please request a new OTP.', 400);
    }

    if (strtotime($user['reset_expiry']) < time()) {
        db_execute($conn, "UPDATE $table SET reset_token = NULL, reset_expiry = NULL, reset_attempts = 0 WHERE $id_col = ?", 's', [$id]);
        apiError('OTP expired. Please request a new OTP.', 400);
    }

    if (!hash_equals($user['reset_token'], $otp)) {
        $attempts = (int) $user['reset_attempts'] + 1;
        if ($attempts >= 5) {
            db_execute($conn, "UPDATE $table SET reset_token = NULL, reset_expiry = NULL, reset_attempts = 0 WHERE $id_col = ?", 's', [$id]);
            apiError('OTP attempts exceeded. Request a new OTP.', 429);
        }

        db_execute($conn, "UPDATE $table SET reset_attempts = ? WHERE $id_col = ?", 'is', [$attempts, $id]);
        apiError('Invalid OTP. Please try again.', 400);
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE $table SET $pass_col = ?, reset_token = NULL, reset_expiry = NULL, reset_attempts = 0 WHERE $id_col = ?";
    
    // For customers, also mark password as created
    if ($is_customer) {
        $sql = "UPDATE $table SET $pass_col = ?, reset_token = NULL, reset_expiry = NULL, reset_attempts = 0, is_password_created = 1 WHERE $id_col = ?";
    }

    db_execute($conn, $sql, 'ss', [$hashedPassword, $id]);

    apiSuccess([], 'Password reset successfully');

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
