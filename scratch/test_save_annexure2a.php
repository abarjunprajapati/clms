<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
session_start();
$_SESSION['user_id'] = 2; // Contractor user ID 2

// Mock config
include __DIR__ . '/../include/config.php';

// Prepare a mock $_POST request for SAVE DRAFT (empty optional fields/dates)
$_POST = [
    'action' => 'draft',
    'vendor_code' => 'TEST002',
    'vendor_name' => 'Test Vendor 2',
    'mobile_legacy' => '9999999999',
    'vendor_mob2' => '',
    'email' => 'vendor2@test.com',
    'address' => '123 Test St',
    'work_awarding_department' => 'IT',
    'epf_registered' => 'NO',
    'epf_code' => '',
    'esi_registered' => 'YES',
    'esi_code' => 'ESI123',
    'epf_esi_exemption_reason' => 'Small workforce',
    'wage_category' => 'Skilled',
    'ecp_number' => '', // Empty for draft
    'ecp_valid_from' => '', // Empty for draft
    'ecp_valid_to' => '', // Empty for draft
    'workers_ecp' => '0',
    'workers_proposed_to_be_engaged' => '5',
    'worker_categories' => ['Skilled'],
    'license_no' => '',
    'license_issued' => '',
    'issued_date' => '',
    'expiry_date' => '',
    'klwf_registration_no' => '',
    'labour_identification_no' => '',
    'contact_person' => 'John Doe',
    'remarks' => 'Draft saving test'
];

echo "--- RUNNING TEST 1: SAVE DRAFT ---\n";
// Change directory to api so the relative include in save_annexure2a.php works
chdir(__DIR__ . '/../api');
ob_start();
include 'save_annexure2a.php';
$response = ob_get_clean();
echo "Response: $response\n\n";

// Clear session active flag for test 2
// Prepare a mock $_POST request for SUBMIT REGISTRATION (valid data)
$_POST = [
    'action' => 'submit',
    'vendor_code' => 'TEST002',
    'vendor_name' => 'Test Vendor 2',
    'mobile_legacy' => '9999999999',
    'vendor_mob2' => '',
    'email' => 'vendor2@test.com',
    'address' => '123 Test St',
    'work_awarding_department' => 'IT',
    'epf_registered' => 'NO',
    'epf_code' => '',
    'esi_registered' => 'YES',
    'esi_code' => 'ESI123',
    'epf_esi_exemption_reason' => 'Small workforce',
    'wage_category' => 'Skilled',
    'ecp_number' => 'ECP-992-B',
    'ecp_valid_from' => '2026-05-01',
    'ecp_valid_to' => '2027-05-01',
    'workers_ecp' => '15',
    'workers_proposed_to_be_engaged' => '10',
    'worker_categories' => ['Skilled'],
    'license_no' => '', // Not mandatory since workers < 20
    'license_issued' => '',
    'issued_date' => '',
    'expiry_date' => '',
    'klwf_registration_no' => '',
    'labour_identification_no' => '',
    'contact_person' => 'John Doe',
    'remarks' => 'Submit registration test'
];

echo "--- RUNNING TEST 2: SUBMIT REGISTRATION (Workers < 20, no license) ---\n";
ob_start();
// Re-include but bypass include_once/require_once of config.php inside save_annexure2a.php (it uses include, so it's fine)
include 'save_annexure2a.php';
$response2 = ob_get_clean();
echo "Response: $response2\n\n";
