<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payment_flow.php';

$workers = ['TEMP-000119', 'TEMP-000121', 'TEMP-000123'];
foreach ($workers as $temp) {
    $w = db_single($conn, "SELECT * FROM workmen WHERE temp_id = ?", 's', [$temp]);
    if ($w) {
        $wid = (int)$w['id'];
        $cid = (int)$w['contractor_id'];
        
        // Fix the draft bug!
        db_execute($conn, "UPDATE workmen SET status = 'pending', work_order_source = 'PWO', safety_fee_payment_option = 'pay_later', execution_training_status = 'pending_eo' WHERE id = ?", 'i', [$wid]);
        
        // Ensure Training Request manually
        $hasReq = db_single($conn, "SELECT id FROM training_requests WHERE workman_id = ?", 'i', [$wid]);
        if (!$hasReq) {
            db_execute($conn, "INSERT INTO training_requests (workman_id, contractor_id, training_type, requested_date, source, status, created_at, updated_at) VALUES (?, ?, 'Safety Induction', CURRENT_DATE(), 'enrolment', 'pending_eo', NOW(), NOW())", 'ii', [$wid, $cid]);
        } else {
            db_execute($conn, "UPDATE training_requests SET status = 'pending_eo' WHERE id = ?", 'i', [(int)$hasReq['id']]);
        }
        
        // Ensure Payment Request
        $paidWorkerIds = clms_paid_training_worker_ids($conn, [$wid]);
        if (!in_array($wid, $paidWorkerIds, true)) {
            $req = clms_create_training_payment_request($conn, $cid, [$wid], 1, 'enrolment');
            if ($req) {
                echo "Payment generated for $temp. <br>";
            } else {
                echo "Payment generated failed (or already exists) for $temp. <br>";
            }
        }
        
        echo "Fixed $temp <br><br>";
    } else {
        echo "Could not find $temp <br><br>";
    }
}
echo "All done! Please check training_request.php now.";
