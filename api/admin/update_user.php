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
    $employee_code = strtoupper(trim($input['employee_code'] ?? ''));
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = trim($input['role'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $status = trim($input['status'] ?? '');

    if (!$user_id) apiError('User ID is required', 400);
    if (empty($name)) apiError('Name is required', 400);
    if (empty($role)) apiError('Role is required', 400);
    if ($role === 'execution_officer' && $employee_code === '') {
        apiError('Employee E-Code is required for Execution Officer', 400);
    }
    if ($status !== '' && !in_array($status, ['active', 'inactive'], true)) {
        apiError('Invalid status', 400);
    }

    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'employee_code'");
    if (!$colRes || mysqli_num_rows($colRes) === 0) {
        @mysqli_query($conn, "ALTER TABLE users ADD COLUMN employee_code VARCHAR(50) NULL");
    }

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

    if ($employee_code !== '') {
        $duplicate = db_single($conn, "SELECT id FROM users WHERE employee_code = ? AND id <> ? LIMIT 1", 'si', [$employee_code, $user_id]);
        if ($duplicate) {
            apiError('Employee E-Code already exists', 409);
        }
    }

    $conn->begin_transaction();

    $sql = $status === ''
        ? "UPDATE users SET employee_code = ?, name = ?, email = ?, role = ?, mobile = ? WHERE id = ?"
        : "UPDATE users SET employee_code = ?, name = ?, email = ?, role = ?, mobile = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($status === '') {
        $stmt->bind_param('sssssi', $employee_code, $name, $email, $role, $mobile, $user_id);
    } else {
        $stmt->bind_param('ssssssi', $employee_code, $name, $email, $role, $mobile, $status, $user_id);
    }
    
    if ($stmt->execute()) {
        if ($role === 'execution_officer') {
            $eoStatus = $status !== '' ? $status : ($existing['status'] ?? 'active');
            mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_officers (
                id INT NOT NULL AUTO_INCREMENT,
                employee_code VARCHAR(50) NULL,
                name VARCHAR(200) NULL,
                email VARCHAR(150) NULL,
                mobile VARCHAR(30) NULL,
                status VARCHAR(30) DEFAULT 'active',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_execution_employee_code (employee_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $oldEmployeeCode = strtoupper(trim((string)($existing['employee_code'] ?? '')));
            if ($oldEmployeeCode === '') {
                $oldEmployeeCode = strtoupper(trim((string)($existing['contractor_id'] ?? '')));
            }
            $eo = db_single(
                $conn,
                "SELECT id FROM execution_officers WHERE employee_code IN (?, ?) ORDER BY FIELD(employee_code, ?, ?) LIMIT 1",
                'ssss',
                [$employee_code, $oldEmployeeCode, $employee_code, $oldEmployeeCode]
            );
            if ($eo) {
                db_execute(
                    $conn,
                    "UPDATE execution_officers SET employee_code = ?, name = ?, email = ?, mobile = ?, status = ? WHERE id = ?",
                    'sssssi',
                    [$employee_code, $name, $email, $mobile, $eoStatus, (int)$eo['id']]
                );
            } else {
                db_execute(
                    $conn,
                    "INSERT INTO execution_officers (employee_code, name, email, mobile, status) VALUES (?, ?, ?, ?, ?)",
                    'sssss',
                    [$employee_code, $name, $email, $mobile, $eoStatus]
                );
            }
        }

        // Audit log
        $logSql = "INSERT INTO audit_logs (user_id, action, module, old_value, remarks, ip_address) VALUES (?, 'update_user', 'user_management', ?, ?, ?)";
        $oldValue = json_encode($existing);
        $remarks = "Updated user details for: $name (ID: $user_id)";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param('isss', $_SESSION['user_id'], $oldValue, $remarks, $ip);
        $logStmt->execute();

        $conn->commit();

        apiSuccess(['user_id' => $user_id, 'employee_code' => $employee_code], 'User updated successfully');
    } else {
        throw new Exception($stmt->error);
    }

} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    apiError($e->getMessage(), 500);
}
