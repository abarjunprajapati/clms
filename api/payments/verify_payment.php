<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';
require_once __DIR__ . '/../../include/AuditLogger.php';

header('Content-Type: application/json; charset=utf-8');

function paymentVerifyJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) paymentVerifyJson(['success' => false, 'message' => 'Invalid payload.'], 400);

    $token = trim((string)($input['token'] ?? ''));
    $paymentId = trim((string)($input['razorpay_payment_id'] ?? ''));
    $orderId = trim((string)($input['razorpay_order_id'] ?? ''));
    $signature = trim((string)($input['razorpay_signature'] ?? ''));

    if (!$token || !$paymentId || !$orderId || !$signature) {
        paymentVerifyJson(['success' => false, 'message' => 'Missing verification parameters.'], 400);
    }

    $request = clms_get_training_payment_request($conn, $token);
    if (!$request) paymentVerifyJson(['success' => false, 'message' => 'Payment request not found.'], 404);

    if (strtolower((string)$request['status']) === 'paid' || strtolower((string)$request['status']) === 'verified') {
        paymentVerifyJson(['success' => true, 'message' => 'Payment already processed.']);
    }

    if ($request['gateway_order_id'] !== $orderId) {
        paymentVerifyJson(['success' => false, 'message' => 'Order ID mismatch.'], 400);
    }

    $secret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
    $generatedSignature = hash_hmac('sha256', $orderId . "|" . $paymentId, $secret);

    if (!hash_equals($generatedSignature, $signature)) {
        AuditLogger::log($conn, 'PAYMENT_VERIFY_FAILED', 'payment', '', [
            'payment_ref' => $request['payment_ref'],
            'order_id' => $orderId,
            'payment_id' => $paymentId
        ], "Razorpay signature verification failed.");
        paymentVerifyJson(['success' => false, 'message' => 'Invalid payment signature.'], 400);
    }

    // Process payment atomically
    mysqli_begin_transaction($conn);
    try {
        // Re-check status inside transaction using lock
        $lockedRequest = db_single($conn, "SELECT id, status, payment_ref FROM training_payment_requests WHERE id = ? FOR UPDATE", 'i', [(int)$request['id']]);
        if (!$lockedRequest || in_array(strtolower((string)$lockedRequest['status']), ['paid', 'verified'])) {
            mysqli_commit($conn);
            paymentVerifyJson(['success' => true, 'message' => 'Payment already processed.']);
        }

        // 1. Update Payment Request
        db_execute(
            $conn,
            "UPDATE training_payment_requests
             SET status = 'paid', gateway_payment_id = ?, payer_reference = ?, paid_at = NOW(), updated_at = NOW()
             WHERE id = ?",
            'ssi',
            [$paymentId, $paymentId, (int)$request['id']]
        );

        // 2. Fetch workers involved in this payment
        $workers = clms_training_payment_workers($conn, (int)$request['id']);
        
        // 3. Update Workers and Training Requests
        foreach ($workers as $w) {
            $workerId = (int)$w['id'];
            // Mark safety fee as paid
            db_execute(
                $conn,
                "UPDATE workmen SET execution_training_status = 'pending_training', safety_fee_payment_option = 'paid', updated_at = NOW() WHERE id = ?",
                'i',
                [$workerId]
            );
            
            // If they already have a training request, update it
            if (!empty($w['training_request_id'])) {
                db_execute(
                    $conn,
                    "UPDATE training_requests SET payment_status = 'paid', payment_ref = ?, updated_at = NOW() WHERE id = ?",
                    'si',
                    [$request['payment_ref'], (int)$w['training_request_id']]
                );
            }
        }

        mysqli_commit($conn);
        
        AuditLogger::log($conn, 'PAYMENT_VERIFIED_SUCCESS', 'payment', '', [
            'payment_ref' => $request['payment_ref'],
            'order_id' => $orderId,
            'payment_id' => $paymentId
        ], "Payment successfully verified and records updated.");

        paymentVerifyJson([
            'success' => true, 
            'message' => 'Payment successful and verified.',
            'redirect' => 'contractor/dashboard.php?payment_success=1'
        ]);

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('[PAYMENT_VERIFY] Transaction error: ' . $e->getMessage());
        paymentVerifyJson(['success' => false, 'message' => 'Internal error during payment update.'], 500);
    }

} catch (Throwable $e) {
    error_log('[PAYMENT_VERIFY] ' . $e->getMessage());
    paymentVerifyJson(['success' => false, 'message' => 'Verification failed.'], 500);
}
