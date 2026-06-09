<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
$tables = ['attendance_sync_queue', 'contractor_block_history', 'contractors', 'workmen'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    echo "$t: " . (mysqli_num_rows($res) > 0 ? "EXISTS" : "MISSING") . "\n";
    if (mysqli_num_rows($res) > 0) {
        $res2 = mysqli_query($conn, "DESCRIBE $t");
        while($row = mysqli_fetch_assoc($res2)) {
            echo "  - {$row['Field']}\n";
        }
    }
}
?>
