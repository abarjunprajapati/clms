<?php
$c = mysqli_connect('localhost', 'root', '', 'updated_clms');
$res = mysqli_query($c, "DESCRIBE annexure3a");
while ($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}
?>

