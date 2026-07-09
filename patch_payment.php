<?php
/**
 * AUTO-PATCH: Rewrites payment_csl.php and create_training_order.php on live server
 * Upload to: https://cslweb.teleconsystems.com/patch_payment.php
 * Run ONCE, then DELETE.
 */
header('Content-Type: text/plain');

$base = __DIR__;

// =========================================================
// FILE 1: include/payment_csl.php
// =========================================================
$cslFile = $base . '/include/payment_csl.php';
$cslContent = <<<'PHPEOF'
<?php
/**
 * CSL Payment Gateway REST API Integration
 */

function clms_csl_generate_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function clms_csl_generate_auth_token($secret, $ip, $uuid, $timestamp) {
    $msg        = "SFTCLS|$ip|$timestamp|$uuid";
    $sig        = base64_encode(hash_hmac('sha256', $msg, $secret, true));
    $token_body = "$msg|$sig";
    return " " . base64_encode($token_body) . " 0";
}

function clms_csl_create_payment_order($conn, $paymentRequest) {
    clms_ensure_payment_flow($conn);

    // HARDCODED — never read from DB to avoid misconfiguration
    $cslWsUrl = 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder';
    $secret   = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

    if (empty($secret)) {
        return ["status" => false, "message" => "CSL Payment API secret key not configured."];
    }

    // Build ref_Trans_List from workers
    $workers          = clms_training_payment_workers($conn, $paymentRequest['id']);
    $refTransList     = [];
    $calculatedTotal  = 0;
    foreach ($workers as $w) {
        $aadhaar = preg_replace('/\D/', '', $w['aadhaar'] ?? '');
        if (strlen($aadhaar) !== 12) {
            $aadhaar = str_pad($aadhaar, 12, '0', STR_PAD_LEFT);
        }
        $amt              = (int)($w['safety_fee'] ?? 0);
        $calculatedTotal += $amt;
        $refTransList[]   = [
            "aadharno"  => $aadhaar,
            "attemptno" => "1",
            "amount"    => (string)$amt
        ];
    }

    // Resolve vendor code
    $vendor_code = '0000000000';
    $cRow = db_single($conn, "SELECT vendor_code, sap_code, application_no FROM contractors WHERE id = ? LIMIT 1", 'i', [(int)$paymentRequest['contractor_id']]);
    if ($cRow) {
        $vendor_code = !empty($cRow['vendor_code']) ? $cRow['vendor_code']
            : (!empty($cRow['sap_code']) ? $cRow['sap_code'] : $cRow['application_no']);
    }

    // Determine source IP — always use public IP
    $ip = '52.172.135.84'; // default public IP
    $ip_from_db = trim((string)clms_payment_setting($conn, 'csl_source_ip', ''));
    if (!empty($ip_from_db) && filter_var($ip_from_db, FILTER_VALIDATE_IP)) {
        $ip = $ip_from_db;
    } else {
        $detected = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '';
        // Only use detected IP if it is a PUBLIC IP
        if ($detected && filter_var($detected, FILTER_VALIDATE_IP)
            && !in_array($detected, ['127.0.0.1','::1'])
            && substr($detected,0,8) !== '192.168.'
            && substr($detected,0,3) !== '10.'
            && substr($detected,0,7) !== '172.16.'
        ) {
            $ip = $detected;
        }
    }

    // Generate token
    $uuid      = clms_csl_generate_uuid();
    $timestamp = round(microtime(true) * 1000);
    $csl_token = clms_csl_generate_auth_token($secret, $ip, $uuid, $timestamp);

    $payload = [
        "source_IP"      => $ip,
        "source_Type"    => "WEB",
        "app_ID"         => "CLMS_SFTCLS",
        "remit_Req_ID"   => $vendor_code,
        "total_Amount"   => (string)$calculatedTotal,
        "paymode"        => "RAZORPAY",
        "ref_Trans_List" => $refTransList
    ];

    $jsonPayload = json_encode($payload);

    // Debug log
    $debugLogPath = __DIR__ . '/csl_debug.log';
    file_put_contents($debugLogPath,
        date('[Y-m-d H:i:s]') . " URL: $cslWsUrl\n" .
        "IP: $ip\nTOKEN: " . substr($csl_token,0,40) . "...\n" .
        "PAYLOAD:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n",
        FILE_APPEND
    );

    $ch = curl_init($cslWsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "token: " . $csl_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Never follow redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents($debugLogPath,
        "HTTP: $httpCode\nRESPONSE:\n" . $response . "\nCURL_ERR:" . ($err?:'none') . "\n" .
        str_repeat('=',80) . "\n",
        FILE_APPEND
    );

    if ($err) {
        return ["status" => false, "message" => "cURL Error: " . $err];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);

        $getVal = function($arr, $keyName) {
            if (!is_array($arr)) return null;
            foreach ($arr as $k => $v) {
                if (trim((string)$k) === $keyName) return trim((string)$v);
            }
            return null;
        };

        $status = $getVal($decoded, 'status');

        if ($status && strtoupper($status) === 'SUCCESS') {
            $orderId = $getVal($decoded, 'order_id');
            db_execute(
                $conn,
                "UPDATE training_payment_requests SET gateway_order_id = ?, status = 'gateway_created', updated_at = NOW() WHERE id = ?",
                'si',
                [$orderId, $paymentRequest['id']]
            );
            return ["status" => true, "order_id" => $orderId, "provider" => "RAZORPAY"];
        }

        $errorMsg = 'Unknown error';
        $message  = $getVal($decoded, 'message');
        if ($message) {
            $errorMsg = $message;
        } elseif (strpos($response, 'IP not in trusted host list') !== false) {
            $errorMsg = 'CSL Firewall Blocked: IP not in trusted host list. Ask CSL to whitelist 52.172.135.84';
        } else {
            $cleanError = trim(strip_tags($response));
            if (!empty($cleanError)) {
                $errorMsg = 'Raw Response: ' . substr($cleanError, 0, 200);
            }
        }
        error_log('[CSL API ERROR] HTTP:' . $httpCode . ' Response: ' . $response);
        return ["status" => false, "message" => "API Error: " . $errorMsg, "raw" => $response];
    }

    return ["status" => false, "message" => "HTTP $httpCode: " . substr($response, 0, 200)];
}

