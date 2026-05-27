<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

try {
    $sql = "SELECT a.*, w.name as worker_name, v.vendor_name as contractor_name
            FROM attendance a
            JOIN workers w ON a.worker_id = w.id
            JOIN sap_vendor_master v ON a.contractor_id = v.vendor_code
            WHERE a.contractor_id IN (SELECT vendor_code FROM work_orders WHERE customer_code = ? AND wo_status = 'ACTIVE')
            ORDER BY a.attendance_date DESC, a.punch_in DESC
            LIMIT 100";
    
    $attendance = db_fetch_all($conn, $sql, 's', [$customer_code]);

    apiSuccess($attendance);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
