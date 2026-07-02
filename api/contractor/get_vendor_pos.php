<?php
/**
 * Get PO Details for a vendor
 * Used by Annexure 2A form - Section B
 */
require_once '../../include/config.php';
header('Content-Type: application/json');

$vendor_code = $_GET['vendor_code'] ?? $_POST['vendor_code'] ?? '';

if (empty($vendor_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Vendor code required']);
    exit;
}

$pos = db_fetch_all($conn,
    "SELECT po_number, po_type, purchasing_group, header_text, currency,
            total_value, document_date, tender_type, tender_type_text,
            msme_type, msme_type_text, contract_number,
            company_code, purchasing_organization, document_type
     FROM sap_po_master
     WHERE TRIM(vendor_code) = TRIM(?)
     ORDER BY document_date DESC",
    's',
    [$vendor_code]
);

echo json_encode([
    'status' => 'success',
    'count' => count($pos),
    'data' => $pos
]);
