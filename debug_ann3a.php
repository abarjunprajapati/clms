<?php
include "include/config.php";
$res = $conn->query("SELECT * FROM annexure3a LIMIT 10");
$data = [];
while($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode($data);

