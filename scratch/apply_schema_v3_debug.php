<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect("localhost", "root", "", "new_clms");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$sql_content = file_get_contents(__DIR__ . "/../sql/enterprise_governance_v3.sql");

// Split by semicolon and execute one by one for better error tracking
$queries = explode(";", $sql_content);
$success_count = 0;
$error_count = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if (mysqli_query($conn, $query)) {
        $success_count++;
    } else {
        echo "ERROR in query: " . $query . "\n";
        echo "REASON: " . mysqli_error($conn) . "\n\n";
        $error_count++;
    }
}

echo "Total Successful Queries: $success_count\n";
echo "Total Failed Queries: $error_count\n";

if ($error_count == 0) {
    echo "SUCCESS: All schema updates applied successfully.\n";
} else {
    echo "COMPLETED with errors. Please check above.\n";
}
?>
