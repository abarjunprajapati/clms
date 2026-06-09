<?php
require 'include/config.php';
$res = db_fetch_all($conn, "SHOW TRIGGERS");
print_r($res);
