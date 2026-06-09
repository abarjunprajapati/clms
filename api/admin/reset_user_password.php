<?php
/**
 * Reset User Password API
 * Resets a user's password to a default or specified value.
 */
require_once __DIR__ . '/../../include/auth_middleware.php';
require_once __DIR__ . '/../api_helper.php';

// Enforce Permission
require_permission('users.manage');
require_csrf();

include __DIR__ . '/../../include/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = getApiInput();
    $user_id = intval($input['user_id'] ?? 0);
    $new_password = $input['new_password'] ?? 'Welcome@123';

    if (!$user_id) apiError('User ID is required', 400);

    // Check user exists
    $existing = db_single($conn, "SELECT id, name, role FROM users WHERE id = ?", 'i', [$user_id]);
    if (!$existing) apiError('User not found', 404);

    // Prevent resetting super_admin unless you are super_admin
    if ($existing['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        apiError('Only super admin can reset other super admins', 403);
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Password reset prepare failed: " . $conn->error);
    }
    $stmt->bind_param('si', $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        // Audit log
        $logSql = "INSERT INTO audit_logs (user_id, action, module, remarks, ip_address) VALUES (?, 'reset_password', 'user_management', ?, ?)";
        $remarks = "Reset password for user: {$existing['name']} (ID: {$user_id})";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param('iss', $_SESSION['user_id'], $remarks, $ip);
        $logStmt->execute();

        apiSuccess(['user_id' => $user_id], 'Password reset successfully');
    } else {
        throw new Exception($stmt->error);
    }

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
