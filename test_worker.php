<?php
require_once __DIR__ . '/include/config.php';
$res = mysqli_query($conn, "SELECT id, name, expected_joining_date, status, training_status FROM workmen");
while($row = mysqli_fetch_assoc($res)){
    print_r($row);
}
?>
