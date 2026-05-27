<?php
include 'include/config.php';
$tables = ['contractors', 'workmen'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
}
?>

