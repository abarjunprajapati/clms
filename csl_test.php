<?php
require_once __DIR__ . '/include/config.php';
header('Content-Type: text/plain');

// Fix URL directly
$url = 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder';
$conn->query("UPDATE system_settings SET setting_value='$url' WHERE setting_key='csl_ws_url'");
if ($conn->affected_rows == 0) {
    $conn->query("INSERT INTO system_settings (id,setting_key,setting_value,setting_group) SELECT COALESCE(MAX(id),0)+1,'csl_ws_url','$url','payment' FROM system_settings");
}

// Get secret
$r = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='payment_gateway_key_secret' LIMIT 1");
$secret = trim((string)($r->fetch_assoc()['setting_value'] ?? ''));

// Server IP
$ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '52.172.135.84';

// Generate token
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
    mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
$ts = round(microtime(true)*1000);
$msg = "SFTCLS|$ip|$ts|$uuid";
$sig = base64_encode(hash_hmac('sha256',$msg,$secret,true));
$token = " ".base64_encode("$msg|$sig")." 0";

$payload = json_encode([
    "source_IP"=>"$ip","source_Type"=>"WEB","app_ID"=>"CLMS_SFTCLS",
    "remit_Req_ID"=>"0011000000","total_Amount"=>"1000","paymode"=>"RAZORPAY",
    "ref_Trans_List"=>[["aadharno"=>"123456789012","attemptno"=>"1","amount"=>"1000"]]
]);

$ch = curl_init($url);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>$payload,
    CURLOPT_HTTPHEADER=>["Content-Type: application/json","token: $token"],
    CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>15
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);

echo "IP=$ip\n";
echo "SECRET=".substr($secret,0,6)."***\n";
echo "URL=$url\n";
echo "HTTP=$code\n";
echo "CURL_ERR=".($err?:'none')."\n";
echo "RESPONSE=".substr($resp,0,400)."\n";
