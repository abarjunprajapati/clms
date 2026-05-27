<?php
include 'include/config.php';
// 1. Reset documents to pending
$conn->query("UPDATE documents SET status = 'pending' WHERE workman_id = 12");
// 2. Reset workman status
$conn->query("UPDATE workmen SET status = 'pending', pass_issuer_verified = 0 WHERE id = 12");
// 3. Reset request status
$conn->query("UPDATE gate_pass_request_workers SET status = 'pending' WHERE workman_id = 12");

echo "Worker 12 reset to PENDING for testing.\n";
echo "Documents: " . $conn->query("SELECT status FROM documents WHERE workman_id = 12")->fetch_row()[0] . "\n";
echo "Request: " . $conn->query("SELECT status FROM gate_pass_request_workers WHERE workman_id = 12")->fetch_row()[0] . "\n";

