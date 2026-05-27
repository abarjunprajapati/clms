<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

try {
    $sql = "SELECT gp.*, w.name as worker_name, v.vendor_name as contractor_name
            FROM gate_passes gp
            JOIN workers w ON gp.worker_id = w.id
            JOIN sap_vendor_master v ON gp.contractor_id = v.vendor_code
            WHERE gp.contractor_id IN (SELECT vendor_code FROM work_orders WHERE customer_code = ? AND wo_status = 'ACTIVE')
            ORDER BY gp.created_at DESC";
    
    $passes = db_fetch_all($conn, $sql, 's', [$customer_code]);

    apiSuccess($passes);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
