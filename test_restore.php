<?php
require_once __DIR__ . '/include/config.php';
$conn->query("INSERT INTO noc_requests (workman_id, to_contractor_id, from_contractor_id, noc_status, created_at) VALUES (54, 78, 3, 'pending', NOW())");
echo "Restored the request from 1100908 to 1100909.";
