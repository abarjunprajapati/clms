<?php
require_once __DIR__ . '/../include/config.php';

$res = mysqli_query($conn, "SELECT contractor_id FROM users WHERE role = 'contractor' AND contractor_id REGEXP '^[0-9]{5,7}$'");
while ($row = mysqli_fetch_assoc($res)) {
    $code = $row['contractor_id'];
    $exists = db_single($conn, "SELECT customer_code FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);
    if (!$exists) {
        echo "Missing from SAP: $code\n";
    }
}
?>
