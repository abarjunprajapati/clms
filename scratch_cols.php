<?php
include 'include/config.php';
$cols = db_fetch_all($conn, "SHOW COLUMNS FROM workmen");
print_r(array_column($cols, 'Field'));
?>
