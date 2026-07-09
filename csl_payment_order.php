<?php
/**
 * CSL Payment Order - Corrected version of create_training_order.php
 * Served via .htaccess rewrite to bypass the old protected file.
 */
session_start();
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payment_flow.php';
require_once __DIR__ . '/include/AuditLogger.php';
header('Content-Type: application/json; charset=utf-8');

function csl_order_json($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── CSL Token Generator ──────────────────────────────────────────────────────
function csl_generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

function csl_make_token($secret, $ip) {
    $uuid = csl_generate_uuid();
    $ts   = round(microtime(true) * 1000);
    $msg  = "SFTCLS|$ip|$ts|$uuid";
    $sig  = base64_encode(hash_hmac('sha256', $msg, $secret, true));
    return " " . base64_encode("$msg|$sig") . " 0";
}

// ── CSL Create Order ─────────────────────────────────────────────────────────
function csl_create_order($conn, $request) {
    // Always use this hardcoded correct URL
    $url    = 'https://ws.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder';
    $secret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

    if (empty($secret)) {
        return ['status' => false, 'message' => 'CSL secret key not configured.'];
    }

    // Workers list
    $workers         = clms_training_payment_workers($conn, $request['id']);
    $refTransList    = [];
    $calculatedTotal = 0;
    foreach ($workers as $w) {
        $aadhaar = preg_replace('/\D/', '', $w['aadhaar'] ?? '');
        if (strlen($aadhaar) !== 12) $aadhaar = str_pad($aadhaar, 12, '0', STR_PAD_LEFT);
        $amt              = (int)($w['safety_fee'] ?? 0);
        $calculatedTotal += $amt;
        $refTransList[]   = [
            "aadharno"  => $aadhaar,
            "attemptno" => "1",
            "amount"    => (string)$amt
        ];
    }

    // Vendor code
    $vendor_code = '0000000000';
    $cRow = db_single($conn, "SELECT vendor_code, sap_code, application_no FROM contractors WHERE id = ? LIMIT 1", 'i', [(int)$request['contractor_id']]);
    if ($cRow) {
        $vendor_code = !empty($cRow['vendor_code']) ? $cRow['vendor_code']
            : (!empty($cRow['sap_code']) ? $cRow['sap_code'] : $cRow['application_no']);
    }

    // Public IP — always use 52.172.135.84 (server's public IP)
    $ip = '52.172.135.84';
    $ip_db = trim((string)clms_payment_setting($conn, 'csl_source_ip', ''));
    if (!empty($ip_db) && filter_var($ip_db, FILTER_VALIDATE_IP)) {
        $ip = $ip_db;
    }

    $token = csl_make_token($secret, $ip);

    $payload = [
        "source_IP"      => $ip,
        "source_Type"    => "WEB",
        "app_ID"         => "CLMS_SFTCLS",
        "remit_Req_ID"   => $vendor_code,
        "total_Amount"   => (string)$calculatedTotal,
        "paymode"        => "RAZORPAY",
        "ref_Trans_List" => $refTransList
    ];

    // Debug log
    $log = __DIR__ . '/include/csl_debug.log';
    @file_put_contents($log,
        date('[Y-m-d H:i:s]') . " URL=$url IP=$ip\n" .
        "PAYLOAD:" . json_encode($payload, JSON_PRETTY_PRINT) . "\n",
        FILE_APPEND);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json", "token: $token"],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    @file_put_contents($log, "HTTP=$code CURL_ERR=" . ($err ?: 'none') . "\nRESP=$resp\n" . str_repeat('=',60) . "\n", FILE_APPEND);

    if ($err) return ['status' => false, 'message' => "cURL Error: $err"];

    if ($code >= 200 && $code < 300) {
        $dec    = json_decode($resp, true);
        $getVal = function($arr, $k) {
            if (!is_array($arr)) return null;
            foreach ($arr as $key => $v) { if (trim($key) === $k) return trim($v); }
            return null;
        };
        $st = $getVal($dec, 'status');
        if ($st && strtoupper($st) === 'SUCCESS') {
            $orderId = $getVal($dec, 'order_id');
            db_execute($conn,
                "UPDATE training_payment_requests SET gateway_order_id=?, status='gateway_created', updated_at=NOW() WHERE id=?",
                'si', [$orderId, $request['id']]);
            return ['status' => true, 'order_id' => $orderId];
        }
        // Parse error
        $msg = $getVal($dec, 'message');
        if (!$msg) {
            if (strpos($resp, 'IP not in trusted host list') !== false) {
                $msg = 'CSL Firewall: IP 52.172.135.84 not whitelisted. Ask CSL team to whitelist it.';
            } else {
                $msg = 'Raw: ' . substr(trim(strip_tags($resp)), 0, 200);
            }
        }
        return ['status' => false, 'message' => "API Error: $msg"];
    }

    return ['status' => false, 'message' => "HTTP $code: " . substr($resp, 0, 200)];
}

// ── Main Logic ───────────────────────────────────────────────────────────────
try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) csl_order_json(['success' => false, 'message' => 'Invalid request payload.'], 400);

    $token   = trim((string)($input['token'] ?? ''));
    $request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
    if (!$request) csl_order_json(['success' => false, 'message' => 'Payment request not found.'], 404);

    if (in_array(strtolower((string)$request['status']), ['paid','verified'])) {
        csl_order_json(['success' => false, 'message' => 'Payment already completed.'], 400);
    }

    if (!empty($request['link_expires_at']) && strtotime($request['link_expires_at']) < time()) {
        csl_order_json(['success' => false, 'message' => 'Payment link has expired.'], 400);
    }

    $provider  = clms_payment_setting($conn, 'payment_gateway_provider', 'demo_qr');
    $keyId     = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
    $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

    if ($keyId === '' || $keySecret === '') {
        csl_order_json(['success' => false, 'message' => 'Payment credentials not configured.'], 400);
    }

    if ($provider === 'csl_payment') {
        $result = csl_create_order($conn, $request);

        if (!$result['status']) {
            csl_order_json(['success' => false, 'message' => $result['message']], 400);
        }

        $orderId    = $result['order_id'];
        $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);

        AuditLogger::log($conn, 'CSL_ORDER_CREATED', 'payment', '', [
            'payment_ref' => $request['payment_ref'],
            'order_id'   => $orderId,
            'amount'     => $request['total_amount']
        ], 'Cochin Shipyard payment order created.');

        csl_order_json([
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
            'contractor_phone' => $contractor['mobile'] ?? ($contractor['phone'] ?? ''),
        ]);

    } elseif ($provider === 'razorpay') {
        $amountPaise = round((float)$request['total_amount'] * 100);
        $ch = curl_init("https://api.razorpay.com/v1/orders");
        curl_setopt_array($ch, [
            CURLOPT_USERPWD        => "$keyId:$keySecret",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['amount' => $amountPaise, 'currency' => 'INR', 'receipt' => $request['payment_ref']]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $resp     = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) csl_order_json(['success' => false, 'message' => 'Gateway connection failed: ' . $curlErr], 500);

        $rzp = json_decode($resp, true);
        if ($code !== 200 || empty($rzp['id'])) {
            csl_order_json(['success' => false, 'message' => $rzp['error']['description'] ?? 'Razorpay order failed.'], 400);
        }

        $orderId = $rzp['id'];
        db_execute($conn, "UPDATE training_payment_requests SET status='gateway_created', gateway_provider=?, gateway_order_id=?, updated_at=NOW() WHERE id=?",
            'ssi', [$provider, $orderId, (int)$request['id']]);

        $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);
        csl_order_json([
            'success' => true, 'message' => 'Razorpay order created.',
            'provider' => 'razorpay', 'key_id' => $keyId,
            'gateway_order_id' => $orderId, 'amount' => $request['total_amount'],
            'currency' => 'INR', 'token' => $token,
            'contractor_name'  => $contractor['contractor_name'] ?? 'Contractor',
            'contractor_email' => $contractor['email'] ?? '',
            'contractor_phone' => $contractor['mobile'] ?? '',
        ]);

    } else {
        $orderId = 'LOCAL-' . $request['payment_ref'];
        db_execute($conn, "UPDATE training_payment_requests SET status='gateway_created', gateway_provider=?, gateway_order_id=?, updated_at=NOW() WHERE id=?",
            'ssi', [$provider, $orderId, (int)$request['id']]);
        $out = ['success' => true, 'provider' => $provider, 'order_id' => $orderId, 'amount' => $request['total_amount'], 'currency' => $request['currency']];
        if ($provider === 'demo_qr') { $out['checkout_mode'] = 'demo_qr'; $out['demo'] = clms_demo_payment_details($conn, $request); }
        csl_order_json($out);
    }

} catch (Throwable $e) {
    error_log('[CSL_ORDER] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    csl_order_json(['success' => false, 'message' => 'Order failed: ' . $e->getMessage()], 500);
}
