<?php
/**
 * ANNEXURE 5/A Integration Guide
 * 
 * यह file दिखाता है कि pass limit validation को अपने API में कैसे integrate करें
 */

// ============ BACKEND INTEGRATION ============

// 📍 IN YOUR API ENDPOINT (e.g., generate_pass.php)

/*

require_once __DIR__ . '/../include/pass_limit_validator.php';

// After getting contractor_id and pass_type...

try {
    // Single validation call
    $result = validatePassLimit(
        $conn,
        $contractor_id,      // INTEGER
        'Supervisor',        // Pass type
        2,                   // How many are being added?
        false                // Is welfare admin override?
    );

    // $result = [
    //     'valid' => true/false,
    //     'current' => 1,
    //     'allowed' => 3,
    //     'rule' => '1 per 10 workmen (Workmen: 25, Ratio: 1:10)',
    //     'override' => true,
    //     'pass_type' => 'Supervisor'
    // ]

    if (!$result['valid']) {
        throw new Exception("Annexure 5/A validation failed: " . $result['rule']);
    }

    // Proceed with pass generation...

} catch (Exception $e) {
    apiError("Pass Limit Error: " . $e->getMessage(), 400);
}

*/

// ============ USE CASES & INTEGRATION POINTS ============

/*

📍 1. DURING PASS GENERATION
   File: api/generate_pass.php
   Function: validatePassLimit($conn, $contractor_id, 'Workman', $workmen_count)
   
📍 2. DURING SUPERVISOR ENROLLMENT
   File: api/save_annexure3a.php
   Function: validatePassLimit($conn, $contractor_id, 'Supervisor', $added_count)
   
📍 3. DURING REPRESENTATIVE ASSIGNMENT
   File: api/enrol_representative.php
   Function: validatePassLimit($conn, $contractor_id, 'Representative', 1)
   
📍 4. OVERRIDE BY WELFARE ADMIN
   File: api/welfare/override_pass_limit.php
   Function: validatePassLimit($conn, $contractor_id, $pass_type, $count, true)

*/

// ============ DATABASE VERIFICATION ============

require_once __DIR__ . '/../../include/config.php';

echo "====== ANNEXURE 5/A - INTEGRATION VERIFICATION ======\n\n";

// Check if pass_limits table exists
$check_table = "SELECT 1 FROM pass_limits LIMIT 1";
if (clms_db_query($conn, $check_table)) {
    echo "✅ Table 'pass_limits' exists\n";
    
    // Get all rules
    $rules = clms_db_query($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0");
    echo "\n📋 Default Rules in Database:\n";
    
    while ($row = clms_db_fetch_assoc($rules)) {
        echo sprintf(
            "  • %-15s → Max: %-5s | Ratio: %-3s | Rule: %s\n",
            $row['pass_type'],
            $row['max_allowed'] ?? 'Unlimited',
            $row['ratio_per_workmen'] ?? 'N/A',
            $row['rule']
        );
    }
} else {
    echo "❌ Table 'pass_limits' NOT FOUND\n";
    echo "   Run: php api/annexure5a/init_pass_limits.php\n";
}

// ============ QUICK TEST ============

echo "\n🧪 Quick Test:\n";

require_once __DIR__ . '/../../include/pass_limit_validator.php';

// Test 1: Check supervisor limit for 35 workmen
$test1 = calculateAllowed($conn, 1, 'Supervisor');
echo "  Supervisor (35 workmen): Allowed = " . $test1['allowed'] . " | Rule: " . $test1['rule'] . "\n";

// Test 2: Check contractor limit
$test2 = calculateAllowed($conn, 1, 'Contractor');
echo "  Contractor: Allowed = " . $test2['allowed'] . " | Rule: " . $test2['rule'] . "\n";

// Test 3: Check representative limit
$test3 = calculateAllowed($conn, 1, 'Representative');
echo "  Representative: Allowed = " . $test3['allowed'] . " | Rule: " . $test3['rule'] . "\n";

echo "\n✅ Integration verification complete!\n";

?>

