<?php
require 'include/config.php';
$res = db_single($conn, "SELECT workflow_status FROM annexure2a WHERE application_id = 'CMS-2026-0157'");
echo $res ? "Status: " . $res['workflow_status'] : "Application Not Found";

