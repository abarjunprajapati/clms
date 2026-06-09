<?php
require_once 'include/config.php';

$roles = [
    'super_admin',
    'welfare_admin',
    'welfare_user',
    'contractor',
    'front_line_user',
    'pass_user',
    'safety_user'
];

$password = password_hash('password123', PASSWORD_DEFAULT);

foreach ($roles as $role) {
    $email = "test_{$role}@example.com";
    $name = ucwords(str_replace('_', ' ', $role)) . " Test";
    
    // Check if user exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE role = '$role' LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO users (role, name, email, mobile, password, status) 
                VALUES ('$role', '$name', '$email', '1234567890', '$password', 'active')";
        if (mysqli_query($conn, $sql)) {
            echo "Created user for role: $role\n";
        } else {
            echo "Error creating user for role $role: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "User for role $role already exists.\n";
    }
}

