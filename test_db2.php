<?php
require_once __DIR__ . '/include/config.php';
$wo = db_single($conn, "SELECT source FROM contractor_projects WHERE work_order_no = 'PO1100929001'");
if ($wo) {
    echo "Contractor projects source: " . $wo['source'] . "\n";
} else {
    echo "Not found in contractor_projects\n";
}

$po = db_single($conn, "SELECT po_number FROM contractor_po_selection WHERE po_number = 'PO1100929001'");
if ($po) echo "Found in contractor_po_selection\n";

$pwo = db_single($conn, "SELECT pwo_number FROM contractor_pwo_selection WHERE pwo_number = 'PO1100929001'");
if ($pwo) echo "Found in contractor_pwo_selection\n";

$sap = db_single($conn, "SELECT pwo_number FROM sap_pwo_master WHERE pwo_number = 'PO1100929001'");
if ($sap) echo "Found in sap_pwo_master\n";

$sap_po = db_single($conn, "SELECT po_number FROM sap_po_master WHERE po_number = 'PO1100929001'");
if ($sap_po) echo "Found in sap_po_master\n";
