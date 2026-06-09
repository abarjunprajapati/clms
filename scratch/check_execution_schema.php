<?php
include __DIR__ . '/../include/config.php';
$tables = ['execution_officers', 'execution_officer_contractors', 'execution_worker_deployments', 'execution_observations', 'execution_actions'];
foreach($tables as $table) {
    echo "--- $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table not found.\n";
    }
}
?>
