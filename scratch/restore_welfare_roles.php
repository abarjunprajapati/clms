<?php
include 'include/config.php';

echo "Restoring roles based on IDs...\n";

// Restore ID 5 to welfare_admin
$conn->query("UPDATE users SET role = 'welfare_admin' WHERE id = 5");
echo "ID 5 restored: " . $conn->affected_rows . "\n";

// Restore ID 8 to welfare_user
$conn->query("UPDATE users SET role = 'welfare_user' WHERE id = 8");
echo "ID 8 restored: " . $conn->affected_rows . "\n";

echo "Restoration complete.\n";

