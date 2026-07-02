<?php
require 'include/config.php';
$res = $conn->query("SELECT role FROM users WHERE role LIKE '%admin%'");
print_r($res->fetch_all(MYSQLI_ASSOC));
