<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

function selectedPaymentJson($payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        selectedPaymentJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        selectedPaymentJson(['success' => false, 'message' => 'Session expired. Please login again.'], 401);
    }

    $contractor = clms_get_current_contractor_for_payment(
        $conn,
        $userId,
        $_SESSION['contractor_id'] ?? ($_SESSION['vendor_code'] ?? '')
    );
    if (!$contractor) {
        selectedPaymentJson(['success' => false, 'message' => 'Contractor record not found.'], 404);
    }

    $contractorId = (int)$contractor['id'];
    $workerIds = $input['worker_ids'] ?? [];
    if (!is_array($workerIds)) $workerIds = [];
    $workerIds = array_values(array_unique(array_filter(array_map('intval', $workerIds))));
    if (!$workerIds) {
        selectedPaymentJson(['success' => false, 'message' => 'Please select at least one pending worker.'], 400);
    }

    $pendingRows = clms_pending_safety_fee_workers($conn, $contractorId);
    $allowed = [];
    foreach ($pendingRows as $row) {
        $allowed[(int)$row['id']] = true;
    }

    $invalid = array_filter($workerIds, function($workerId) use ($allowed) {
        return !isset($allowed[(int)$workerId]);
    });
    if ($invalid) {
        selectedPaymentJson(['success' => false, 'message' => 'Selected worker list contains workers without pending safety fee.'], 400);
    }

    $request = clms_create_selected_training_payment_request($conn, $contractorId, $workerIds, $userId, 'contractor_pending_payment');
    if (!$request) {
        selectedPaymentJson(['success' => false, 'message' => 'Payment request generate nahi ho pa raha. Please retry or check payment settings.'], 500);
    }

    selectedPaymentJson([
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
    selectedPaymentJson(['success' => false, 'message' => 'Payment request failed: ' . $e->getMessage()], 500);
}
