<?php
/**
 * Welfare Dashboard Debugger
 * Use this to find the exact line causing the 500 error.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Dashboard Debugger Starting...</h3>";

echo "Step 1: Including auth.php... ";
require_once __DIR__ . '/../../include/auth.php';
echo "OK<br>";

echo "Step 2: Checking Auth... ";
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
echo "OK (You are authenticated)<br>";

echo "Step 3: Including config.php... ";
include __DIR__ . '/../../include/config.php';
echo "OK<br>";

echo "Step 4: Testing DB Connection... ";
if (!$conn) {
    die("FAILED: Database connection is null!");
}
echo "OK<br>";

echo "Step 5: Testing Simple Query... ";
$res = $conn->query("SELECT 1");
if (!$res) {
    die("FAILED: Simple query failed: " . $conn->error);
}
echo "OK<br>";

echo "Step 6: Testing Dashboard Queries...<br>";

$queries = [
    "Contractors Count" => "SELECT COUNT(*)c FROM contractors",
    "Workmen Count" => "SELECT COUNT(*)c FROM workmen",
    "Users Count" => "SELECT COUNT(*)c FROM users",
    "Audit Logs Count" => "SELECT COUNT(*)c FROM audit_logs",
    "Compliance Count" => "SELECT COUNT(*)c FROM compliance",
    "Gate Pass Count" => "SELECT COUNT(*)c FROM gate_passes"
];

foreach ($queries as $label => $sql) {
    echo " - Testing $label... ";
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo "SKIPPED (Table missing or error: " . $conn->error . ")<br>";
            continue;
        }
        $stmt->execute();
        echo "OK<br>";
        $stmt->close();
    } catch (Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "<br>";
    }
}

echo "Step 7: Including layout.php... ";
include __DIR__ . '/../../include/layout.php';
echo "OK<br>";

echo "<h4>Debug Finished. If you reached here, the dashboard should work.</h4>";
echo "<a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
?>
