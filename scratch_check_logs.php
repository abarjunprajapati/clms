<?php
require_once 'include/config.php';
$cols = $conn->query("SHOW COLUMNS FROM login_logs");
if (!$cols) {
    echo "Error showing columns: " . $conn->error . "\n";
    exit;
}
$col_names = [];
while($c = $cols->fetch_assoc()) $col_names[] = $c['Field'];
echo "Columns: " . implode(', ', $col_names) . "\n";

$order_by = in_array('created_at', $col_names) ? 'created_at' : (in_array('attempted_at', $col_names) ? 'attempted_at' : 'id');

$result = $conn->query("SELECT * FROM login_logs ORDER BY $order_by DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "[" . ($row['created_at'] ?? $row['attempted_at'] ?? $row['id']) . "] " . $row['identifier'] . " - " . $row['status'] . " - " . $row['failure_reason'] . "\n";
}
?>
