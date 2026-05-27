<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

function check_account($conn, $code) {
    echo "=== Account: $code ===\n";
    
    // 1. Users table
    $res = mysqli_query($conn, "SELECT id, name, email, role, contractor_id, status FROM users WHERE contractor_id = '$code'");
    if (mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "users: ID: {$row['id']} | Name: {$row['name']} | Role: {$row['role']} | Status: {$row['status']}\n";
        }
    } else {
        echo "users: No record found\n";
    }
    
    // 2. Contractors table
    $res = mysqli_query($conn, "SELECT id, contractor_name, status, is_blocked FROM contractors WHERE vendor_code = '$code'");
    if (mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "contractors: ID: {$row['id']} | Name: {$row['contractor_name']} | Status: {$row['status']} | Blocked: {$row['is_blocked']}\n";
        }
    } else {
        echo "contractors: No record found\n";
    }

    // 3. Annexure2a table
    $res = mysqli_query($conn, "SELECT id, contractor_name, workflow_status FROM annexure2a WHERE contractor_id = (SELECT id FROM contractors WHERE vendor_code = '$code')");
    if ($res && mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "annexure2a: ID: {$row['id']} | Name: {$row['contractor_name']} | Status: {$row['workflow_status']}\n";
        }
    } else {
        echo "annexure2a: No record found\n";
    }

    // 4. sap_customer_master table
    $res = mysqli_query($conn, "SELECT id, customer_name, customer_code, is_password_created, ACTIVE_IND, status FROM sap_customer_master WHERE customer_code = '$code'");
    if (mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "sap_customer_master: ID: {$row['id']} | Name: {$row['customer_name']} | Password Created: {$row['is_password_created']} | Active Ind: {$row['ACTIVE_IND']} | Status: {$row['status']}\n";
        }
    } else {
        echo "sap_customer_master: No record found\n";
    }
    
    echo "\n";
}

check_account($conn, '1100908');
check_account($conn, '1100927');
?>
