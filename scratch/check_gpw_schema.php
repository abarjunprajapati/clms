<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "SHOW COLUMNS FROM gate_pass_request_workers");
while ($row = mysqli_fetch_assoc($res)) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>

