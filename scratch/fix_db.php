<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "Altering table sap_sale_order_master...\n";

$sql = "ALTER TABLE sap_sale_order_master 
        ADD COLUMN customer_name varchar(255) DEFAULT NULL AFTER customer_code,
        ADD COLUMN vendor_code varchar(50) DEFAULT NULL AFTER status,
        ADD COLUMN po_number varchar(100) DEFAULT NULL AFTER vendor_code,
        ADD COLUMN department varchar(100) DEFAULT NULL AFTER po_number";

if (mysqli_query($conn, $sql)) {
    echo "Columns added successfully!\n";
} else {
    echo "Error altering table: " . mysqli_error($conn) . "\n";
}

// Also run the user's insert statement
$insert_sql = "INSERT INTO sap_sale_order_master (
    sale_order_no,
    customer_code,
    customer_name,
    amount,
    currency,
    doc_date,
    sales_organization,
    description,
    status
) VALUES (
    'SO55065',
    '55065',
    'Sample Customer',
    250000.00,
    'INR',
    CURDATE(),
    'SO001',
    'Sample Sale Order for Customer Code 55065',
    'active'
)";

if (mysqli_query($conn, $insert_sql)) {
    echo "Insert statement executed successfully!\n";
} else {
    echo "Error inserting: " . mysqli_error($conn) . "\n";
}
