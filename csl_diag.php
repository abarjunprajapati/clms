<?php
/**
 * CSL Diagnostic - shows exact URL used + does a real test cURL call
 * Upload to: https://cslweb.teleconsystems.com/csl_diag.php
 * Run ONCE then DELETE.
 */
require_once __DIR__ . '/include/config.php';

echo "<pre><h2>CSL Diagnostic</h2>\n";

// Step 1: Show what's in DB right now
echo "=== DB Values (raw SELECT) ===\n";
$keys = ['csl_ws_url', 'csl_source_ip', 'payment_gateway_provider', 'payment_gateway_key_secret'];
foreach ($keys as $key) {
    $r = $conn->query("SELECT id, setting_value FROM system_settings WHERE setting_key = '$key' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    $val = $row['setting_value'] ?? '(NOT IN DB)';
    $id  = $row['id'] ?? '-';
    if (str_contains($key, 'secret') && strlen($val) > 6) {
        $val = substr($val, 0, 6) . str_repeat('*', strlen($val) - 6);
    }
    echo "[$id] $key = $val\n";
}

// Step 2: Force correct URL now
echo "\n=== Force setting csl_ws_url ===\n";
$correctUrl = 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder';
$r = $conn->query("SELECT id FROM system_settings WHERE setting_key = 'csl_ws_url' LIMIT 1");
$row = $r ? $r->fetch_assoc() : null;
if ($row) {
    $id = (int)$row['id'];
    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE id = ?");
    $stmt->bind_param('si', $correctUrl, $id);
    $stmt->execute();
    echo ($stmt->affected_rows >= 0 ? "✅" : "❌") . " Updated id=$id to: $correctUrl\n";
    $stmt->close();
} else {
    // Try INSERT with computed id
    $conn->query("INSERT INTO system_settings (id, setting_key, setting_value, setting_group, description) SELECT COALESCE(MAX(id),0)+1, 'csl_ws_url', '$correctUrl', 'payment', 'CSL WS URL' FROM system_settings");
    echo ($conn->affected_rows > 0 ? "✅ Inserted" : "❌ Failed: " . $conn->error) . "\n";
}

// Step 3: Do a REAL test cURL call to CSL
echo "\n=== Real cURL Test to CSL API ===\n";
$secret = '';
$r2 = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'payment_gateway_key_secret' LIMIT 1");
$row2 = $r2 ? $r2->fetch_assoc() : null;
$secret = trim((string)($row2['setting_value'] ?? ''));

$ip = $_SERVER['SERVER_ADDR'] ?? '52.172.135.84';
if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
    $ip = '52.172.135.84';
}

echo "Server IP used: $ip\n";
echo "Secret set: " . (empty($secret) ? "❌ NO" : "✅ YES (" . substr($secret,0,6) . "...)") . "\n";
echo "Calling URL: $correctUrl\n\n";

// Generate token
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff)|0x4000, mt_rand(0, 0x3fff)|0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);
$timestamp = round(microtime(true) * 1000);
$msg = "SFTCLS|$ip|$timestamp|$uuid";
$sig = base64_encode(hash_hmac('sha256', $msg, $secret, true));
$token = " " . base64_encode("$msg|$sig") . " 0";

// Minimal test payload
$payload = json_encode([
    "source_IP"    => $ip,
    "source_Type"  => "WEB",
    "app_ID"       => "CLMS_SFTCLS",
    "remit_Req_ID" => "0011000000",
    "total_Amount" => "1000",
    "paymode"      => "RAZORPAY",
    "ref_Trans_List" => [
        ["aadharno" => "123456789012", "attemptno" => "1", "amount" => "1000"]
    ]
]);

echo "Payload sent:\n$payload\n\nToken (first 40 chars): " . substr($token, 0, 40) . "...\n\n";

$ch = curl_init($correctUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "token: " . $token
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Never follow redirects
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response  = curl_exec($ch);
$curlErr   = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "=== cURL Result ===\n";
echo "HTTP Code : $httpCode\n";
echo "Final URL : $finalUrl\n";
echo "cURL Error: " . ($curlErr ?: 'none') . "\n";
echo "Response  :\n" . substr($response, 0, 500) . "\n";

echo "\n⚠️  DELETE this file after done!\n</pre>";
