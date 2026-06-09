<?php
session_start();
require_once 'include/config.php';

// Set up test session
$_SESSION['contractor_id'] = 1;
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';

$testData = [
    'vendor_code' => 'VEN-2024-001',
    'contractor_name' => 'Test Construction Ltd',
    'contractor_type' => 'Company',
    'address' => '123 Test Street, Test City',
    'state' => 'Haryana',
    'district' => 'Gurugram',
    'pin' => '122001',
    'contact_person' => 'John Doe',
    'mobile' => '9876543210',
    'email' => 'test@example.com',
    'pan' => 'ABCDE1234F',
    'gst' => '07ABCDE1234F1Z5',
    'esic' => '41000429350',
    'pf' => 'DL/40523/ENF/EMP/0001',
    'license_no' => 'LL/HRY/2024/0112',
    'max_workmen' => '50',
    'valid_from' => '2024-01-01',
    'valid_to' => '2025-12-31',
    'nature_of_work' => 'Civil',
    'department' => 'Construction',
    'bank_name' => 'State Bank of India',
    'account_number' => '1234567890',
    'ifsc' => 'SBIN0001234',
    'branch_name' => 'Gurugram Branch',
    'contract_no' => 'WO/2024/PWD/0423',
    'project_name' => 'Test Project',
    'work_location' => 'Test Location',
    'category_work' => 'Civil Construction',
    'contract_value' => '1000000.00',
    'contract_start' => '2024-01-01',
    'contract_end' => '2024-12-31'
];

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set GET parameters for testing
$_GET = $testData;
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Sending data: " . json_encode($testData) . "\n";

ob_start();
include 'api/save_annexure2a.php';
$output = ob_get_clean();

echo "Response: " . $output . "\n";
?>
