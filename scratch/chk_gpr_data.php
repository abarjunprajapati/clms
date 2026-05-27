<?php
include 'include/config.php';
echo "--- gate_pass_requests Count ---\n";
$r = $conn->query("SELECT COUNT(*) as count FROM gate_pass_requests");
$row = $r->fetch_assoc();
echo "Count: " . $row['count'] . "\n";

echo "\n--- Latest 5 gate_pass_requests ---\n";
$r = $conn->query("SELECT * FROM gate_pass_requests ORDER BY created_at DESC LIMIT 5");
if ($r) {
    while($row = $r->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n--- gate_pass_request_workers Count ---\n";
$r = $conn->query("SELECT COUNT(*) as count FROM gate_pass_request_workers");
$row = $r->fetch_assoc();
echo "Count: " . $row['count'] . "\n";

