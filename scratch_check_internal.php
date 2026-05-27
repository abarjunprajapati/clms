<?php
require_once 'include/config.php';
$user = db_single($conn, "SELECT id, contractor_id, role, password FROM users WHERE email = 'welfare1@example.com' OR contractor_id = 'welfare1@example.com'");
print_r($user);
?>
