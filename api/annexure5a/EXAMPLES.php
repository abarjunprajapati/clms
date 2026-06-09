<?php
/**
 * ANNEXURE 5/A - Practical Examples
 * 
 * Real-world usage examples for different scenarios
 */

echo "====== ANNEXURE 5/A - PRACTICAL EXAMPLES ======\n\n";

require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/pass_limit_validator.php';

// ============ EXAMPLE 1: Check Supervisor Limit ============
echo "📌 EXAMPLE 1: Checking Supervisor Limit\n";
echo "─" . str_repeat("─", 60) . "\n";

$contractor_id = 1;
$workmen_in_system = 35;

// Scenario: We want to add 2 supervisors
$requested_supervisors = 2;

echo "Scenario: Adding $requested_supervisors supervisors for $workmen_in_system workmen\n\n";

try {
    $result = validatePassLimit(
        $conn,
        $contractor_id,
        'Supervisor',
        $requested_supervisors,
        false  // Not a welfare override
    );

    echo "Result:\n";
    echo "  ✅ Valid: " . ($result['valid'] ? 'YES' : 'NO') . "\n";
    echo "  📊 Current Count: " . $result['current'] . "\n";
    echo "  📈 Allowed: " . $result['allowed'] . "\n";
    echo "  📝 Rule: " . $result['rule'] . "\n";
    echo "  💬 Message: " . $result['allowed'] . " supervisors allowed (after adding " . $requested_supervisors . ", total: " . ($result['current'] + $requested_supervisors) . ")\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ EXAMPLE 2: Exceeding Limit (Error Case) ============
echo "\n📌 EXAMPLE 2: Attempting to Exceed Limit\n";
echo "─" . str_repeat("─", 60) . "\n";

$requested_supervisors = 5;

echo "Scenario: Trying to add $requested_supervisors supervisors for $workmen_in_system workmen\n";
echo "(Allowed = 1 + floor(35/10) = 4, Current = 1, So max 3 more can be added)\n\n";

try {
    $result = validatePassLimit(
        $conn,
        $contractor_id,
        'Supervisor',
        $requested_supervisors,
        false
    );

    echo "Result: " . ($result['valid'] ? '✅ VALID' : '❌ INVALID') . "\n";

} catch (Exception $e) {
    echo "⚠️  Validation Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ EXAMPLE 3: Representative Limit (Strict) ============
echo "\n📌 EXAMPLE 3: Representative Limit (Strict)\n";
echo "─" . str_repeat("─", 60) . "\n";

echo "Scenario: Contractor wants to add a 2nd representative\n";
echo "(Rule: Only 1 representative per firm - NO EXCEPTIONS)\n\n";

try {
    $result = validatePassLimit(
        $conn,
        $contractor_id,
        'Representative',
        1,   // Adding 1 representative
        false
    );

    echo "Result: " . ($result['valid'] ? '✅ VALID' : '❌ INVALID') . "\n";
    echo "Current Representatives: " . $result['current'] . "\n";
    echo "Allowed: " . $result['allowed'] . "\n";

} catch (Exception $e) {
    echo "⚠️  Blocked: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ EXAMPLE 4: Contractor Limit ============
echo "\n📌 EXAMPLE 4: Contractor Registration Limit\n";
echo "─" . str_repeat("─", 60) . "\n";

echo "Scenario: Adding contractors (Max 2 per firm)\n\n";

try {
    $result = validatePassLimit(
        $conn,
        $contractor_id,
        'Contractor',
        1,   // Adding 1 contractor
        false
    );

    echo "Current Contractors: " . $result['current'] . "\n";
    echo "Allowed: " . $result['allowed'] . "\n";
    echo "Can add more: " . ($result['valid'] ? '✅ YES' : '❌ NO') . "\n";

} catch (Exception $e) {
    echo "⚠️  Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ EXAMPLE 5: Welfare Override ============
echo "\n📌 EXAMPLE 5: Welfare Admin Override\n";
echo "─" . str_repeat("─", 60) . "\n";

echo "Scenario: Exceeded supervisor limit, but Welfare Admin wants to override\n\n";

try {
    $result = validatePassLimit(
        $conn,
        $contractor_id,
        'Supervisor',
        5,   // Exceeds limit
        true  // ← Welfare override enabled
    );

    echo "Result: " . ($result['valid'] ? '✅ ALLOWED (Override)' : '❌ BLOCKED') . "\n";
    if (isset($result['overridden'])) {
        echo "Status: Overridden by Welfare Admin\n";
    }

} catch (Exception $e) {
    echo "❌ Override rejected: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ EXAMPLE 6: Workman (No Limit) ============
echo "\n📌 EXAMPLE 6: Workman Enrollment (No Fixed Limit)\n";
echo "─" . str_repeat("─", 60) . "\n";

echo "Scenario: Adding 100 workmen to the system\n";
echo "(Rule: No fixed limit - depends on work order)\n\n";

try {
    $result = validatePassLimit(
        $conn,
        $contractor_id,
        'Workman',
        100,  // Adding 100 workmen
        false
    );

    echo "Result: " . ($result['valid'] ? '✅ VALID' : '❌ INVALID') . "\n";
    echo "Allowed: " . ($result['allowed'] ?? 'Unlimited') . "\n";
    echo "Message: " . $result['rule'] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============ EXAMPLE 7: Dynamic Supervisor Calculation ============
echo "\n📌 EXAMPLE 7: Supervisor Limit for Different Workmen Counts\n";
echo "─" . str_repeat("─", 60) . "\n";

$workmen_scenarios = [10, 15, 20, 25, 30, 35, 40, 50, 100];

echo "Workmen Count → Supervisors Allowed (1 per 10 + 1)\n";
echo "─" . str_repeat("─", 40) . "\n";

foreach ($workmen_scenarios as $wm) {
    // Simulate having wm workmen by calculating directly
    $allowed = 1 + floor($wm / 10);
    echo sprintf("  %3d workmen → %2d supervisors\n", $wm, $allowed);
}

echo "\n";

// ============ EXAMPLE 8: Summary Report ============
echo "\n📌 EXAMPLE 8: Pass Limits Summary Report\n";
echo "─" . str_repeat("─", 60) . "\n";

$limits = db_fetch_all($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0");

echo "Type             │ Max Allowed │ Ratio  │ Rule    │ Override\n";
echo "─" . str_repeat("─", 57) . "\n";

foreach ($limits as $limit) {
    printf(
        "%-16s │ %-11s │ %-6s │ %-7s │ %s\n",
        $limit['pass_type'],
        $limit['max_allowed'] ?? 'Unlimited',
        $limit['ratio_per_workmen'] ?? 'N/A',
        $limit['rule'],
        $limit['override_allowed'] ? 'Yes' : 'No'
    );
}

echo "\n✅ All examples completed!\n";

?>

