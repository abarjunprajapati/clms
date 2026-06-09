<?php
require_once 'include/config.php';

$tables = [
    'sap_vendors' => "CREATE TABLE IF NOT EXISTS sap_vendors (
        vendor_code VARCHAR(50) PRIMARY KEY,
        vendor_name VARCHAR(200),
        mobile1 VARCHAR(20),
        mobile2 VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        msme_type VARCHAR(50),
        active_ind CHAR(1) DEFAULT 'A'
    )",
    'sap_po_master' => "CREATE TABLE IF NOT EXISTS sap_po_master (
        po_number VARCHAR(50) PRIMARY KEY,
        vendor_code VARCHAR(50),
        po_type VARCHAR(50),
        purchasing_group VARCHAR(50),
        header_text TEXT,
        po_date DATE
    )",
    'sap_pwo_master' => "CREATE TABLE IF NOT EXISTS sap_pwo_master (
        pwo_number VARCHAR(50) PRIMARY KEY,
        po_number VARCHAR(50),
        vessel VARCHAR(100),
        description TEXT,
        project VARCHAR(100),
        completion_date DATE
    )",
    'sap_sales_order_master' => "CREATE TABLE IF NOT EXISTS sap_sales_order_master (
        sales_order_number VARCHAR(50) PRIMARY KEY,
        vendor_code VARCHAR(50),
        amount DECIMAL(15,2),
        currency VARCHAR(10),
        doc_date DATE,
        description TEXT
    )"
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "Table $name ensured.\n";
    } else {
        echo "Error creating table $name: " . $conn->error . "\n";
    }
}

// Add some dummy data for testing if empty
$check = $conn->query("SELECT COUNT(*) FROM sap_vendors")->fetch_row()[0];
if ($check == 0) {
    $conn->query("INSERT INTO sap_vendors (vendor_code, vendor_name, mobile1, email, address) VALUES ('V1001', 'Global Services', '9876543210', 'global@example.com', 'Mumbai, India')");
    $conn->query("INSERT INTO sap_po_master (po_number, vendor_code, po_type, header_text) VALUES ('PO8899', 'V1001', 'ZCON', 'Annual Maintenance Contract')");
    $conn->query("INSERT INTO sap_pwo_master (pwo_number, po_number, vessel, description) VALUES ('PWO123', 'PO8899', 'INS Vikrant', 'Electrical Repairs')");
    $conn->query("INSERT INTO sap_sales_order_master (sales_order_number, vendor_code, amount, currency) VALUES ('SO5544', 'V1001', 50000.00, 'INR')");
    echo "Dummy SAP data inserted.\n";
}
?>
