<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';
require_once __DIR__ . '/../../include/AuditLogger.php';

header('Content-Type: application/json; charset=utf-8');

function webhookJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rawPayload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

    if ($rawPayload === '') {
        webhookJson(['success' => false, 'message' => 'Empty payload.'], 400);
    }

    $webhookSecret = trim((string)clms_payment_setting($conn, 'payment_gateway_webhook_secret', ''));

    // Validate signature if webhook secret is configured
    if ($webhookSecret !== '') {
        if ($signature === '') {
            webhookJson(['success' => false, 'message' => 'Missing Razorpay webhook signature.'], 400);
        }
        $expectedSignature = hash_hmac('sha256', $rawPayload, $webhookSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            AuditLogger::log($conn, 'WEBHOOK_SIGNATURE_FAILED', 'payment', '', '', "Invalid signature from Razorpay webhook call.");
            webhookJson(['success' => false, 'message' => 'Invalid webhook signature.'], 400);
        }
    }

    $eventData = json_decode($rawPayload, true);
    if (!is_array($eventData)) {
        webhookJson(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
    }

    $event = $eventData['event'] ?? '';
    
    // Support payment.captured and order.paid
    if (in_array($event, ['payment.captured', 'order.paid'], true)) {
        $rzpOrderId = '';
        $rzpPaymentId = '';
        
        if ($event === 'order.paid') {
            $orderEntity = $eventData['payload']['order']['entity'] ?? [];
            $rzpOrderId = $orderEntity['id'] ?? '';
            $payments = $orderEntity['payments'] ?? [];
            if ($payments && is_array($payments)) {
                // Find captured payment if available
                foreach ($payments as $p) {
                    if (isset($p['status']) && strtolower($p['status']) === 'captured') {
                        $rzpPaymentId = $p['id'];
                        break;
                    }
                }
            }
        } else {
            $paymentEntity = $eventData['payload']['payment']['entity'] ?? [];
            $rzpOrderId = $paymentEntity['order_id'] ?? '';
            $rzpPaymentId = $paymentEntity['id'] ?? '';
        }

        if ($rzpOrderId === '') {
            webhookJson(['success' => false, 'message' => 'No order ID in webhook payload.'], 400);
        }

        // Find the training payment request by gateway_order_id
        $request = db_single(
            $conn,
            "SELECT * FROM training_payment_requests WHERE gateway_order_id = ? LIMIT 1",
            's',
            [$rzpOrderId]
        );

        if ($request) {
            $status = strtolower(trim((string)($request['status'] ?? '')));
            if ($status !== 'paid' && $status !== 'verified') {
                clms_mark_training_payment_paid($conn, (int)$request['id'], $rzpPaymentId, $rzpOrderId);
                
                db_execute(
                    $conn,
                    "UPDATE training_payment_requests 
                     SET payment_signature = ?, payment_verified_at = NOW() 
                     WHERE id = ?",
                    'si',
                    [$signature !== '' ? $signature : 'WEBHOOK_VERIFIED', (int)$request['id']]
                );

                AuditLogger::log($conn, 'WEBHOOK_PAYMENT_PROCESSED', 'payment', $status, 'paid', "Webhook event $event processed successfully. Payment marked paid.");
            }
            webhookJson(['success' => true, 'message' => 'Webhook processed successfully.']);
        } else {
            webhookJson(['success' => false, 'message' => 'Order not found in database.'], 404);
        }
    }

    // Acknowledge other event types with success
    webhookJson(['success' => true, 'message' => 'Event ignored.']);

} catch (Throwable $e) {
    error_log('[RAZORPAY_WEBHOOK] ' . $e->getMessage());
    webhookJson(['success' => false, 'message' => 'Webhook execution failed.'], 500);
}
