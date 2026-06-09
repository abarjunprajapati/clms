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

// Dynamically check if customer_code exists to avoid SQL validation errors
$check_col = clms_db_query($conn, "SHOW COLUMNS FROM sap_po_master LIKE 'customer_code'");
$has_customer_code = ($check_col && clms_db_num_rows($check_col) > 0);

if ($has_customer_code) {
    $pos = db_fetch_all($conn, 
        "SELECT po_number, po_type, purchasing_group, header_text, currency, 
                total_value, document_date, tender_type, tender_type_text, 
                msme_type, msme_type_text, release_status, contract_number,
                company_code, purchasing_organization, document_type
         FROM sap_po_master WHERE vendor_code = ? OR customer_code = ? ORDER BY document_date DESC", 
        'ss', [$vendor_code, $vendor_code]
    );
} else {
    $pos = db_fetch_all($conn, 
        "SELECT po_number, po_type, purchasing_group, header_text, currency, 
                total_value, document_date, tender_type, tender_type_text, 
                msme_type, msme_type_text, release_status, contract_number,
                company_code, purchasing_organization, document_type
         FROM sap_po_master WHERE vendor_code = ? ORDER BY document_date DESC", 
        's', [$vendor_code]
    );
}

echo json_encode([
    'status' => 'success',
    'count' => count($pos),
    'data' => $pos
]);
