<?php
include '../include/config.php';
$res = $conn->query("SELECT customer_code, customer_name, ACTIVE_IND FROM sap_customer_master WHERE customer_code LIKE '%110090%'");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data, JSON_PRETTY_PRINT);
?>
