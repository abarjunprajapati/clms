<?php
/**
 * ONE-TIME FIX SCRIPT for CSL Payment Settings
 * Upload this to: https://cslweb.teleconsystems.com/fix_csl_payment.php
 * Run it ONCE from browser, then DELETE the file.
 */

require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payment_flow.php';

$results = [];

// Fix 1: Set correct CSL WS URL
clms_set_payment_setting($conn, 'csl_ws_url', 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder', 1);
$results[] = "✅ csl_ws_url set to: https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder";

// Fix 2: Set provider to csl_payment
clms_set_payment_setting($conn, 'payment_gateway_provider', 'csl_payment', 1);
$results[] = "✅ payment_gateway_provider set to: csl_payment";

// Fix 3: Set source IP to the live server IP
clms_set_payment_setting($conn, 'csl_source_ip', '52.172.135.84', 1);
$results[] = "✅ csl_source_ip set to: 52.172.135.84";

// Verify what's in DB now
echo "<pre><h2>CSL Payment Fix Results</h2>\n";
foreach ($results as $r) {
    echo $r . "\n";
}

echo "\n--- Current DB Values ---\n";
$keys = ['csl_ws_url', 'payment_gateway_provider', 'csl_source_ip', 'payment_gateway_key_secret', 'payment_gateway_key_id'];
foreach ($keys as $key) {
    $val = clms_payment_setting($conn, $key, '(not set)');
    // Mask secret
    if ($key === 'payment_gateway_key_secret' && strlen($val) > 4) {
        $val = substr($val, 0, 4) . str_repeat('*', strlen($val) - 4);
    }
    echo "$key = $val\n";
}

echo "\n⚠️  DELETE this file from server after running it!\n</pre>";
