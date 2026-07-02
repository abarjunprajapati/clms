<?php
require 'c:\xampp\htdocs\CLMS1\include\config.php';
mysqli_query($conn, "UPDATE workmen SET training_status='training_passed', eligibility_status='ELIGIBLE', safety_training_status='TRAINING_PASSED' WHERE temp_id='TEMP-000049'");
echo "Affected: " . mysqli_affected_rows($conn);
?>
