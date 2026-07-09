<?php
require_once __DIR__ . '/include/config.php';
$cp = db_fetch_all($conn, "SELECT work_order_no, source FROM contractor_projects WHERE work_order_no = 'PO1100929001'");
print_r($cp);
