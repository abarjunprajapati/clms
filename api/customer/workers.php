<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

try {
    $sql = "SELECT w.id, w.name, 'Active' as status, v.vendor_name as contractor_name 
            FROM workmen w
            JOIN contractors c ON c.id = w.contractor_id
            JOIN work_orders wo ON wo.vendor_code = c.vendor_code
            LEFT JOIN sap_vendor_master v ON v.vendor_code = c.vendor_code
            WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
            ORDER BY w.id DESC";
    
    $workers = db_fetch_all($conn, $sql, 's', [$customer_code]);

    apiSuccess($workers);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
