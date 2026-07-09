<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/include/config.php';

$temp = 'TEMP-000124';
$w = db_single($conn, "SELECT id, contractor_id, name, temp_id, status FROM workmen WHERE temp_id = ?", 's', [$temp]);

if (!$w) {
    echo "<h1>Error: Worker $temp NOT FOUND in workmen table!</h1>";
} else {
    echo "<h1>Worker Found</h1>";
    echo "<pre>";
    print_r($w);
    echo "</pre>";
    
    $wid = (int)$w['id'];
    
    $tr = db_fetch_all($conn, "SELECT id, status FROM training_requests WHERE workman_id = ?", 'i', [$wid]);
    echo "<h2>Training Requests:</h2>";
    echo "<pre>";
    print_r($tr);
    echo "</pre>";
}
