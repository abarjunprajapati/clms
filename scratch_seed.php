<?php
require_once 'include/config.php';

// Seed annexure2a
$conn->query("INSERT IGNORE INTO annexure2a (application_id, contractor_name, workflow_status) 
              VALUES ('APP-1', 'Test Contractor', 'temporary_pass_issued')");

// Seed applications
$conn->query("INSERT IGNORE INTO applications (application_no, contractor_id, current_status) 
              VALUES ('APP-1', 1, 'temporary_pass_issued')");

// Seed application_workflow
$conn->query("INSERT IGNORE INTO application_workflow (application_id, current_stage, overall_status) 
              VALUES ('APP-1', 'temporary_pass_issued', 'temporary_pass_issued') 
              ON DUPLICATE KEY UPDATE current_stage='temporary_pass_issued', overall_status='temporary_pass_issued'");

echo "Seeded APP-1 into annexure2a, applications, and application_workflow.\n";
?>
