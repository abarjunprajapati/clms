<?php
require 'include/config.php';
$res = db_single($conn, 'SELECT application_id FROM annexure2a WHERE contractor_id = 1');
echo $res ? "Application ID in Annexure2a: " . $res['application_id'] : "Application NOT Found in Annexure2a for contractor_id=1";

