<?php
$cslWsUrl = "https://ws.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder";
$ip = '103.217.132.114';
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
echo "HTTP Code: $httpCode\n";
echo "cURL Error: $err\n";
echo "Response: $response\n";
?>
