<?php
include 'include/config.php';
echo "--- Workers with training_passed ---\n";
$res = $conn->query("SELECT id, name, training_status, safety_training_status FROM workmen WHERE training_status = 'training_passed' LIMIT 10");
while($row = $res->fetch_assoc()) {
    print_r($row);
    $wid = $row['id'];
    echo "  Requests for this worker:\n";
    $reqs = $conn->query("SELECT id, status, scheduled_date, preferred_date, updated_at, created_at FROM training_requests WHERE workman_id = $wid");
    while($r = $reqs->fetch_assoc()) {
        print_r($r);
    }
}
?>
