<?php
// Connect to new_clms database
$conn = new mysqli('localhost', 'root', '', 'new_clms');

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Query from approve_contractors.php
$query = "SELECT a.id, a.contractor_id, a.contractor_name, a.workflow_status, c.vendor_code, c.vendor_name
FROM annexure2a a
JOIN contractors c ON a.contractor_id = c.id
WHERE a.workflow_status IN ('submitted', 'under_review', 'pending')";

$result = $conn->query($query);

if (!$result) {
    die('Query failed: ' . $conn->error);
}

echo "=== Contractors in Workflow Status (submitted/under_review/pending) ===\n";
echo str_pad('ID', 5) . ' | ' . str_pad('Contractor ID', 15) . ' | ' . str_pad('Vendor Code', 12) . ' | ' . str_pad('Contractor Name', 30) . ' | ' . str_pad('Status', 15) . "\n";
echo str_repeat('-', 100) . "\n";

$found_contractor_2 = false;

while ($row = $result->fetch_assoc()) {
    echo str_pad($row['id'], 5) . ' | ' . 
         str_pad($row['contractor_id'], 15) . ' | ' . 
         str_pad($row['vendor_code'], 12) . ' | ' . 
         str_pad($row['contractor_name'], 30) . ' | ' . 
         str_pad($row['workflow_status'], 15) . "\n";
    
    if ($row['contractor_id'] == 2) {
        $found_contractor_2 = true;
        echo "\n>>> FOUND: Contractor ID 2 (Vendor Code: " . $row['vendor_code'] . ")\n";
        echo "    Name: " . $row['contractor_name'] . "\n";
        echo "    Status: " . $row['workflow_status'] . "\n";
    }
}

$result->close();
$conn->close();

echo "\n=== Result ===\n";
if ($found_contractor_2) {
    echo "✓ Contractor ID 2 IS VISIBLE in the welfare approval dashboard\n";
    echo "✓ The form will be displayed in approve_contractors.php\n";
} else {
    echo "✗ Contractor ID 2 NOT found in the approval queue\n";
}
?>
