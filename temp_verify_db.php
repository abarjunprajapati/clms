<?php
$conn = mysqli_connect('127.0.0.1', 'root', '', 'updated_clms');
if (!$conn) {
    echo 'CONNECT_FAIL\n';
    exit(1);
}
$tables = ['workflow_logs', 'application_workflow', 'permanent_gate_passes'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    echo "TABLE $t: " . ($res && $res->num_rows ? 'FOUND' : 'MISSING') . "\n";
}
$res = $conn->query("SHOW COLUMNS FROM annexure2a LIKE 'workflow_status'");
echo 'COLUMN annexure2a.workflow_status: ' . ($res && $res->num_rows ? 'FOUND' : 'MISSING') . "\n";
$res = $conn->query("SHOW COLUMNS FROM applications LIKE 'status'");
echo 'COLUMN applications.status: ' . ($res && $res->num_rows ? 'FOUND' : 'MISSING') . "\n";
?>
