<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/include/config.php';

$workers = ['TEMP-000123'];
foreach ($workers as $temp) {
    $w = db_single($conn, "SELECT id FROM workmen WHERE temp_id = ?", 's', [$temp]);
    if ($w) {
        $wid = (int)$w['id'];
        
        // Fix the execution_training_status!
        // It was set to pending_eo in the previous script, but because they haven't booked a batch yet, it should be pending_payment.
        db_execute($conn, "UPDATE workmen SET execution_training_status = 'pending_payment' WHERE id = ?", 'i', [$wid]);
        
        // Also update the training_requests status to match!
        db_execute($conn, "UPDATE training_requests SET status = 'pending_payment' WHERE workman_id = ?", 'i', [$wid]);
        
        echo "Successfully reverted $temp back to pending_payment!<br>";
    } else {
        echo "Could not find $temp <br>";
    }
}
