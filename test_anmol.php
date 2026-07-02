<?php
require 'include/config.php';
$res = $conn->query("SELECT d.id, d.document_type, d.status as doc_status, w.name, gpr.status AS gpr_status, gpr.id as req_id FROM documents d JOIN workmen w ON w.id = d.workman_id LEFT JOIN gate_pass_requests gpr ON d.gate_pass_request_id = gpr.id WHERE w.name LIKE '%Anmol%'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
?>
