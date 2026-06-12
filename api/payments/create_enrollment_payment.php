<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

function enrollmentPaymentJson($payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Session expired. Please login again.'], 401);
    }

    $contractor = clms_get_current_contractor_for_payment(
        $conn,
        $userId,
        $_SESSION['contractor_id'] ?? ($_SESSION['vendor_code'] ?? '')
    );
    if (!$contractor) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Contractor record not found.'], 404);
    }

    $workerId = (int)($input['worker_id'] ?? 0);
    if (!$workerId) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Worker draft is required.'], 400);
    }

    $worker = db_single(
        $conn,
        "SELECT id, contractor_id, work_order_no, work_order_source
         FROM workmen
         WHERE id = ? AND contractor_id = ?
         LIMIT 1",
        'ii',
        [$workerId, (int)$contractor['id']]
    );
    if (!$worker) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Worker draft not found.'], 404);
    }

    $workOrderSource = strtoupper(trim((string)($worker['work_order_source'] ?? '')));
    $workOrderNo = strtoupper(trim((string)($worker['work_order_no'] ?? '')));
    if ($workOrderSource !== 'PWO' && strpos($workOrderNo, 'PWO') !== 0) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Safety fee payment is applicable only for PWO workers.'], 400);
    }

    $paidWorkerIds = clms_paid_training_worker_ids($conn, [$workerId]);
    if (in_array($workerId, $paidWorkerIds, true)) {
        enrollmentPaymentJson([
            'success' => true,
            'message' => 'Safety fee payment is already completed.',
            'already_paid' => true,
        ]);
    }

    $request = db_single(
        $conn,
        "SELECT pr.*
         FROM training_payment_requests pr
         JOIN training_payment_request_workers pw ON pw.payment_request_id = pr.id
         WHERE pr.contractor_id = ?
           AND pw.workman_id = ?
           AND pr.status IN ('pending','link_sent','gateway_created','submitted')
         ORDER BY pr.id DESC
         LIMIT 1",
        'ii',
        [(int)$contractor['id'], $workerId]
    );

    if (!$request) {
        $request = clms_create_training_payment_request(
            $conn,
            (int)$contractor['id'],
            [$workerId],
            $userId,
            'enrolment_draft'
        );
    }
    if (!$request) {
        enrollmentPaymentJson(['success' => false, 'message' => 'Payment request generate nahi ho pa raha. Please retry.'], 500);
    }

    enrollmentPaymentJson([
        'success' => true,
        'message' => 'Payment request generated.',
        'payment' => [
            'payment_ref' => $request['payment_ref'],
            'payment_token' => $request['payment_token'],
            'payment_link' => $request['payment_link'],
            'worker_count' => (int)$request['worker_count'],
            'amount' => (float)$request['total_amount'],
        ],
    ]);
} catch (Throwable $e) {
    error_log('[CREATE_ENROLLMENT_PAYMENT] ' . $e->getMessage());
    enrollmentPaymentJson(['success' => false, 'message' => 'Payment request failed.'], 500);
}
