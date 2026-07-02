<?php
require 'include/config.php';
$w = db_single($conn, "SELECT * FROM workmen WHERE name LIKE '%Amjad%' LIMIT 1");
if($w) {
    echo 'Workman ID: ' . $w['id'] . ', Status: ' . $w['safety_enrollment_status'] . ', Aadhaar: ' . $w['aadhaar'] . PHP_EOL;
    $tbw = db_fetch_all($conn, 'SELECT * FROM training_batch_workers WHERE workman_id='.$w['id']);
    echo 'TBW: ' . json_encode($tbw) . PHP_EOL;
    $tr = db_fetch_all($conn, 'SELECT * FROM training_requests WHERE workman_id='.$w['id']);
    echo 'TR: ' . json_encode($tr);
} else {
    echo 'No workman found';
}
