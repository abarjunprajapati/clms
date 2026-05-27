<?php
include 'include/config.php';
$res = $conn->query('SELECT id, status, scheduled_date, preferred_date, created_at FROM training_requests LIMIT 20');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
