<?php
require_once 'include/config.php';

// 1. Populate roles table
$standard_roles = [
    'super_admin',
    'admin',
    'welfare_admin',
    'welfare_user',
    'safety_user',
    'front_line_user',
    'pass_user',
    'contractor'
];

echo "Populating roles table...\n";
foreach ($standard_roles as $role) {
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO roles (role_name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $role);
    mysqli_stmt_execute($stmt);
}

// 2. Fetch all roles to map name -> id
$roles_map = [];
$res = mysqli_query($conn, "SELECT id, role_name FROM roles");
while ($row = mysqli_fetch_assoc($res)) {
    $roles_map[$row['role_name']] = $row['id'];
}

// 3. Update users table schema (ensure ENUM is correct)
echo "Updating users table ENUM...\n";
$enum_values = "'" . implode("','", $standard_roles) . "'";
$alter_sql = "ALTER TABLE users MODIFY COLUMN role ENUM($enum_values) DEFAULT 'contractor'";
if (!mysqli_query($conn, $alter_sql)) {
    echo "Error updating ENUM: " . mysqli_error($conn) . "\n";
}

// 4. Map users to role_id based on role name
echo "Mapping users to role_id...\n";
foreach ($roles_map as $name => $id) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET role_id = ? WHERE role = ? OR (role = '' AND contractor_id = ?)");
    // Special case for ID 3 who has role='' but contractor_id='admin'
    $contractor_id_match = ($name === 'super_admin') ? 'admin' : '____none____';
    mysqli_stmt_bind_param($stmt, "iss", $id, $name, $contractor_id_match);
    mysqli_stmt_execute($stmt);
}

// Special fix for the 'admin' user with ID 3
echo "Fixing user ID 3...\n";
mysqli_query($conn, "UPDATE users SET role = 'super_admin', role_id = {$roles_map['super_admin']} WHERE id = 3");

echo "Database roles fixed successfully.\n";
?>

