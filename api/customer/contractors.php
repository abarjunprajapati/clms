<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

try {
    $sql = "SELECT wo.vendor_code, wo.work_order_no, wo.project_name, wo.department, 
                   v.vendor_name, v.vendor_name as contractor_name, v.email_address as email, v.vendor_mob1 as mobile,
                   COALESCE(c.status, 'pending') as registration_status,
                   COALESCE(a.status, 'not_submitted') as annexure3a_status,
                   a.id as annexure3a_id,
                   (SELECT COUNT(*) FROM workmen w WHERE w.contractor_id = c.id) as total_workers
            FROM work_orders wo
            JOIN sap_vendor_master v ON wo.vendor_code = v.vendor_code
            LEFT JOIN contractors c ON wo.vendor_code = c.vendor_code
            LEFT JOIN contractor_annexure3a a ON a.work_order_no = wo.work_order_no AND a.customer_code = wo.customer_code
            WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'";
    
    $contractors = db_fetch_all($conn, $sql, 's', [$customer_code]);

    apiSuccess($contractors);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
