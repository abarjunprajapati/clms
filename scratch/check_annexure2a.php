<?php
require_once '../include/config.php';
$res = db_fetch_all($conn, "DESCRIBE annexure2a");
echo json_encode($res, JSON_PRETTY_PRINT);
