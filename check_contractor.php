<?php
// Connect to new_clms database
$conn = new mysqli('localhost', 'root', '', 'new_clms');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== Checking Contractor ID 2 Details ===\n\n";

// Check if contractor_id 2 exists
$check = "SELECT * FROM contractors WHERE id = 2";
$result = $conn->query($check);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Contractor Record Found:\n";
    foreach ($row as $key => $val) {
        echo "  $key: $val\n";
    }
} else {
    echo "Contractor ID 2 not found in contractors table\n";
}

echo "\n=== Checking Annexure2a Records for Contractor ID 2 ===\n";

// Check all annexure2a records for contractor_id 2
$check2 = "SELECT * FROM annexure2a WHERE contractor_id = 2";
$result2 = $conn->query($check2);

if ($result2->num_rows > 0) {
    echo "Found " . $result2->num_rows . " annexure2a record(s):\n";
    while ($row = $result2->fetch_assoc()) {
        echo "\nRecord ID: " . $row['id'] . "\n";
        foreach ($row as $key => $val) {
            echo "  $key: $val\n";
        }
    }
} else {
    echo "No annexure2a records found for contractor_id 2\n";
}

echo "\n=== All Annexure2a Records with Their Statuses ===\n";
$all = "SELECT a.id, a.contractor_id, a.contractor_name, a.workflow_status, c.vendor_code FROM annexure2a a JOIN contractors c ON a.contractor_id = c.id";
$resultAll = $conn->query($all);

while ($row = $resultAll->fetch_assoc()) {
    echo "Annexure ID: " . $row['id'] . " | Contractor ID: " . $row['contractor_id'] . " | Name: " . $row['contractor_name'] . " | Status: " . $row['workflow_status'] . " | Vendor: " . $row['vendor_code'] . "\n";
}

$conn->close();
?>
