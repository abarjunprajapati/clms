<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

header('Content-Type: application/json; charset=utf-8');

function demoPaymentJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) demoPaymentJson(['success' => false, 'message' => 'Invalid request payload.'], 400);

    $token = trim((string)($input['token'] ?? ''));
    $request = $token !== '' ? clms_get_training_payment_request($conn, $token) : null;
    if (!$request) demoPaymentJson(['success' => false, 'message' => 'Payment request not found.'], 404);
    if (strtolower((string)$request['status']) === 'paid') demoPaymentJson(['success' => false, 'message' => 'Payment already completed.'], 400);
    if (!empty($request['link_expires_at']) && strtotime($request['link_expires_at']) < time()) {
        demoPaymentJson(['success' => false, 'message' => 'Payment link has expired.'], 400);
    }

    clms_submit_demo_training_payment(
        $conn,
        (int)$request['id'],
        $input['payer_reference'] ?? '',
        $input['note'] ?? ''
    );

    demoPaymentJson([
        'success' => true,
        'message' => 'Safety fee payment successful. Please proceed to Safety Training & Seat Booking.',
    ]);
} catch (InvalidArgumentException $e) {
    demoPaymentJson(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('[SUBMIT_DEMO_PAYMENT] ' . $e->getMessage());
    demoPaymentJson(['success' => false, 'message' => 'Payment submission failed.'], 500);
}
