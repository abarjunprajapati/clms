<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$status = strtolower(trim($_POST['status'] ?? ''));
$remarks = trim($_POST['remarks'] ?? '');
$allowed = ['approved', 'rejected', 'resubmit_required', 'correction_required', 'pending'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    apiError('Invalid Annexure 3A status request.', 422);
}

if (in_array($status, ['rejected', 'resubmit_required', 'correction_required'], true) && $remarks === '') {
    apiError('Remarks are required for reject or correction action.', 422);
}

try {
    $record = db_single(
        $conn,
        "SELECT id, vendor_code, customer_code, work_order_no FROM contractor_annexure3a WHERE id = ? AND customer_code = ?",
        'is',
        [$id, $customer_code]
    );

    if (!$record) {
        apiError('Annexure 3A request not found for this customer.', 404);
    }

    $dbStatus = $status === 'resubmit_required' ? 'correction_required' : $status;
    $updated = db_execute(
        $conn,
        "UPDATE contractor_annexure3a SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ? AND customer_code = ?",
        'ssis',
        [$dbStatus, $remarks, $id, $customer_code]
    );

    if (!$updated) {
        apiError('Unable to update Annexure 3A status.', 500);
    }

    db_execute(
        $conn,
        "INSERT INTO contractor_annexure3a_history (annexure3a_id, vendor_code, customer_code, work_order_no, status, reason)
         VALUES (?, ?, ?, ?, ?, ?)",
        'isssss',
        [$id, $record['vendor_code'], $customer_code, $record['work_order_no'], $dbStatus, $remarks ?: 'Customer status update']
    );

    if ($dbStatus === 'approved') {
        db_execute($conn, "UPDATE contractor_documents SET status='verified', updated_at=NOW() WHERE annexure3a_id = ?", 'i', [$id]);
    } elseif ($dbStatus === 'rejected') {
        db_execute($conn, "UPDATE contractor_documents SET status='rejected', updated_at=NOW() WHERE annexure3a_id = ?", 'i', [$id]);
    }

    apiSuccess(['status' => $dbStatus], 'Annexure 3A status updated.');
} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
