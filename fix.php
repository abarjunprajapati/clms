<?php
require_once __DIR__ . '/include/config.php';
db_execute($conn, "UPDATE annexure2a SET workflow_status = 'resubmitted' WHERE contractor_id = (SELECT id FROM contractors WHERE vendor_code = '1100914')");
echo "Fixed 1100914\n";
?>
