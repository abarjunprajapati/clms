<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "SELECT COUNT(*) FROM productivity_reports");
$row = mysqli_fetch_row($res);
echo "Count: " . $row[0] . "\n";
