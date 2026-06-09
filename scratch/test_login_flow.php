<?php
require_once __DIR__ . '/../include/config.php';

echo "=== CONTRACTOR LOGIN FLOW TEST ===\n\n";

function test_regex($username) {
    $is_contractor = preg_match('/^[0-9]{5,7}$/', $username);
    echo "Testing '$username': " . ($is_contractor ? "CONTRACTOR" : "INTERNAL") . "\n";
}

test_regex("55065");
test_regex("admin");
test_regex("5506500");
test_regex("user123");

echo "\n=== TESTING OTP ROUTING ===\n";

$test_code = '55065';
$sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$test_code]);

if ($sap) {
    echo "Found Customer: " . $sap['customer_name'] . "\n";
    $mobile = $sap['Customer_MOB1'] ?: $sap['mobile'] ?: '';
    $email = $sap['EMAIL_ADDRESS'] ?: $sap['email'] ?: '';
    
    if (!empty($mobile)) {
        echo "Priority 1 (Mobile) Found: $mobile -> USE SMS\n";
    } elseif (!empty($email)) {
        echo "Priority 2 (Email) Found: $email -> USE EMAIL\n";
    } else {
        echo "No contact info -> BLOCK\n";
    }
} else {
    echo "Customer $test_code not found!\n";
}

echo "\n=== TESTING LOGIN AUTH (MOCK) ===\n";
$test_pass = 'password123';
$hashed = password_hash($test_pass, PASSWORD_BCRYPT);
echo "Password: $test_pass\n";
echo "Hashed: $hashed\n";
echo "Verify Correct: " . (password_verify($test_pass, $hashed) ? "YES" : "NO") . "\n";
echo "Verify Wrong: " . (password_verify('wrong', $hashed) ? "YES" : "NO") . "\n";

?>
