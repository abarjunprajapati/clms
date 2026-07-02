<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../include/config.php';

echo "<h2>Database Test 2</h2>";

try {
    $search_term = "121221212121"; // The one the user entered
    
    echo "1. Querying workmen table...<br>";
    $stmt = $conn->prepare("SELECT id, contractor_id, is_in_common_pool FROM workmen WHERE aadhaar = ? OR temp_id = ?");
    if (!$stmt) {
        die("<b>Failed to prepare workmen query:</b> " . $conn->error);
    }
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $res = $stmt->get_result();
    echo "Found " . $res->num_rows . " workmen.<br>";
    
    if ($res->num_rows > 0) {
        $workman = $res->fetch_assoc();
        $workman_id = $workman['id'];
        
        echo "2. Querying noc_requests for existing...<br>";
        $chk = $conn->prepare("SELECT id FROM noc_requests WHERE workman_id = ? AND noc_status IN ('pending', 'approved_by_contractor')");
        if (!$chk) {
            die("<b>Failed to prepare noc_requests SELECT:</b> " . $conn->error);
        }
        $chk->bind_param("i", $workman_id);
        $chk->execute();
        echo "Found " . $chk->get_result()->num_rows . " existing requests.<br>";
        
        echo "3. Testing INSERT prepare...<br>";
        $insert = $conn->prepare("INSERT INTO noc_requests (workman_id, to_contractor_id, from_contractor_id, noc_status) VALUES (?, ?, ?, ?)");
        if (!$insert) {
            die("<b>Failed to prepare noc_requests INSERT:</b> " . $conn->error);
        }
        echo "INSERT prepared successfully.<br>";
    }
    
    echo "<h3 style='color:green;'>All queries prepared successfully!</h3>";
    
} catch (\Throwable $e) {
    echo "<h3 style='color:red;'>Exception Caught:</h3>";
    echo $e->getMessage();
}
