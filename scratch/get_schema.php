<?php
include 'include/config.php';
$res = $conn->query("DESCRIBE sap_vendors");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
