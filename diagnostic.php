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

echo "=== STEP 1: Contractors with vendor_code = 1100908 ===\n";
$query1 = "SELECT id, vendor_code, vendor_name, contractor_name, status, created_at FROM contractors WHERE vendor_code = '1100908'";
$stmt1 = $pdo->query($query1);
$contractors = $stmt1->fetchAll(PDO::FETCH_ASSOC);

if (empty($contractors)) {
    echo "No contractors found with vendor_code 1100908\n\n";
} else {
    echo "Found " . count($contractors) . " contractor(s):\n";
    foreach ($contractors as $row) {
        echo "  ID: " . $row['id'] . "\n";
        echo "  Vendor Code: " . $row['vendor_code'] . "\n";
        echo "  Vendor Name: " . $row['vendor_name'] . "\n";
        echo "  Contractor Name: " . $row['contractor_name'] . "\n";
        echo "  Status: " . $row['status'] . "\n";
        echo "  Created At: " . $row['created_at'] . "\n";
        echo "  ---\n";
    }
}

$contractorIds = array_column($contractors, 'id');
if (empty($contractorIds)) {
    echo "\nNo contractor IDs to search in annexure2a\n";
    exit;
}

echo "\n=== STEP 2: Annexure2a records for these contractors ===\n";
$idList = implode(', ', $contractorIds);
echo "Searching for annexure2a records with contractor_ids: " . $idList . "\n\n";

$placeholders = implode(',', array_fill(0, count($contractorIds), '?'));
$query2 = "SELECT contractor_id, contractor_name, workflow_status, submitted_at FROM annexure2a WHERE contractor_id IN ($placeholders)";
$stmt2 = $pdo->prepare($query2);
$stmt2->execute($contractorIds);
$annexures = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if (empty($annexures)) {
    echo "No annexure2a records found for these contractors\n\n";
} else {
    echo "Found " . count($annexures) . " annexure2a record(s):\n";
    foreach ($annexures as $row) {
        echo "  Contractor ID: " . $row['contractor_id'] . "\n";
        echo "  Contractor Name: " . $row['contractor_name'] . "\n";
        echo "  Workflow Status: " . $row['workflow_status'] . "\n";
        echo "  Submitted At: " . $row['submitted_at'] . "\n";
        echo "  ---\n";
    }
}

echo "\n=== STEP 3: Checking name consistency ===\n";
$contractorNameMap = array_combine(array_column($contractors, 'id'), array_column($contractors, 'contractor_name'));

$mismatches = 0;
foreach ($annexures as $annexure) {
    $contractorId = $annexure['contractor_id'];
    $annexureContractorName = $annexure['contractor_name'];
    $contractorsTableName = $contractorNameMap[$contractorId] ?? null;
    
    if ($contractorsTableName !== $annexureContractorName) {
        $mismatches++;
        echo "MISMATCH for Contractor ID " . $contractorId . ":\n";
        echo "  Contractors table: '" . $contractorsTableName . "'\n";
        echo "  Annexure2a table:  '" . $annexureContractorName . "'\n";
        echo "  ---\n";
    }
}

if ($mismatches === 0) {
    echo "All contractor names match between tables!\n";
} else {
    echo "\nFound " . $mismatches . " name mismatch(es)\n";
}

echo "\n=== Summary ===\n";
echo "Total contractors with vendor_code 1100908: " . count($contractors) . "\n";
echo "Total annexure2a records: " . count($annexures) . "\n";
echo "Name mismatches: " . $mismatches . "\n";
?>
