<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = mysqli_query($conn, "SELECT id, status, approval_reason FROM contractors WHERE id = 26");
$c = mysqli_fetch_assoc($res);
echo "Contractors Table - Status: {$c['status']} | Reason: {$c['approval_reason']}\n";

$res2 = mysqli_query($conn, "SELECT workflow_status FROM annexure2a WHERE contractor_id = 26");
$a = mysqli_fetch_assoc($res2);
echo "Annexure2a Table - Workflow Status: {$a['workflow_status']}\n";
?>
