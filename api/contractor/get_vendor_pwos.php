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

$pwos = db_fetch_all($conn,
    "SELECT pwo_number, vessel, work_completion_date, pwo_description, project, status
     FROM sap_pwo_master
     WHERE TRIM(vendor_code) = TRIM(?)
     ORDER BY created_at DESC",
    's',
    [$vendor_code]
);

echo json_encode([
    'status' => 'success',
    'count' => count($pwos),
    'data' => $pwos
]);
