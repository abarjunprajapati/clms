<?php
$host = "127.0.0.1";
$db = "new_clms";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to $db database\n\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "=== ALL CONTRACTORS IN DATABASE ===\n";
$query = "SELECT id, vendor_code, vendor_name, contractor_name, status, created_at, user_id 
          FROM contractors 
          ORDER BY id ASC";

$stmt = $pdo->query($query);
$contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($contractors)) {
    echo "No contractors found in database\n\n";
} else {
    echo "Total contractors: " . count($contractors) . "\n";
    echo str_repeat("-", 150) . "\n";
    echo sprintf("%-5s | %-15s | %-20s | %-30s | %-10s | %-19s | %-8s\n", 
        "ID", "Vendor Code", "Vendor Name", "Contractor Name", "Status", "Created At", "User ID");
    echo str_repeat("-", 150) . "\n";
    
    foreach ($contractors as $row) {
        echo sprintf("%-5s | %-15s | %-20s | %-30s | %-10s | %-19s | %-8s\n",
            $row['id'] ?? "NULL",
            $row['vendor_code'] ?? "NULL",
            substr($row['vendor_name'] ?? "NULL", 0, 20),
            substr($row['contractor_name'] ?? "NULL", 0, 30),
            $row['status'] ?? "NULL",
            $row['created_at'] ?? "NULL",
            $row['user_id'] ?? "NULL"
        );
    }
    
    echo str_repeat("-", 150) . "\n";
    echo "\nSummary:\n";
    echo "Total contractors: " . count($contractors) . "\n";
    
    // Check for duplicates in vendor_code
    $vendorCodes = array_column($contractors, 'vendor_code');
    $duplicateVendorCodes = array_diff_key($vendorCodes, array_unique($vendorCodes));
    if (!empty($duplicateVendorCodes)) {
        echo "Duplicate vendor codes found: " . count(array_unique($duplicateVendorCodes)) . "\n";
    }
    
    // Check for duplicates in contractor_name
    $names = array_column($contractors, 'contractor_name');
    $duplicateNames = array_diff_key($names, array_unique($names));
    if (!empty($duplicateNames)) {
        echo "Duplicate contractor names found: " . count(array_unique($duplicateNames)) . "\n";
    }
}
?>
