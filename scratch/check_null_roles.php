<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "SELECT id, role, role_id FROM users WHERE role_id IS NULL OR role_id = 0");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>

