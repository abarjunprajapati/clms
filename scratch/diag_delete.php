<?php
include 'include/config.php';
$identifier = 'V1001';
$res = mysqli_query($conn, "SELECT id, name, role, contractor_id FROM users WHERE contractor_id='$identifier' OR email='V1001@sap-vendor.com'");
if (mysqli_num_rows($res) == 0) {
    echo "User $identifier not found in users table.\n";
    exit;
}
$user = mysqli_fetch_assoc($res);
$uid = $user['id'];
echo "Found User: {$user['name']} (ID: $uid, Role: {$user['role']})\n";

$tables = [
    'contractors' => 'user_id',
    'approvals' => 'approved_by',
    'contractor_blocks' => 'blocked_by',
    'logs' => 'user_id',
    'notifications' => 'user_id',
    'audit_logs' => 'user_id'
];

echo "\nChecking for foreign key references:\n";
foreach ($tables as $table => $column) {
    $q = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table WHERE $column = $uid");
    if ($q) {
        $row = mysqli_fetch_assoc($q);
        if ($row['count'] > 0) {
            echo " - $table: {$row['count']} records found (Column: $column)\n";
        }
    } else {
        echo " - Error checking table $table: " . mysqli_error($conn) . "\n";
    }
}
?>
