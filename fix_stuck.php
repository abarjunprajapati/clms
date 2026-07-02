<?php
require 'include/config.php';
$conn->query("UPDATE gate_pass_requests gpr JOIN gate_pass_request_workers gprw ON gpr.id = gprw.request_id JOIN workmen w ON w.id = gprw.workman_id SET gpr.status = 'approved', gprw.status = 'approved', w.status = 'verified', w.pass_issuer_verified = 1 WHERE w.name = 'samuel Augustine'");
echo "Done";
