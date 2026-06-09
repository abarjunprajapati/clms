<?php
include 'include/config.php';
$res = $conn->query("DESCRIBE contractor_annexure3a");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
