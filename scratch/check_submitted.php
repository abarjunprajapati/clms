<?php
include 'include/config.php';
$res = $conn->query("SELECT a.contractor_id, a.contractor_name, a.workflow_status, c.vendor_code FROM annexure2a a JOIN contractors c ON a.contractor_id = c.id WHERE a.workflow_status = 'submitted'");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
