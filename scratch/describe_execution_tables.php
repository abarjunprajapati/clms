<?php
$conn = mysqli_connect("localhost", "root", "", "new_clms");
$tables = [
    'execution_officers', 
    'execution_officer_workorders', 
    'execution_officer_contractors', 
    'execution_observations', 
    'execution_worker_deployments', 
    'execution_daily_reports', 
    'execution_actions',
    'workmen',
    'contractors',
    'work_orders',
    'users',
    'execution_officers'
];
foreach($tables as $t) {
    echo "--- $t ---\n";
    $res = $conn->query("DESCRIBE `$t` ");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo str_pad($row['Field'], 25) . " " . $row['Type'] . "\n";
        }
    } else {
        echo "Table not found or error\n";
    }
    echo "\n";
}
?>
