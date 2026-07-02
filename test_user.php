<?php
require 'include/config.php';
$r = $conn->query("SELECT * FROM users WHERE username = '1100909'");
print_r($r->fetch_all(MYSQLI_ASSOC));
