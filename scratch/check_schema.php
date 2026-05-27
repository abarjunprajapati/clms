<?php
include 'd:/Xampp/htdocs/clms/include/config.php';
$res = mysqli_query($conn, "DESCRIBE contractors status");
print_r(mysqli_fetch_assoc($res));
