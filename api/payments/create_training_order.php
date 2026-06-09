<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

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
    if (strtolower((string)$request['status']) === 'paid') paymentOrderJson(['success' => false, 'message' => 'Payment already completed.'], 400);
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
} catch (Throwable $e) {
    error_log('[CREATE_TRAINING_ORDER] ' . $e->getMessage());
    paymentOrderJson(['success' => false, 'message' => 'Payment order creation failed.'], 500);
}
