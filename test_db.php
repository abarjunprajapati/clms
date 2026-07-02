<?php
require_once __DIR__ . '/include/config.php';
echo "<h2>Users Table for 1100908 and 1100909</h2>";
$res = $conn->query("SELECT id, contractor_id, role FROM users WHERE contractor_id IN ('1100908', '1100909')");
if ($res) {
    echo "<table border='1'><tr><th>User ID</th><th>Contractor Code</th><th>Role</th></tr>";
    while ($r = $res->fetch_assoc()) {
        echo "<tr><td>{$r['id']}</td><td>{$r['contractor_id']}</td><td>{$r['role']}</td></tr>";
    }
    echo "</table>";
} else echo "Error: " . $conn->error;

echo "<h2>Contractors Table for 1100908 and 1100909</h2>";
$res2 = $conn->query("SELECT id, user_id, vendor_code, contractor_name FROM contractors WHERE vendor_code IN ('1100908', '1100909')");
if ($res2) {
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Vendor Code</th><th>Contractor Name</th></tr>";
    while ($r = $res2->fetch_assoc()) {
        echo "<tr><td>{$r['id']}</td><td>{$r['user_id']}</td><td>{$r['vendor_code']}</td><td>{$r['contractor_name']}</td></tr>";
    }
    echo "</table>";
} else echo "Error: " . $conn->error;
