<?php
require 'c:/xampp/htdocs/CLMS1/include/config.php';
mysqli_query($conn, "UPDATE training_requests SET status='pending_safety' WHERE status='pending' AND source='contractor_re_enroll'");
echo "Done";
?>