function clms_verify_razorpay_signature($conn, $orderId, $paymentId, $signature) {
    $secret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
    $generatedSignature = hash_hmac('sha256', $orderId . "|" . $paymentId, $secret);
    return hash_equals($generatedSignature, $signature);
}
PHPEOF;

$ok1 = file_put_contents($cslFile, $cslContent);
echo ($ok1 !== false ? "✅ PATCHED" : "❌ FAILED (check permissions)") . ": include/payment_csl.php (" . strlen($cslContent) . " bytes)\n";

// =========================================================
// FILE 2: api/payments/create_training_order.php
// =========================================================
$orderFile    = $base . '/api/payments/create_training_order.php';
$orderContent = <<<'PHPEOF'
<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';
require_once __DIR__ . '/../../include/payment_csl.php';
require_once __DIR__ . '/../../include/AuditLogger.php';
header('Content-Type: application/json; charset=utf-8');

function paymentOrderJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) paymentOrderJson(['success' => false, 'message' => 'Invalid request payload.'], 400);

    $token   = trim((string)($input['token'] ?? ''));
    $request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
    if (!$request) paymentOrderJson(['success' => false, 'message' => 'Payment request not found.'], 404);

    if (in_array(strtolower((string)$request['status']), ['paid', 'verified'])) {
        paymentOrderJson(['success' => false, 'message' => 'Payment already completed.'], 400);
    }

    if (!empty($request['link_expires_at']) && strtotime($request['link_expires_at']) < time()) {
        paymentOrderJson(['success' => false, 'message' => 'Payment link has expired.'], 400);
    }

    if (!clms_payment_gateway_configured($conn)) {
        paymentOrderJson(['success' => false, 'message' => 'Payment gateway keys are not configured.', 'gateway_configured' => false], 400);
    }

    $provider = clms_payment_setting($conn, 'payment_gateway_provider', 'demo_qr');

    if ($provider === 'csl_payment') {
        $keyId     = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
        $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

        if ($keyId === '' || $keySecret === '') {
            paymentOrderJson(['success' => false, 'message' => 'Payment API credentials are not configured.'], 400);
        }

        $cslOrderResult = clms_csl_create_payment_order($conn, $request);

        if (!$cslOrderResult['status']) {
            paymentOrderJson(['success' => false, 'message' => $cslOrderResult['message']], 400);
        }

        $orderId    = $cslOrderResult['order_id'];
        $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);

        AuditLogger::log($conn, 'CSL_ORDER_CREATED', 'payment', '', [
            'payment_ref' => $request['payment_ref'],
            'order_id'   => $orderId,
            'amount'     => $request['total_amount']
        ], "Cochin Shipyard payment order created successfully.");

        paymentOrderJson([
            'success'          => true,
            'message'          => 'CSL order created.',
            'provider'         => 'razorpay',
            'key_id'           => $keyId,
            'gateway_order_id' => $orderId,
            'amount'           => $request['total_amount'],
            'currency'         => 'INR',
            'token'            => $token,
            'contractor_name'  => $contractor['contractor_name'] ?? ($contractor['vendor_name'] ?? 'Contractor'),
            'contractor_email' => $contractor['email'] ?? '',
            'contractor_phone' => $contractor['mobile'] ?? ($contractor['phone'] ?? '')
        ]);

    } elseif ($provider === 'razorpay') {
        $keyId     = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
        $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

        if ($keyId === '' || $keySecret === '') {
            paymentOrderJson(['success' => false, 'message' => 'Razorpay credentials not configured.'], 400);
        }

        $amountInPaise = round((float)$request['total_amount'] * 100);
        $ch = curl_init("https://api.razorpay.com/v1/orders");
        curl_setopt($ch, CURLOPT_USERPWD, "$keyId:$keySecret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['amount' => $amountInPaise, 'currency' => 'INR', 'receipt' => $request['payment_ref']]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            paymentOrderJson(['success' => false, 'message' => 'Gateway connection failed: ' . $curlError], 500);
        }

        $rzpOrder = json_decode($response, true);
        if ($httpCode !== 200 || empty($rzpOrder['id'])) {
            paymentOrderJson(['success' => false, 'message' => $rzpOrder['error']['description'] ?? 'Razorpay Order Creation Failed.'], 400);
        }

        $orderId = $rzpOrder['id'];
        db_execute($conn, "UPDATE training_payment_requests SET status='gateway_created', gateway_provider=?, gateway_order_id=?, updated_at=NOW() WHERE id=?", 'ssi', [$provider, $orderId, (int)$request['id']]);

        $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);

        AuditLogger::log($conn, 'RAZORPAY_ORDER_CREATED', 'payment', '', ['payment_ref' => $request['payment_ref'], 'order_id' => $orderId, 'amount' => $request['total_amount']], "Razorpay order created.");

        paymentOrderJson([
            'success'          => true,
            'message'          => 'Razorpay order created.',
            'provider'         => 'razorpay',
            'key_id'           => $keyId,
            'gateway_order_id' => $orderId,
            'amount'           => $request['total_amount'],
            'currency'         => 'INR',
            'token'            => $token,
            'contractor_name'  => $contractor['contractor_name'] ?? ($contractor['vendor_name'] ?? 'Contractor'),
            'contractor_email' => $contractor['email'] ?? '',
            'contractor_phone' => $contractor['mobile'] ?? ($contractor['phone'] ?? '')
        ]);

    } else {
        $orderId = 'LOCAL-' . $request['payment_ref'];
        db_execute($conn, "UPDATE training_payment_requests SET status='gateway_created', gateway_provider=?, gateway_order_id=?, updated_at=NOW() WHERE id=?", 'ssi', [$provider, $orderId, (int)$request['id']]);

        $payload = ['success' => true, 'message' => 'Gateway order created.', 'provider' => $provider, 'order_id' => $orderId, 'amount' => $request['total_amount'], 'currency' => $request['currency']];
        if ($provider === 'demo_qr') {
            $payload['checkout_mode'] = 'demo_qr';
            $payload['demo']          = clms_demo_payment_details($conn, $request);
        }
        paymentOrderJson($payload);
    }

} catch (Throwable $e) {
    error_log('[CREATE_TRAINING_ORDER] ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
    paymentOrderJson(['success' => false, 'message' => 'Payment order creation failed: ' . $e->getMessage()], 500);
}
PHPEOF;

$ok2 = file_put_contents($orderFile, $orderContent);
echo ($ok2 !== false ? "✅ PATCHED" : "❌ FAILED (check permissions)") . ": api/payments/create_training_order.php (" . strlen($orderContent) . " bytes)\n";

echo "\nDone. DELETE this file now!\n";
echo "Test: https://cslweb.teleconsystems.com/api/payments/create_training_order.php\n";
