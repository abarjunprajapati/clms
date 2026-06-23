<?php
require_once __DIR__ . '/../include/config.php';
$res = $conn->query("SELECT id, name, contractor_id, status, execution_training_status, safety_training_status, is_blocked, work_order_no, worker_type FROM workmen");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
