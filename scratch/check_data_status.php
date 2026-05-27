<?php
include __DIR__ . '/../include/config.php';
$tables = ['execution_officers', 'execution_officer_contractors', 'execution_worker_deployments', 'execution_observations', 'execution_escalations', 'execution_productivity_logs', 'attendance_exceptions'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $t");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        echo "$t: " . $row['cnt'] . "\n";
    } else {
        echo "$t: ERROR (" . mysqli_error($conn) . ")\n";
    }
}
?>
