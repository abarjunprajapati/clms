<?php
/**
 * FIXED DB Script — uses direct mysqli to force-save CSL settings
 * Upload to: https://cslweb.teleconsystems.com/fix_csl_payment2.php
 * Run ONCE, then DELETE.
 */
require_once __DIR__ . '/include/config.php';

$settings = [
    'csl_ws_url'               => 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder',
    'payment_gateway_provider' => 'csl_payment',
    'csl_source_ip'            => '52.172.135.84',
];

echo "<pre><h2>CSL Payment DB Fix (Direct SQL)</h2>\n";

foreach ($settings as $key => $value) {
    // Try UPDATE first
    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, setting_group = 'payment', updated_at = NOW() WHERE setting_key = ?");
    $stmt->bind_param('ss', $value, $key);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        // Key doesn't exist — INSERT it
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_at) VALUES (?, ?, 'payment', 'CSL Payment setting', NOW())");
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $inserted = $stmt->affected_rows;
        $stmt->close();
        echo ($inserted > 0 ? "✅ INSERTED" : "❌ FAILED INSERT") . ": $key = $value\n";
    } else {
        echo "✅ UPDATED: $key = $value\n";
    }
}

echo "\n--- Verify Current DB Values ---\n";
$keys = ['csl_ws_url', 'payment_gateway_provider', 'csl_source_ip', 'payment_gateway_key_secret'];
foreach ($keys as $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $val = $row['setting_value'] ?? '(NOT FOUND IN DB)';
    if ($key === 'payment_gateway_key_secret' && strlen($val) > 4) {
        $val = substr($val, 0, 4) . str_repeat('*', strlen($val) - 4);
    }
    echo "$key = $val\n";
}

echo "\n";
echo "⚠️  IMPORTANT: payment_gateway_key_secret must be the CSL HMAC secret key\n";
echo "   (NOT the Razorpay key). Ask the CSL safety team to provide it.\n";
echo "\n⚠️  DELETE this file from server after verifying!\n</pre>";
