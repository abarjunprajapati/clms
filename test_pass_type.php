<?php
require 'include/config.php';
$res = db_fetch_all($conn, "SELECT gpr.id, gpr.pass_type, gpr.status, w.name, w.temp_pass_status FROM gate_pass_requests gpr JOIN gate_pass_request_workers gprw ON gpr.id = gprw.request_id JOIN workmen w ON gprw.workman_id = w.id WHERE w.name LIKE '%ss vv%'");
print_r($res);
?>
