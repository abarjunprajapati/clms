<?php
require_once 'include/config.php';
mysqli_query($conn, "ALTER TABLE workflow_status MODIFY application_id VARCHAR(50)");
echo "Altered workflow_status successfully.\n";

