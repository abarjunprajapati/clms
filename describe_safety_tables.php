<?php
include 'include/config.php';
$tables = ['safety_training', 'training_results', 'training_schedule', 'training_sessions'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
}
?>

