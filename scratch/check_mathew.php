<?php
require_once __DIR__ . '/../include/config.php';

echo "=== Contractors with Mathew/Joseph ===\n";
$res = $conn->query("SELECT id, vendor_code, vendor_name, contractor_name, status FROM contractors WHERE contractor_name LIKE '%Mathew%' OR vendor_name LIKE '%Mathew%' OR contractor_name LIKE '%Joseph%' OR vendor_name LIKE '%Joseph%'");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n=== Workmen with Mathew/Joseph ===\n";
$res = $conn->query("SELECT id, name, contractor_id, status, worker_type FROM workmen WHERE name LIKE '%Mathew%' OR name LIKE '%Joseph%'");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n=== All Workmen under Contractor Mathew Joseph (if found) ===\n";
// Let's assume Mathew Joseph is a contractor, let's fetch all workmen under him
$res = $conn->query("SELECT id, name, contractor_id, status, worker_type FROM workmen WHERE contractor_id IN (SELECT id FROM contractors WHERE contractor_name LIKE '%Mathew%' OR vendor_name LIKE '%Mathew%')");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
