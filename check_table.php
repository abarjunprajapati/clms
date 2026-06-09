<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE 'productivity_reports'");
echo "Rows: " . mysqli_num_rows($res) . "\n";
