<?php
require_once __DIR__ . '/include/config.php';
db_execute($conn, "UPDATE annexure2a SET workflow_status = 'resubmitted' WHERE contractor_id = (SELECT id FROM contractors WHERE vendor_code = '1100914') AND workflow_status = 'submitted'");
echo "Contractor 1100914 status has been successfully updated to RESUBMITTED in the database.";
?>
