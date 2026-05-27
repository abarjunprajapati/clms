<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

try {
    // Safety status usually comes from workmen/workers table (safety_status column)
    $sql = "SELECT w.name as worker_name, w.safety_status, v.vendor_name as contractor_name
            FROM workers w
            JOIN sap_vendor_master v ON w.contractor_id = v.vendor_code
            WHERE w.contractor_id IN (SELECT vendor_code FROM work_orders WHERE customer_code = ? AND wo_status = 'ACTIVE')
            ORDER BY w.name ASC";
    
    $safety = db_fetch_all($conn, $sql, 's', [$customer_code]);

    apiSuccess($safety);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
