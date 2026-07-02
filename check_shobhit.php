<?php
require 'c:/xampp/htdocs/CLMS1/include/config.php';
$res=db_fetch_all($conn, "SELECT id, name, safety_enrollment_status, training_status FROM workmen WHERE name LIKE '%shobhit%'");
var_dump($res);
?>
