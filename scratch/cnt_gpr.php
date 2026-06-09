<?php
include 'include/config.php';
$r1 = $conn->query("SELECT COUNT(*) FROM gate_pass_requests");
$r2 = $conn->query("SELECT COUNT(*) FROM gate_pass_request_workers");
echo "Requests: " . $r1->fetch_row()[0] . "\n";
echo "Workers: " . $r2->fetch_row()[0] . "\n";

