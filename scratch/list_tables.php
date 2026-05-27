<?php
$c = mysqli_connect('localhost', 'root', '', 'new_clms');
$res = mysqli_query($c, "SHOW TABLES");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}
?>
