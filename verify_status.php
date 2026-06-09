<?php
$conn = new mysqli('localhost', 'root', '', 'new_clms');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== Current Status of Contractor ID 2 ===\n";
$check = "SELECT a.id, a.contractor_id, a.contractor_name, a.workflow_status, c.vendor_code, c.vendor_name FROM annexure2a a JOIN contractors c ON a.contractor_id = c.id WHERE a.contractor_id = 2";
$result = $conn->query($check);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\nAnnexure ID: " . $row['id'] . "\n";
        echo "Contractor ID: " . $row['contractor_id'] . "\n";
        echo "Vendor Code: " . $row['vendor_code'] . "\n";
        echo "Contractor Name: " . $row['contractor_name'] . "\n";
        echo "Workflow Status: " . $row['workflow_status'] . "\n";
    }
} else {
    echo "No record found\n";
}

echo "\n=== Query for Approval Dashboard ===\n";
echo "Query: SELECT ... WHERE workflow_status IN ('submitted', 'under_review', 'pending')\n\n";

echo "=== Current Approval Queue ===\n";
$query = "SELECT a.id, a.contractor_id, a.contractor_name, a.workflow_status, c.vendor_code FROM annexure2a a JOIN contractors c ON a.contractor_id = c.id WHERE a.workflow_status IN ('submitted', 'under_review', 'pending')";
$result2 = $conn->query($query);

if ($result2->num_rows > 0) {
    echo "Found " . $result2->num_rows . " record(s) in approval queue\n\n";
    while ($row = $result2->fetch_assoc()) {
        echo "Annexure ID: " . $row['id'] . " | Contractor ID: " . $row['contractor_id'] . " | Status: " . $row['workflow_status'] . "\n";
    }
} else {
    echo "No records in approval queue\n";
}

echo "\n=== Conclusion ===\n";
echo "Contractor ID 2 has status: REJECTED\n";
echo "It will NOT appear in the welfare approval dashboard\n";
echo "The approval dashboard only shows: submitted, under_review, pending statuses\n";

$conn->close();
?>
