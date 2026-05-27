<?php
/**
 * Get PWO Details for a vendor
 * Used by Annexure 2A form - Section C
 */
require_once '../../include/config.php';
header('Content-Type: application/json');

$vendor_code = $_GET['vendor_code'] ?? $_POST['vendor_code'] ?? '';

if (empty($vendor_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Vendor code required']);
    exit;
}

// Dynamically check if customer_code exists to avoid SQL validation errors
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM sap_pwo_master LIKE 'customer_code'");
$has_customer_code = ($check_col && mysqli_num_rows($check_col) > 0);

if ($has_customer_code) {
    $pwos = db_fetch_all($conn, 
        "SELECT pwo_number, vessel, work_completion_date, pwo_description, project
         FROM sap_pwo_master WHERE vendor_code = ? OR customer_code = ? ORDER BY created_at DESC", 
        'ss', [$vendor_code, $vendor_code]
    );
} else {
    $pwos = db_fetch_all($conn, 
        "SELECT pwo_number, vessel, work_completion_date, pwo_description, project
         FROM sap_pwo_master WHERE vendor_code = ? ORDER BY created_at DESC", 
        's', [$vendor_code]
    );
}

echo json_encode([
    'status' => 'success',
    'count' => count($pwos),
    'data' => $pwos
]);
