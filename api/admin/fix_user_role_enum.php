<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "ALTER TABLE users MODIFY role ENUM(
    'contractor',
    'welfare_admin',
    'welfare_user',
    'safety_user',
    'front_line_user',
    'pass_user',
    'super_admin',
    'execution_officer',
    'execution'
) DEFAULT 'contractor'";

if (!mysqli_query($conn, $sql)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update users.role enum',
        'error' => mysqli_error($conn)
    ]);
    exit;
}

$email = trim($_GET['email'] ?? '');
$role = trim($_GET['role'] ?? 'execution_officer');
$updated = 0;

if ($email !== '') {
    $allowed = ['execution_officer', 'execution'];
    if (!in_array($role, $allowed, true)) {
        $role = 'execution_officer';
    }

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE email = ?");
    $stmt->bind_param('ss', $role, $email);
    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'message' => 'users.role enum now supports execution roles.',
    'updated_users' => $updated,
    'email' => $email ?: null,
    'role' => $email ? $role : null
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
