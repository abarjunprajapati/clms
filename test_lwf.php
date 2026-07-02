<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(__DIR__ . '/pages/contractor');
require '../../include/config.php';

$c = db_single($conn, "SELECT user_id, vendor_code FROM contractors LIMIT 1");
if (!$c) die("No contractor found");

session_start();
$_SESSION['role'] = 'contractor';
$_SESSION['user_id'] = $c['user_id'];
$_SESSION['contractor_id'] = $c['vendor_code'];
$_SESSION['vendor_code'] = $c['vendor_code'];

try {
    require 'lwf_rate_view.php';
} catch (Throwable $e) {
    echo "Fatal Error Caught: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
