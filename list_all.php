<?php
$conn = new mysqli('localhost', 'root', '', 'new_clms');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== All Annexure2a Records ===\n";
$all = "SELECT a.id, a.contractor_id, a.contractor_name, a.workflow_status FROM annexure2a a";
$result = $conn->query($all);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Contractor ID: " . $row['contractor_id'] . " | Name: " . $row['contractor_name'] . " | Status: " . $row['workflow_status'] . "\n";
    }
} else {
    echo "No records found\n";
}

$conn->close();
?>
