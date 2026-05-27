<?php
require 'include/config.php';
$res = db_fetch_all($conn, "DESCRIBE users");
print_r($res);
