<?php
$c = mysqli_connect('localhost', 'root', '', 'new_clms');
$res = mysqli_query($c, "SELECT * FROM roles");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
