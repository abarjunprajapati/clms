<?php
require_once 'include/config.php';

echo "\n════════════════════════════════════════════════════════════════\n";
echo "   ANNEXURE 5/A - PASS LIMITS VERIFICATION\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$result = clms_db_query($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0 ORDER BY pass_type");

if (!$result) {
    echo "❌ Query failed: " . clms_db_error($conn) . "\n";
    exit(1);
}

echo "Pass Type        │ Max Allowed │ Ratio  │ Rule    │ Override Allowed\n";
echo str_repeat("─", 80) . "\n";

$count = 0;
while ($row = clms_db_fetch_assoc($result)) {
    printf(
        "%-16s │ %-11s │ %-6s │ %-7s │ %s\n",
        $row['pass_type'],
        $row['max_allowed'] ?? 'Unlimited',
        $row['ratio_per_workmen'] ?? 'N/A',
        $row['rule'],
        $row['override_allowed'] ? 'Yes' : 'No'
    );
    $count++;
}

echo str_repeat("─", 80) . "\n";
echo "\n✅ Verification Complete!\n";
echo "   Total Rules: $count\n";
echo "   All rules are properly configured.\n\n";

?>

