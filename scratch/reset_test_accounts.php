<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== RESETTING CONTRACTOR ACCOUNTS (1100908 & 1100927) ===\n";

// 1. Set user_id in contractors to NULL first to bypass foreign key constraint
$q = "UPDATE contractors SET user_id = NULL, status = 'draft', approval_reason = NULL WHERE vendor_code IN ('1100908', '1100927')";
if (mysqli_query($conn, $q)) {
    echo "- Set user_id = NULL and status = 'draft' in contractors table: " . mysqli_affected_rows($conn) . " rows.\n";
}

// 2. Delete users table records
$q = "DELETE FROM users WHERE contractor_id IN ('1100908', '1100927')";
if (mysqli_query($conn, $q)) {
    echo "- Deleted contractor users from 'users' table: " . mysqli_affected_rows($conn) . " rows.\n";
}

// 3. Reset annexure2a table status to 'draft'
$q = "UPDATE annexure2a SET workflow_status = 'draft' WHERE contractor_id IN (SELECT id FROM contractors WHERE vendor_code IN ('1100908', '1100927'))";
if (mysqli_query($conn, $q)) {
    echo "- Reset 'annexure2a' table workflow_status to 'draft': " . mysqli_affected_rows($conn) . " rows.\n";
}

// 4. Clean status history
$q = "DELETE FROM contractor_status_history WHERE contractor_id IN (SELECT id FROM contractors WHERE vendor_code IN ('1100908', '1100927'))";
if (mysqli_query($conn, $q)) {
    echo "- Cleaned status history records.\n";
}


echo "\n=== RESETTING CUSTOMER ACCOUNTS (53585, 54557 & 55065) ===\n";

// 1. Reset sap_customer_master table values to default/unactivated
$q = "UPDATE sap_customer_master SET 
        is_password_created = NULL,
        login_password = NULL,
        status = NULL,
        login_attempts = 0,
        last_login = NULL,
        last_otp_sent_at = NULL,
        password_updated_at = NULL,
        reset_token = NULL,
        reset_expiry = NULL,
        reset_attempts = 0
      WHERE customer_code IN ('53585', '54557', '55065')";

if (mysqli_query($conn, $q)) {
    echo "- Reset active customer records in 'sap_customer_master': " . mysqli_affected_rows($conn) . " rows.\n";
}

// 2. Just in case, delete any users matching these codes
$q = "DELETE FROM users WHERE contractor_id IN ('53585', '54557', '55065')";
if (mysqli_query($conn, $q)) {
    echo "- Cleaned matching customer records in 'users' table: " . mysqli_affected_rows($conn) . " rows.\n";
}

echo "\n=== VERIFYING FINAL STATES ===\n";

// Verify Contractor 1100908
$res = mysqli_query($conn, "SELECT status FROM contractors WHERE vendor_code = '1100908'");
$c = mysqli_fetch_assoc($res);
$res2 = mysqli_query($conn, "SELECT workflow_status FROM annexure2a WHERE contractor_id = (SELECT id FROM contractors WHERE vendor_code = '1100908')");
$a = mysqli_fetch_assoc($res2);
$res3 = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE contractor_id = '1100908'");
$u = mysqli_fetch_assoc($res3);
echo "Contractor 1100908 -> Table Status: {$c['status']} | Annexure2A Workflow: " . ($a['workflow_status'] ?? 'N/A') . " | Active User Account: " . ($u['cnt'] > 0 ? 'YES' : 'NO') . "\n";

// Verify Contractor 1100927
$res = mysqli_query($conn, "SELECT status FROM contractors WHERE vendor_code = '1100927'");
$c = mysqli_fetch_assoc($res);
$res3 = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE contractor_id = '1100927'");
$u = mysqli_fetch_assoc($res3);
echo "Contractor 1100927 -> Table Status: {$c['status']} | Active User Account: " . ($u['cnt'] > 0 ? 'YES' : 'NO') . "\n";

// Verify Customers
$res = mysqli_query($conn, "SELECT customer_code, customer_name, is_password_created, status FROM sap_customer_master WHERE customer_code IN ('53585', '54557', '55065')");
while($row = mysqli_fetch_assoc($res)) {
    echo "Customer {$row['customer_code']} ({$row['customer_name']}) -> Password Created: " . ($row['is_password_created'] ?? 'NULL') . " | Status: " . ($row['status'] ?? 'NULL') . "\n";
}

?>
