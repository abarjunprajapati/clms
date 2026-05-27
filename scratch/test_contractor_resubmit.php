<?php
include __DIR__ . '/../include/config.php';

// 1. Setup - Mock contractor 1 to be 'approved'
echo "Setting Contractor 1 to APPROVED...\n";
db_execute($conn, "UPDATE contractors SET status = 'approved' WHERE id = 1");
db_execute($conn, "UPDATE annexure2a SET workflow_status = 'approved', submitted_at = '2026-01-01 00:00:00' WHERE contractor_id = 1");

// Verify setup
$c = db_single($conn, "SELECT status FROM contractors WHERE id = 1");
$a = db_single($conn, "SELECT workflow_status, submitted_at FROM annexure2a WHERE contractor_id = 1");
echo "Current State -> Contractor Status: {$c['status']} | Annexure2A WorkflowStatus: {$a['workflow_status']} | SubmittedAt: {$a['submitted_at']}\n\n";

// 2. Simulate POST resubmission to save_annexure2a.php
$_POST = [
    'action' => 'submit',
    'vendor_code' => 'TEST002',
    'vendor_name' => 'Test Vendor 2 Updated',
    'mobile_legacy' => '9876543210',
    'email' => 'test2@test.com',
    'address' => 'Test Address 2 Updated',
    'epf_registered' => 'yes',
    'epf_code' => 'EPF12345',
    'esi_registered' => 'yes',
    'esi_code' => 'ESI12345',
    'epf_esi_exemption_reason' => 'None',
    'work_awarding_department' => 'Engineering',
    'wage_category' => 'Skilled',
    'ecp_number' => 'ECP-999',
    'ecp_valid_from' => '2026-05-01',
    'ecp_valid_to' => '2027-05-01',
    'workers_ecp' => 15,
    'workers_proposed_to_be_engaged' => 18,
    'worker_categories' => ['Skilled', 'Unskilled'],
    'license_no' => 'LIC-777',
    'license_issued' => 'yes',
    'issued_date' => '2026-05-01',
    'expiry_date' => '2027-05-01',
    'klwf_registration_no' => 'KLWF-555',
    'labour_identification_no' => '12345678',
    'contact_person' => 'John Doe',
    'remarks' => 'Resubmitted after approval',
    'selected_pos' => json_encode(['PO-111', 'PO-222'])
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock $_SESSION variables for contractor user
$_SESSION = [
    'user_id' => 2,
    'role' => 'contractor',
    'contractor_id' => 'TEST002'
];

echo "Changing directory to 'api' and simulating save_annexure2a.php execution...\n";
chdir(__DIR__ . '/../api');
ob_start();
include 'save_annexure2a.php';
$response_raw = ob_get_clean();

echo "Response from API: $response_raw\n\n";

// Go back to root directory to use config.php correctly
chdir(__DIR__ . '/..');

// 3. Verify Database Updates
$c_after = db_single($conn, "SELECT status FROM contractors WHERE id = 1");
$a_after = db_single($conn, "SELECT workflow_status, submitted_at FROM annexure2a WHERE contractor_id = 1");
echo "After Resubmission -> Contractor Status: {$c_after['status']} | Annexure2A WorkflowStatus: {$a_after['workflow_status']} | SubmittedAt: {$a_after['submitted_at']}\n\n";

// 4. Test Welfare approve_contractors query
echo "Testing Welfare Query...\n";
$pending_contractors = db_fetch_all($conn, "
    SELECT a.*, c.vendor_code, a.contractor_id as cid,
           COALESCE(a.contractor_name, c.contractor_name, c.vendor_name) as display_name
    FROM annexure2a a 
    JOIN contractors c ON a.contractor_id = c.id 
    WHERE a.workflow_status IN ('submitted', 'under_review', 'pending') 
    ORDER BY a.submitted_at DESC
");

echo "Count of pending contractors found: " . count($pending_contractors) . "\n";
foreach ($pending_contractors as $p) {
    echo "  - ID: {$p['cid']} | Name: {$p['display_name']} | Status: {$p['workflow_status']} | SubmittedAt: {$p['submitted_at']}\n";
}
