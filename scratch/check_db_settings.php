<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/payment_flow.php';

$qrPath = clms_payment_setting($conn, 'payment_demo_qr_path', 'default_empty');
$demo = clms_demo_payment_details($conn);
$history = clms_get_qr_history($conn);

echo "--- QR PATH SETTING ---\n";
var_dump($qrPath);

echo "\n--- DEMO PAYMENT DETAILS ---\n";
print_r($demo);

echo "\n--- QR HISTORY ---\n";
print_r($history);
