<?php
require 'include/config.php';
$q = "SELECT n.*, w.name, w.temp_id, w.aadhaar, c.contractor_name as requesting_contractor 
      FROM noc_requests n 
      JOIN workmen w ON n.workman_id = w.id 
      JOIN contractors c ON n.requesting_contractor_id = c.user_id 
      WHERE n.existing_contractor_id = ? ORDER BY n.created_at DESC";
$stmt = $conn->prepare($q);
if (!$stmt) {
    echo "ERROR 1: " . $conn->error . "\n";
} else {
    echo "SUCCESS 1\n";
}

$q2 = "SELECT n.*, w.name, w.temp_id, w.aadhaar 
        FROM noc_requests n 
        JOIN workmen w ON n.workman_id = w.id 
        WHERE n.requesting_contractor_id = ? ORDER BY n.created_at DESC";
$stmt2 = $conn->prepare($q2);
if (!$stmt2) {
    echo "ERROR 2: " . $conn->error . "\n";
} else {
    echo "SUCCESS 2\n";
}
