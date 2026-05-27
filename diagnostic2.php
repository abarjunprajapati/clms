<?php
$host = '127.0.0.1';
$db = 'new_clms';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to $db database\n\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "=== STEP 1: Contractors with NULL or empty vendor_code ===\n";
$query1 = "SELECT id, contractor_name, vendor_name FROM contractors WHERE vendor_code IS NULL OR vendor_code = ''";
$stmt1 = $pdo->query($query1);
$nullVendorContractors = $stmt1->fetchAll(PDO::FETCH_ASSOC);

if (empty($nullVendorContractors)) {
    echo "No contractors found with NULL or empty vendor_code\n\n";
} else {
    echo "Found " . count($nullVendorContractors) . " contractor(s) with NULL/empty vendor_code:\n";
    foreach ($nullVendorContractors as $row) {
        echo "  ID: " . $row['id'] . " | Name: " . $row['contractor_name'] . " | Vendor: " . $row['vendor_name'] . "\n";
    }
    echo "\n";
}

echo "=== STEP 2: Contractors NOT linked to any annexure2a record ===\n";
$query2 = "SELECT c.id, c.vendor_code, c.vendor_name, c.contractor_name 
           FROM contractors c 
           LEFT JOIN annexure2a a ON c.id = a.contractor_id 
           WHERE a.contractor_id IS NULL";
$stmt2 = $pdo->query($query2);
$unlinkedContractors = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if (empty($unlinkedContractors)) {
    echo "All contractors are linked to at least one annexure2a record\n\n";
} else {
    echo "Found " . count($unlinkedContractors) . " contractor(s) NOT linked to annexure2a:\n";
    foreach ($unlinkedContractors as $row) {
        echo "  ID: " . $row['id'] . " | Vendor Code: " . $row['vendor_code'] . " | Vendor: " . $row['vendor_name'] . " | Name: " . $row['contractor_name'] . "\n";
    }
    echo "\n";
}

echo "=== STEP 3: Count of annexure2a records and their contractor_ids ===\n";
$query3 = "SELECT COUNT(*) as total_count FROM annexure2a";
$stmt3 = $pdo->query($query3);
$countResult = $stmt3->fetch(PDO::FETCH_ASSOC);
echo "Total annexure2a records: " . $countResult['total_count'] . "\n";

$query3b = "SELECT DISTINCT contractor_id FROM annexure2a ORDER BY contractor_id";
$stmt3b = $pdo->query($query3b);
$contractorIds = $stmt3b->fetchAll(PDO::FETCH_ASSOC);
if (empty($contractorIds)) {
    echo "No contractor IDs in annexure2a records\n\n";
} else {
    echo "Contractor IDs in annexure2a: ";
    echo implode(', ', array_column($contractorIds, 'contractor_id'));
    echo "\n\n";
}

echo "=== STEP 4: Annexure2a records in 'draft' or 'submitted' status ===\n";
$query4 = "SELECT id, contractor_id, contractor_name, workflow_status 
           FROM annexure2a 
           WHERE workflow_status IN ('draft', 'submitted')
           ORDER BY contractor_id, id";
$stmt4 = $pdo->query($query4);
$draftSubmittedRecords = $stmt4->fetchAll(PDO::FETCH_ASSOC);

if (empty($draftSubmittedRecords)) {
    echo "No annexure2a records in 'draft' or 'submitted' status\n\n";
} else {
    echo "Found " . count($draftSubmittedRecords) . " annexure2a record(s) in draft/submitted status:\n";
    foreach ($draftSubmittedRecords as $row) {
        echo "  ID: " . $row['id'] . " | Contractor ID: " . $row['contractor_id'] . " | Name: " . $row['contractor_name'] . " | Status: " . $row['workflow_status'] . "\n";
    }
    echo "\n";
}

echo "=== Summary ===\n";
echo "Contractors with NULL/empty vendor_code: " . count($nullVendorContractors) . "\n";
echo "Contractors not linked to annexure2a: " . count($unlinkedContractors) . "\n";
echo "Total annexure2a records: " . $countResult['total_count'] . "\n";
echo "Annexure2a records in draft/submitted status: " . count($draftSubmittedRecords) . "\n";
?>
