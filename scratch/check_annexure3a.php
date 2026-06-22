<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "CHECKING CONTRACTOR_ANNEXURE3A FOR 55065:\n";
$rows = db_fetch_all($conn, "SELECT id, vendor_code, work_order_no, customer_code, status, updated_at FROM contractor_annexure3a WHERE customer_code = '55065'");
foreach ($rows as $row) {
    print_r($row);
}
