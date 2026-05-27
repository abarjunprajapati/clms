<?php
require_once 'include/config.php';
$res = $conn->query("SELECT id, contractor_id, email, role FROM users WHERE contractor_id LIKE '%welfare%' OR email LIKE '%welfare%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
