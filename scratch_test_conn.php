<?php
echo "--- TESTING LOCALHOST ---\n";
$conn1 = @mysqli_connect('localhost', 'root', '', 'new_clms');
if ($conn1) {
    echo "LOCALHOST OK\n";
    mysqli_close($conn1);
} else {
    echo "LOCALHOST FAIL: " . mysqli_connect_error() . "\n";
}

echo "--- TESTING 127.0.0.1 ---\n";
$conn2 = @mysqli_connect('127.0.0.1', 'root', '', 'new_clms');
if ($conn2) {
    echo "127.0.0.1 OK\n";
    mysqli_close($conn2);
} else {
    echo "127.0.0.1 FAIL: " . mysqli_connect_error() . "\n";
}
?>
