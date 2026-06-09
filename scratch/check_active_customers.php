<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== Active Customers in users Table ===\n";
$res = mysqli_query($conn, "SELECT id, name, email, role, contractor_id, status FROM users WHERE role = 'customer'");
if (mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        echo "users: ID: {$row['id']} | Name: {$row['name']} | Contractor_ID (Customer Code): {$row['contractor_id']} | Status: {$row['status']}\n";
    }
} else {
    echo "No customer accounts found in users table\n";
}

echo "\n=== Active Customers in sap_customer_master Table ===\n";
$res2 = mysqli_query($conn, "SELECT id, customer_name, customer_code, is_password_created, ACTIVE_IND, status FROM sap_customer_master WHERE is_password_created = 1 OR status = 'ACTIVE' OR ACTIVE_IND = 'A'");
if (mysqli_num_rows($res2) > 0) {
    while($row = mysqli_fetch_assoc($res2)) {
        echo "sap_customer_master: ID: {$row['id']} | Name: {$row['customer_name']} | Code: {$row['customer_code']} | Password Created: {$row['is_password_created']} | Active Ind: {$row['ACTIVE_IND']} | Status: {$row['status']}\n";
    }
} else {
    echo "No active customers found in sap_customer_master table\n";
}
?>
