<?php
/**
 * Toggle User Status API
 * Activates or deactivates a user account.
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
    $new_status = $input['status'] ?? ''; // 'ACTIVE' or 'INACTIVE'

    if (!$user_id) apiError('User ID is required', 400);
    if (!in_array($new_status, ['ACTIVE', 'INACTIVE'])) apiError('Invalid status', 400);

    // Check user exists
    $existing = db_single($conn, "SELECT id, name, role, status FROM users WHERE id = ?", 'i', [$user_id]);
    if (!$existing) apiError('User not found', 404);

    // Prevent toggling self
    if ($user_id == $_SESSION['user_id']) {
        apiError('Cannot deactivate your own account', 400);
    }

    // Prevent toggling super_admin unless you are super_admin
    if ($existing['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        apiError('Only super admin can modify other super admins', 403);
    }

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $user_id);
    
    if ($stmt->execute()) {
        // Audit log
        $logSql = "INSERT INTO audit_logs (user_id, action, module, old_value, remarks, ip_address) VALUES (?, 'toggle_status', 'user_management', ?, ?, ?)";
        $oldValue = $existing['status'];
        $remarks = "Changed status of user {$existing['name']} to $new_status";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param('isss', $_SESSION['user_id'], $oldValue, $remarks, $ip);
        $logStmt->execute();

        apiSuccess(['user_id' => $user_id, 'status' => $new_status], 'User status updated successfully');
    } else {
        throw new Exception($stmt->error);
    }

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
