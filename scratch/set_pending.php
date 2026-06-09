<?php
include 'include/config.php';
$conn->query("UPDATE gate_pass_request_workers SET status = 'pending' WHERE workman_id = 12");
echo "Updated: " . $conn->affected_rows . " worker(s) to pending status.\n";

