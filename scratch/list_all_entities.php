<?php
require_once __DIR__ . '/../include/config.php';

echo "=== ALL CONTRACTORS ===\n";
$res = $conn->query("SELECT id, vendor_code, vendor_name, contractor_name, status, user_id FROM contractors");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "=== ALL USERS ===\n";
$res = $conn->query("SELECT id, contractor_id, name, role, email, status FROM users");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
