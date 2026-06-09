<?php
require 'include/config.php';

$users = [
    [
        'contractor_id' => 'admin',
        'name' => 'Super Admin',
        'role' => 'admin',
        'password' => 'admin123'
    ],
    [
        'contractor_id' => 'contractor1',
        'name' => 'John Contractor',
        'role' => 'contractor',
        'password' => 'pass123'
    ],
    [
        'contractor_id' => 'welfare1',
        'name' => 'Welfare Officer',
        'role' => 'welfare_admin',
        'password' => 'welfare123'
    ],
    [
        'contractor_id' => 'safety1',
        'name' => 'Safety Officer',
        'role' => 'safety_user',
        'password' => 'safety123'
    ]
];

foreach ($users as $u) {
    $hashed = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (contractor_id, name, role, password, status, email, mobile) VALUES (?, ?, ?, ?, 'active', ?, '0000000000') ON DUPLICATE KEY UPDATE role = ?, password = ?");
    $email = strtolower($u['contractor_id']) . "@example.com";
    $stmt->bind_param("sssssss", $u['contractor_id'], $u['name'], $u['role'], $hashed, $email, $u['role'], $hashed);
    if ($stmt->execute()) {
        echo "User {$u['contractor_id']} (Role: {$u['role']}) updated/created.\n";
    } else {
        echo "Error for {$u['contractor_id']}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

