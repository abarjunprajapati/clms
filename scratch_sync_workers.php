<?php
require_once 'include/config.php';

echo "Syncing worker statuses...\n";

// 1. If has acc_number, status should be acc_generated (or permanent_active)
$res1 = $conn->query("UPDATE workmen SET status = 'acc_generated' 
                      WHERE (acc_number IS NOT NULL AND acc_number != '') 
                      AND status NOT IN ('permanent_active', 'blocked')");
echo "Updated " . $conn->affected_rows . " workers to acc_generated.\n";

// 2. If has temp_id but no acc_number, status should be temporary_issued
$res2 = $conn->query("UPDATE workmen SET status = 'temporary_issued' 
                      WHERE (temp_id IS NOT NULL AND temp_id != '' AND temp_id != 'Pending') 
                      AND (acc_number IS NULL OR acc_number = '')
                      AND status NOT IN ('acc_generated', 'permanent_active', 'blocked')");
echo "Updated " . $conn->affected_rows . " workers to temporary_issued.\n";

// 3. Update application status if needed
$apps = $conn->query("SELECT DISTINCT application_no FROM workmen");
while($row = $apps->fetch_assoc()) {
    $app = $row['application_no'];
    // Check if any worker in this app has ACC
    $hasAcc = $conn->query("SELECT 1 FROM workmen WHERE application_no = '$app' AND (acc_number IS NOT NULL AND acc_number != '') LIMIT 1")->num_rows > 0;
    if ($hasAcc) {
        $conn->query("UPDATE application_workflow SET overall_status = 'acc_generated' WHERE application_id = '$app'");
        $conn->query("UPDATE annexure2a SET workflow_status = 'acc_generated' WHERE application_id = '$app'");
    }
}

echo "Sync complete.\n";
?>
