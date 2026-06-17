<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';
require_once __DIR__ . '/../../include/AuditLogger.php';

header('Content-Type: application/json; charset=utf-8');

function verifyPaymentJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) verifyPaymentJson(['success' => false, 'message' => 'Invalid request payload.'], 400);

    $token = trim((string)($input['token'] ?? ''));
    $razorpayPaymentId = trim((string)($input['razorpay_payment_id'] ?? ''));
    $razorpayOrderId = trim((string)($input['razorpay_order_id'] ?? ''));
    $razorpaySignature = trim((string)($input['razorpay_signature'] ?? ''));

    if ($token === '' || $razorpayPaymentId === '' || $razorpayOrderId === '' || $razorpaySignature === '') {
        verifyPaymentJson(['success' => false, 'message' => 'Missing payment validation parameters.'], 400);
    }

    $request = clms_get_training_payment_request($conn, $token);
    if (!$request) verifyPaymentJson(['success' => false, 'message' => 'Payment request not found.'], 404);

    // Prevent duplicate processing
    $status = strtolower(trim((string)($request['status'] ?? '')));
    if ($status === 'paid' || $status === 'verified') {
        verifyPaymentJson(['success' => true, 'message' => 'Payment already verified and marked paid.']);
    }

    $keyId = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
    $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

    if ($keyId === '' || $keySecret === '') {
        verifyPaymentJson(['success' => false, 'message' => 'Payment settings not configured.'], 400);
    }

    // 1. Signature Verification
    $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $keySecret);
    if (!hash_equals($expectedSignature, $razorpaySignature)) {
        AuditLogger::log($conn, 'PAYMENT_SIGNATURE_FAILED', 'payment', $request['status'], 'failed', "Failed signature check for order $razorpayOrderId");
        verifyPaymentJson(['success' => false, 'message' => 'Invalid payment signature. Verification failed.'], 400);
    }

    // 2. Fetch status from Razorpay API to confirm capture
    $ch = curl_init("https://api.razorpay.com/v1/payments/" . urlencode($razorpayPaymentId));
    curl_setopt($ch, CURLOPT_USERPWD, "$keyId:$keySecret");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $paymentDetails = json_decode($response, true);
    if ($httpCode !== 200 || empty($paymentDetails['status'])) {
        verifyPaymentJson(['success' => false, 'message' => 'Unable to verify payment status with gateway.'], 400);
    }

    $gatewayStatus = strtolower((string)$paymentDetails['status']);
    if ($gatewayStatus !== 'captured') {
        AuditLogger::log($conn, 'PAYMENT_NOT_CAPTURED', 'payment', $request['status'], $gatewayStatus, "Payment status is not captured: $gatewayStatus");
        verifyPaymentJson(['success' => false, 'message' => 'Payment has not been captured. Status: ' . $gatewayStatus], 400);
    }

    // 3. Update database
    clms_mark_training_payment_paid($conn, (int)$request['id'], $razorpayPaymentId, $razorpayOrderId);
    
    db_execute(
        $conn,
        "UPDATE training_payment_requests 
         SET payment_signature = ?, payment_verified_at = NOW() 
         WHERE id = ?",
        'si',
        [$razorpaySignature, (int)$request['id']]
    );

    AuditLogger::log($conn, 'PAYMENT_VERIFIED', 'payment', $status, 'paid', "Payment verified for order $razorpayOrderId");

    verifyPaymentJson([
        'success' => true,
        'message' => 'Payment verified successfully.'
    ]);

} catch (Throwable $e) {
    error_log('[VERIFY_RAZORPAY_PAYMENT] ' . $e->getMessage());
    verifyPaymentJson(['success' => false, 'message' => 'Payment verification failed on server.'], 500);
}
