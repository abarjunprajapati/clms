<?php
require 'c:/xampp/htdocs/CLMS1/include/config.php';
mysqli_query($conn, "UPDATE training_requests SET status='pending' WHERE status='pending_safety'");
echo "Done";
?>
