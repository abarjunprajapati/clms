<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/include/config.php';

$temp = 'TEMP-000123';
$w = db_single($conn, "SELECT id FROM workmen WHERE temp_id = ?", 's', [$temp]);
if ($w) {
    $wid = (int)$w['id'];
    
    // It's already paid, so it should be pending_booking now
    db_execute($conn, "UPDATE workmen SET execution_training_status = 'pending_booking' WHERE id = ?", 'i', [$wid]);
    
    // Update the training request to pending so it can be booked
    db_execute($conn, "UPDATE training_requests SET status = 'pending' WHERE workman_id = ?", 'i', [$wid]);
    
    echo "Fixed $temp! It is now ready for booking.";
}
