<?php
require_once 'include/config.php';
function desc($table) {
    global $conn;
    echo "--- $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if($res) while($row = mysqli_fetch_assoc($res)) echo $row['Field'] . " | " . $row['Type'] . "\n";
}
desc('documents');
desc('training_results');
desc('training_session_workers');
?>

