<?php
/**
 * Get Sales Orders for a vendor
 * Used by Annexure 2A form - Section D
 */
require_once '../../include/config.php';
header('Content-Type: application/json');

$vendor_code = $_GET['vendor_code'] ?? $_POST['vendor_code'] ?? '';

if (empty($vendor_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Vendor code required']);
    exit;
}

$sales = db_fetch_all($conn, 
    "SELECT sale_order_no, customer_code, customer_name, amount, currency, 
            doc_date, sales_organization, department, description
     FROM sap_sale_order_master WHERE vendor_code = ? OR customer_code = ? ORDER BY doc_date DESC", 
    'ss', [$vendor_code, $vendor_code]
);

echo json_encode([
    'status' => 'success',
    'count' => count($sales),
    'data' => $sales
]);
