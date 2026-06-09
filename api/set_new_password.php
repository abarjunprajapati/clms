<?php
/**
 * Set New Password API
 * Called when user logs in for the first time (must_change_password = 1)
 */
session_start();
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // User must be logged in
    if (empty($_SESSION['user_id'])) {
        apiError('Unauthorized. Please login first.', 401);
    }

    $input = getApiInput();
    $new_password = $input['new_password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';

    // Validation
    if (empty($new_password)) apiError('New password is required', 400);
    if (strlen($new_password) < 6) apiError('Password must be at least 6 characters', 400);
    if ($new_password !== $confirm_password) apiError('Passwords do not match', 400);

    // Password strength check
    if (!preg_match('/[A-Z]/', $new_password)) apiError('Password must contain at least one uppercase letter', 400);
    if (!preg_match('/[a-z]/', $new_password)) apiError('Password must contain at least one lowercase letter', 400);
    if (!preg_match('/[0-9]/', $new_password)) apiError('Password must contain at least one number', 400);

    $user_id = $_SESSION['user_id'];

    // Verify user still has must_change_password flag
    $user = db_single($conn, "SELECT id, must_change_password FROM users WHERE id = ?", 'i', [$user_id]);
    if (!$user) apiError('User not found', 404);

    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password and clear the flag
    $result = db_execute($conn, "UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?", 'si', [$hashedPassword, $user_id]);
    if (!$result) apiError('Failed to update password', 500);

    // Clear the session flag
    unset($_SESSION['must_change_password']);

    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    db_execute($conn, 
        "INSERT INTO audit_logs (user_id, action, module, remarks, ip_address, created_at) VALUES (?, 'set_new_password', 'authentication', 'User set new password on first login', ?, NOW())", 
        'is', [$user_id, $ip]
    );

    // Get redirect URL
    require_once __DIR__ . '/../include/session.php';
    $redirectMap = [
        'super_admin'       => 'pages/admin/dashboard.php',
        'welfare_admin'     => 'pages/welfare/admin_dashboard.php',
        'welfare_user'      => 'pages/welfare/dashboard.php',
        'contractor'        => 'pages/contractor/dashboard.php',
        'front_line_user'   => 'pages/frontline/dashboard.php',
        'pass_user'         => 'pages/welfare/pass_issuer_dashboard.php',
        'safety_user'       => 'pages/safety/dashboard.php',
        'execution' => 'pages/execution/dashboard.php',
        'execution_officer' => 'pages/execution/dashboard.php'
    ];
    $redirect = $redirectMap[$_SESSION['role']] ?? 'pages/contractor/dashboard.php';

    apiSuccess([
        'redirect' => $redirect
    ], 'Password updated successfully! Redirecting to dashboard...');

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
