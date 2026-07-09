<?php
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payment_csl.php';

$ip_from_db = trim((string)clms_payment_setting($conn, 'csl_source_ip', ''));
$ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '127.0.0.1';

// get public ip
$public_ip = file_get_contents('https://api.ipify.org');

if (
    $ip === '127.0.0.1' || $ip === '::1' ||
    substr($ip, 0, 8) === '192.168.' ||
    substr($ip, 0, 3) === '10.' ||
    substr($ip, 0, 7) === '172.16.'
) {
    $ip = '52.172.135.84';
}

echo "DB Setting csl_source_ip: " . ($ip_from_db ?: 'NOT SET') . "\n";
echo "Server Local IP: " . ($_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'Unknown') . "\n";
echo "Server Public IP: " . $public_ip . "\n";
echo "Final IP to send: " . (!empty($ip_from_db) ? $ip_from_db : $ip) . "\n";

echo "\n--- TEST CSL API ---\n";
$dummy_req = [
    'id' => 1,
    'contractor_id' => 1100908,
    'total_amount' => 100
];

// $res = clms_csl_create_payment_order($conn, $dummy_req);
// print_r($res);
?>
