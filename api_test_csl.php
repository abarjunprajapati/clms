<?php
$env = $_GET['env'] ?? 'dev';
$cslWsUrl = ($env === 'prod') 
    ? "https://ws.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder"
    : "https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder";

$ip = '52.172.135.84';
$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000, mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
$timestamp = round(microtime(true) * 1000);
$secret = 'dummy_secret';
$msg = "SFTCLS|$ip|$timestamp|$uuid";
$sig = base64_encode(hash_hmac('sha256', $msg, $secret, true));
$token_body = "$msg|$sig";
$csl_token = " " . base64_encode($token_body) . " 0";
$payload = [
    "source_IP" => $ip,
    "source_Type" => "WEB",
    "app_ID" => "CLMS_SFTCLS",
    "remit_Req_ID" => "1100908",
    "total_Amount" => "100",
    "paymode" => "RAZORPAY",
    "token" => $csl_token,
    "ref_Trans_List" => [
        [
            "aadharno" => "123456789012",
            "attemptno" => "1",
            "amount" => "100"
        ]
    ]
];
$ch = curl_init($cslWsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "token: " . $csl_token
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<div style='font-family:Arial; max-width:800px; margin:0 auto; padding:20px;'>";
echo "<h2 style='color:#1e3a8a;'>CSL API Connection Test</h2>";
echo "<div style='margin-bottom:20px;'>";
echo "<a href='?env=dev' style='padding:10px 15px; background:".($env==='dev'?'#2563eb':'#e2e8f0')."; color:".($env==='dev'?'white':'black')."; text-decoration:none; border-radius:5px; margin-right:10px;'>Test DEV (wsdev)</a>";
echo "<a href='?env=prod' style='padding:10px 15px; background:".($env==='prod'?'#2563eb':'#e2e8f0')."; color:".($env==='prod'?'white':'black')."; text-decoration:none; border-radius:5px;'>Test PROD (ws)</a>";
echo "</div>";

echo "<div style='background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #cbd5e1;'>";
echo "<strong>Server Outgoing IP (Sent to CSL):</strong> " . $ip . "<br>";
echo "<strong>Testing Endpoint:</strong> " . $cslWsUrl . "<br>";
echo "<strong>HTTP Code:</strong> " . $httpCode . "<br>";
if ($err) {
    echo "<strong>cURL Error:</strong> <span style='color:red;'>" . $err . "</span><br>";
}
echo "</div>";

echo "<h3 style='margin-top:25px;'>Request Headers Sent:</h3>";
echo "<pre style='background:#f1f5f9; padding:15px; border-radius:8px;'>";
print_r([
    "Content-Type: application/json",
    "token: " . $csl_token
]);
echo "</pre>";

echo "<h3>Request Body Sent:</h3>";
echo "<pre style='background:#f1f5f9; padding:15px; border-radius:8px;'>";
echo json_encode($payload, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h3 style='margin-top:25px;'>Raw Response from CSL:</h3>";
echo "<div style='padding:15px; background:#1e293b; color:#10b981; border-radius:8px; font-family:monospace; white-space:pre-wrap; overflow-wrap:break-word;'>";
echo htmlspecialchars($response);
echo "</div>";

echo "<div style='margin-top:20px; font-size:14px; color:#475569;'>";
echo "<strong>Note:</strong> If the response says <em>'IP not in trusted host list'</em>, it means CSL's firewall is still blocking requests from <code>$ip</code> to this specific endpoint.";
echo "</div>";
echo "</div>";
?>
