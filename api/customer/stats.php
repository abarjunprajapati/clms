<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

try {
    // 1. Total Mapped Contractors
    $contractor_count = db_count($conn, "SELECT COUNT(DISTINCT vendor_code) FROM work_orders WHERE customer_code = ? AND wo_status = 'ACTIVE'", 's', [$customer_code]);

    // 2. Total Workers under these contractors
    $worker_count = db_count($conn, "
        SELECT COUNT(DISTINCT w.id)
        FROM workmen w
        JOIN contractors c ON c.id = w.contractor_id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
    ", 's', [$customer_code]);

    // 3. Present Today (Attendance)
    $today = date('Y-m-d');
    $present_count = db_count($conn, "
        SELECT COUNT(DISTINCT a.workman_id)
        FROM attendance a
        JOIN workmen w ON w.id = a.workman_id
        JOIN contractors c ON c.id = w.contractor_id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        WHERE DATE(a.check_in) = ? AND wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
    ", 'ss', [$today, $customer_code]);

    // 4. Active Passes
    $active_passes = db_count($conn, "
        SELECT COUNT(DISTINCT gp.id)
        FROM gate_passes gp
        JOIN workmen w ON w.id = gp.workman_id
        JOIN contractors c ON c.id = w.contractor_id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        WHERE gp.status IN ('approved','active') AND wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
    ", 's', [$customer_code]);

    apiSuccess([
        'contractors' => $contractor_count,
        'workers' => $worker_count,
        'present_today' => $present_count,
        'active_passes' => $active_passes
    ]);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
