<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../include/config.php';

echo "<h2>Database Test 3</h2>";

try {
    $search_term = "121221212121"; // The one the user entered
    $requesting_contractor_id = 1; // Dummy contractor ID for test
    
    $stmt = $conn->prepare("SELECT id, contractor_id, is_in_common_pool FROM workmen WHERE aadhaar = ? OR temp_id = ?");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $workman = $res->fetch_assoc();
        $workman_id = $workman['id'];
        $existing_contractor_id = $workman['contractor_id'];
        $status = 'pending';
        
        $conn->begin_transaction();
        
        echo "Testing INSERT execute...<br>";
        $insert = $conn->prepare("INSERT INTO noc_requests (workman_id, to_contractor_id, from_contractor_id, noc_status) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiis", $workman_id, $requesting_contractor_id, $existing_contractor_id, $status);
        
        if ($insert->execute()) {
            echo "<h3 style='color:green;'>INSERT executed successfully!</h3>";
        } else {
            echo "<h3 style='color:red;'>INSERT execute FAILED: " . $insert->error . "</h3>";
        }
        
        $conn->rollback();
        echo "Transaction rolled back safely.<br>";
    } else {
        echo "Workman not found.";
    }
    
} catch (\Throwable $e) {
    echo "<h3 style='color:red;'>Exception Caught:</h3>";
    echo $e->getMessage();
}
