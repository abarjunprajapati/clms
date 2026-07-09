<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/include/config.php';

$temp = 'TEMP-000124';
$w = db_single($conn, "SELECT id, contractor_id, name, temp_id, status FROM workmen WHERE temp_id = ?", 's', [$temp]);

if ($w) {
    $wid = (int)$w['id'];
    $cid = (int)$w['contractor_id'];
    
    // Check if training request exists
    $hasReq = db_single($conn, "SELECT id FROM training_requests WHERE workman_id = ?", 'i', [$wid]);
    if (!$hasReq) {
        db_execute($conn, "INSERT INTO training_requests (workman_id, contractor_id, training_type, requested_date, source, status, created_at, updated_at) VALUES (?, ?, 'Safety Induction', CURRENT_DATE(), 'enrolment', 'pending_booking', NOW(), NOW())", 'ii', [$wid, $cid]);
        echo "Created training request for $temp.<br>";
    } else {
        echo "Training request already exists for $temp.<br>";
    }
}
echo "Done.";
