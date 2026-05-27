<?php
include 'include/config.php';
$r = $conn->query("SELECT id, name, status, training_status FROM workmen WHERE id=12");
$row = $r->fetch_assoc();
print_r($row);

