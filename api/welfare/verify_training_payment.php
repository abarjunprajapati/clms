<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

header('Content-Type: application/json; charset=utf-8');

function verifyPaymentJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) verifyPaymentJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    $paymentId = (int)($input['payment_id'] ?? 0);
    $action = trim((string)($input['action'] ?? ''));
    if (!$paymentId || !in_array($action, ['approve', 'reject'], true)) {
        verifyPaymentJson(['success' => false, 'message' => 'Invalid payment action.'], 400);
    }
    clms_verify_demo_training_payment(
        $conn,
        $paymentId,
        $action === 'approve',
        $input['remarks'] ?? '',
        (int)($_SESSION['user_id'] ?? 0)
    );
    verifyPaymentJson(['success' => true, 'message' => $action === 'approve' ? 'Payment verified.' : 'Payment rejected and link reopened.']);
} catch (Throwable $e) {
    error_log('[VERIFY_TRAINING_PAYMENT] ' . $e->getMessage());
    verifyPaymentJson(['success' => false, 'message' => 'Payment verification failed.'], 500);
}
