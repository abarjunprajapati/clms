<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payment_flow.php';

echo "CLMS LIVE DB CHECK - PAYMENT & QR SETTINGS\n";
echo "========================================\n\n";

$provider = clms_payment_setting($conn, 'payment_gateway_provider', 'demo_qr');
$merchant = clms_payment_setting($conn, 'payment_demo_merchant_name', 'CLMS Safety Training');
$upi = clms_payment_setting($conn, 'payment_demo_upi_id', 'clms-demo@upi');
$qr_path = clms_payment_setting($conn, 'payment_demo_qr_path', '');
$fee = clms_payment_setting($conn, 'training_fee_per_worker', '500');

echo "Provider: $provider\n";
echo "Merchant Name: $merchant\n";
echo "UPI ID: $upi\n";
echo "QR Path: $qr_path\n";
echo "Fee Per Worker: $fee\n\n";

echo "FILES IN uploads/payment_qr/:\n";
$dir = __DIR__ . '/uploads/payment_qr/';
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file (" . filesize($dir . $file) . " bytes)\n";
        }
    }
} else {
    echo "Directory uploads/payment_qr/ does not exist.\n";
}
