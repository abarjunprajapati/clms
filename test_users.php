<?php
require 'include/config.php';
$r = $conn->query("SELECT id, username, contractor_id FROM users WHERE username IN ('1100900', '1100909', '1100908')");
print_r($r->fetch_all(MYSQLI_ASSOC));
