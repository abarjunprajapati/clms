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

    $token = trim((string)($input['token'] ?? ''));
    $request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
    if (!$request) paymentOrderJson(['success' => false, 'message' => 'Payment request not found.'], 404);
    
    // Prevent double processing / duplicate orders
    if (strtolower((string)$request['status']) === 'paid' || strtolower((string)$request['status']) === 'verified') {
        paymentOrderJson(['success' => false, 'message' => 'Payment already completed.'], 400);
    }
    
    if (!empty($request['link_expires_at']) && strtotime($request['link_expires_at']) < time()) {
        paymentOrderJson(['success' => false, 'message' => 'Payment link has expired.'], 400);
    }
    
    if (!clms_payment_gateway_configured($conn)) {
        paymentOrderJson([
            'success' => false,
            'message' => 'Payment gateway keys are not configured yet. Please configure provider, key id and secret.',
            'gateway_configured' => false,
        ], 400);
    }

    $provider = clms_payment_setting($conn, 'payment_gateway_provider', 'demo_qr');
    
    if ($provider === 'csl_payment') {
        $keyId = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
        $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
        
        if ($keyId === '' || $keySecret === '') {
            paymentOrderJson(['success' => false, 'message' => 'Payment API credentials are not configured.'], 400);
        }

        $cslOrderResult = clms_csl_create_payment_order($conn, $request);

        if (!$cslOrderResult['status']) {
            paymentOrderJson(['success' => false, 'message' => $cslOrderResult['message']], 400);
        }

        $orderId = $cslOrderResult['order_id'];
        
        // Audit log
        AuditLogger::log($conn, 'CSL_ORDER_CREATED', 'payment', '', [
            'payment_ref' => $request['payment_ref'],
            'order_id' => $orderId,
            'amount' => $request['total_amount']
        ], "Cochin Shipyard payment order created successfully.");

        paymentOrderJson([
            'success' => true,
            'message' => 'CSL order created.',
            'provider' => 'razorpay', // Load Razorpay checkout in the frontend
            'key_id' => $keyId,
            'gateway_order_id' => $orderId,
            'amount' => $request['total_amount'],
            'currency' => 'INR',
            'token' => $token,
            'contractor_name' => $contractor['contractor_name'] ?? ($contractor['vendor_name'] ?? 'Contractor'),
            'contractor_email' => $contractor['email'] ?? '',
            'contractor_phone' => $contractor['mobile'] ?? ($contractor['phone'] ?? '')
        ]);

    } elseif ($provider === 'razorpay') {
        $keyId = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
        $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
        
        if ($keyId === '' || $keySecret === '') {
            paymentOrderJson(['success' => false, 'message' => 'Razorpay API credentials are not configured.'], 400);
        }
        
        // Call Razorpay Order API
        $amountInPaise = round((float)$request['total_amount'] * 100);
        
        $ch = curl_init("https://api.razorpay.com/v1/orders");
        curl_setopt($ch, CURLOPT_USERPWD, "$keyId:$keySecret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => $request['payment_ref']
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log('[CREATE_TRAINING_ORDER] cURL error: ' . $curlError);
            paymentOrderJson(['success' => false, 'message' => 'Payment gateway connection failed: ' . $curlError], 500);
        }
        
        $rzpOrder = json_decode($response, true);
        if ($httpCode !== 200 || empty($rzpOrder['id'])) {
            $errMessage = $rzpOrder['error']['description'] ?? 'Razorpay Order Creation Failed.';
            error_log('[CREATE_TRAINING_ORDER] Razorpay error: HTTP=' . $httpCode . ' resp=' . $response);
            paymentOrderJson(['success' => false, 'message' => $errMessage], 400);
        }
        
        $orderId = $rzpOrder['id'];
        
        db_execute(
            $conn,
            "UPDATE training_payment_requests
             SET status = 'gateway_created', gateway_provider = ?, gateway_order_id = ?, updated_at = NOW()
             WHERE id = ?",
            'ssi',
            [$provider, $orderId, (int)$request['id']]
        );
        
        $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);
        
        // Audit log
        AuditLogger::log($conn, 'RAZORPAY_ORDER_CREATED', 'payment', '', [
            'payment_ref' => $request['payment_ref'],
            'order_id' => $orderId,
            'amount' => $request['total_amount']
        ], "Razorpay order created successfully.");

        paymentOrderJson([
            'success' => true,
            'message' => 'Razorpay order created.',
            'provider' => 'razorpay',
            'key_id' => $keyId,
            'gateway_order_id' => $orderId,
            'amount' => $request['total_amount'],
            'currency' => 'INR',
            'token' => $token,
            'contractor_name' => $contractor['contractor_name'] ?? ($contractor['vendor_name'] ?? 'Contractor'),
            'contractor_email' => $contractor['email'] ?? '',
            'contractor_phone' => $contractor['mobile'] ?? ($contractor['phone'] ?? '')
        ]);
        
    } else {
        // Fallback or demo_qr provider
        $orderId = 'LOCAL-' . $request['payment_ref'];
        db_execute(
            $conn,
            "UPDATE training_payment_requests
             SET status = 'gateway_created', gateway_provider = ?, gateway_order_id = ?, updated_at = NOW()
             WHERE id = ?",
            'ssi',
            [$provider, $orderId, (int)$request['id']]
        );

        $payload = [
            'success' => true,
            'message' => 'Gateway order created.',
            'provider' => $provider,
            'order_id' => $orderId,
            'amount' => $request['total_amount'],
            'currency' => $request['currency'],
        ];
        if ($provider === 'demo_qr') {
            $payload['checkout_mode'] = 'demo_qr';
            $payload['demo'] = clms_demo_payment_details($conn, $request);
        }
        paymentOrderJson($payload);
    }
    
} catch (Throwable $e) {
    error_log('[CREATE_TRAINING_ORDER] ' . $e->getMessage());
    paymentOrderJson(['success' => false, 'message' => 'Payment order creation failed.'], 500);
}
