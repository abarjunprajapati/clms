<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "CHECKING SAP_SALE_ORDER_MASTER:\n";
$rows = db_fetch_all($conn, "SELECT * FROM sap_sale_order_master WHERE customer_code = '55065'");
if (empty($rows)) {
    echo "No rows found for customer_code 55065.\n";
} else {
    foreach ($rows as $row) {
        echo "ID: {$row['id']} | SO: '{$row['sale_order_no']}' | Cust: '{$row['customer_code']}' | Name: '{$row['customer_name']}' | PO: '{$row['po_number']}' | Status: '{$row['status']}'\n";
    }
}

echo "\nALL ROWS IN sap_sale_order_master:\n";
$all = db_fetch_all($conn, "SELECT * FROM sap_sale_order_master LIMIT 10");
foreach ($all as $row) {
    echo "ID: {$row['id']} | SO: '{$row['sale_order_no']}' | Cust: '{$row['customer_code']}' | Name: '{$row['customer_name']}' | PO: '{$row['po_number']}' | Status: '{$row['status']}'\n";
}
