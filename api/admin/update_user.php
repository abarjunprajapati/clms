<?php
/**
 * Update User API
 * Updates user details (name, email, role, mobile).
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
    $user_id = intval($input['id'] ?? $input['user_id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = trim($input['role'] ?? '');
    $mobile = trim($input['mobile'] ?? '');

    if (!$user_id) apiError('User ID is required', 400);
    if (empty($name)) apiError('Name is required', 400);
    if (empty($role)) apiError('Role is required', 400);

    // Check user exists
    $existing = db_single($conn, "SELECT * FROM users WHERE id = ?", 'i', [$user_id]);
    if (!$existing) apiError('User not found', 404);

    // Prevent modifying super_admin unless you are super_admin
    if ($existing['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        apiError('Only super admin can modify other super admins', 403);
    }

    // Role safety: Only super admin can grant super admin role
    if ($role === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        apiError('Unauthorized to grant super admin role', 403);
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, mobile = ? WHERE id = ?");
    $stmt->bind_param('ssssi', $name, $email, $role, $mobile, $user_id);
    
    if ($stmt->execute()) {
        // Audit log
        $logSql = "INSERT INTO audit_logs (user_id, action, module, old_value, remarks, ip_address) VALUES (?, 'update_user', 'user_management', ?, ?, ?)";
        $oldValue = json_encode($existing);
        $remarks = "Updated user details for: $name (ID: $user_id)";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param('isss', $_SESSION['user_id'], $oldValue, $remarks, $ip);
        $logStmt->execute();

        apiSuccess(['user_id' => $user_id], 'User updated successfully');
    } else {
        throw new Exception($stmt->error);
    }

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
