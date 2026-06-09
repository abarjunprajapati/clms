<?php
include 'include/config.php';

function describeTable($conn, $table) {
    echo "\n--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if (!$res) {
        echo "Error or table does not exist: " . $conn->error . "\n";
        return;
    }
    while ($row = $res->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
    }
}

describeTable($conn, 'sap_customer_master');
describeTable($conn, 'users');
describeTable($conn, 'sap_vendor_master');

echo "\n--- Role counts in users table ---\n";
$res = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Sample from sap_customer_master ---\n";
$res = $conn->query("SELECT customer_code, customer_name, email FROM sap_customer_master LIMIT 3");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Contractors in users table ---\n";
$res = $conn->query("SELECT id, contractor_id, name, email FROM users WHERE role = 'contractor'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
