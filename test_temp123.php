<?php
require_once __DIR__ . '/include/config.php';
$w = db_single($conn, "SELECT id, work_order_no, work_order_source, safety_fee_payment_option, status FROM workmen WHERE temp_id = 'TEMP-000123'");
var_dump($w);
$pr = db_fetch_all($conn, "SELECT pr.* FROM training_payment_requests pr JOIN training_payment_request_workers pw ON pw.payment_request_id = pr.id WHERE pw.workman_id = " . (int)$w['id']);
var_dump($pr);
