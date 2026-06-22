<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "CHECKING CONTRACTOR_SO_SELECTION:\n";
$rows = db_fetch_all($conn, "SELECT * FROM contractor_so_selection");
foreach ($rows as $row) {
    print_r($row);
}

echo "\nCHECKING CONTRACTORS:\n";
$contractors = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name, user_id FROM contractors");
foreach ($contractors as $c) {
    print_r($c);
}
