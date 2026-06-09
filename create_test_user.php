<?php
require_once 'include/config.php';

$contractorId = 'CONT-2024-001';
$name = 'Test Contractor';
$email = 'test@example.com';
$mobile = '9876543210';
$role = 'contractor';
$password = password_hash('password123', PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "INSERT INTO users (contractor_id, name, email, mobile, role, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ssssss", $contractorId, $name, $email, $mobile, $role, $password);

if (mysqli_stmt_execute($stmt)) {
    echo "Test user created successfully!\n";
    echo "Contractor ID: $contractorId\n";
    echo "Password: password123\n";
} else {
    echo "Error creating test user: " . mysqli_stmt_error($stmt) . "\n";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
