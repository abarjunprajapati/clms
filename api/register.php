<?php
/**
 * Register User API (Updated)
 * Creates new user account (welfare_admin / super_admin only)
 * Sets must_change_password = 1 so user sets own password on first login
 */
require_once __DIR__ . '/../include/config.php';
require_once 'api_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Use normalized role check
    $currentRole = get_normalized_role();
    if (empty($_SESSION['user_id']) || !in_array($currentRole, ['super_admin', 'welfare_admin'])) {
        $msg = "Unauthorized. Admin access required.";
        if (isset($_SESSION['user_id'])) {
            $msg .= " (Current Role: " . ($_SESSION['role'] ?? 'NONE') . " | Normalized: " . ($currentRole ?? 'NONE') . ")";
        } else {
            $msg .= " (Session Not Found)";
        }
        apiError($msg, 403);
    }

    $input = getApiInput();

    $contractor_id = trim($input['contractor_id'] ?? '');
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $role = $input['role'] ?? 'contractor';
    $password = $input['password'] ?? '';
    $status = $input['status'] ?? 'active';

    // Validation
    if (empty($contractor_id)) apiError('User ID / Contractor ID is required', 400);
    if (empty($name)) apiError('Name is required', 400);
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) apiError('Valid email is required', 400);
    if (empty($password) || strlen($password) < 6) apiError('Password must be at least 6 characters', 400);

    $validRoles = ['contractor', 'welfare_admin', 'welfare_user', 'safety_user', 'front_line_user', 'pass_user', 'super_admin', 'execution_officer'];
    if (!in_array($role, $validRoles)) apiError('Invalid role', 400);
    if (!in_array($status, ['active', 'inactive'])) apiError('Invalid status', 400);

    // Check if contractor_id already exists
    $count = db_count($conn, "SELECT COUNT(*) FROM users WHERE contractor_id = ?", 's', [$contractor_id]);
    if ($count > 0) apiError('This User ID already exists', 409);

    // Check if email already exists
    $count = db_count($conn, "SELECT COUNT(*) FROM users WHERE email = ?", 's', [$email]);
    if ($count > 0) apiError('Email already exists', 409);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert user with must_change_password = 1
        $stmt = $conn->prepare("
            INSERT INTO users (contractor_id, name, email, mobile, role, password, status, must_change_password, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        if (!$stmt) throw new Exception('Database error: ' . $conn->error);

        $stmt->bind_param('sssssss', $contractor_id, $name, $email, $mobile, $role, $hashedPassword, $status);

        if (!$stmt->execute()) throw new Exception('Failed to create user: ' . $stmt->error);

        $userId = $conn->insert_id;
        $stmt->close();

        // If role is execution_officer, sync with execution_officers table
        if ($role === 'execution_officer') {
            $stmtEO = $conn->prepare("INSERT INTO execution_officers (employee_code, name, email, mobile, status) VALUES (?, ?, ?, ?, ?)");
            if (!$stmtEO) throw new Exception('EO Database error: ' . $conn->error);
            $stmtEO->bind_param('sssss', $contractor_id, $name, $email, $mobile, $status);
            if (!$stmtEO->execute()) throw new Exception('Failed to create officer record: ' . $stmtEO->error);
            $stmtEO->close();
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        apiError($e->getMessage(), 500);
    }

    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logValue = json_encode(['user_id' => $userId, 'contractor_id' => $contractor_id, 'name' => $name, 'role' => $role]);
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, new_value, remarks, ip_address, created_at) VALUES (?, 'create_user', 'user_management', ?, ?, ?, NOW())",
        'isss', [$_SESSION['user_id'], $logValue, "Created user: $name ($contractor_id) as $role", $ip]
    );

    apiSuccess([
        'user_id' => $userId,
        'contractor_id' => $contractor_id,
        'role' => $role,
        'status' => $status,
        'must_change_password' => true
    ], 'User created successfully. User must set new password on first login.');

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
