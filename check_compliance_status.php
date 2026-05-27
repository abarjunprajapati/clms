<?php
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/compliance_schema.php';

header('Content-Type: text/plain; charset=utf-8');

echo "CLMS COMPLIANCE LIVE DIAGNOSTIC\n";
echo "==============================\n\n";

// 1. Check database connection
if (isset($conn) && $conn) {
    echo "[OK] Database Connection is ACTIVE.\n";
} else {
    echo "[ERROR] Database Connection is INACTIVE!\n";
    exit;
}

// 2. Check Directory Permissions
$uploadsDir = __DIR__ . '/uploads';
$complianceDir = __DIR__ . '/uploads/compliance';

echo "\n2. DIRECTORY PERMISSIONS:\n";
echo "  Uploads root dir: $uploadsDir\n";
if (is_dir($uploadsDir)) {
    echo "    - Exists: YES\n";
    echo "    - Writable: " . (is_writable($uploadsDir) ? "YES" : "NO (PERMISSION DENIED)") . "\n";
} else {
    echo "    - Exists: NO\n";
    // Try to create it
    if (@mkdir($uploadsDir, 0777, true)) {
        echo "    - Created successfully: YES\n";
    } else {
        echo "    - Created successfully: NO (PERMISSION DENIED)\n";
    }
}

echo "  Compliance upload dir: $complianceDir\n";
if (is_dir($complianceDir)) {
    echo "    - Exists: YES\n";
    echo "    - Writable: " . (is_writable($complianceDir) ? "YES" : "NO (PERMISSION DENIED)") . "\n";
} else {
    echo "    - Exists: NO\n";
    if (@mkdir($complianceDir, 0777, true)) {
        echo "    - Created successfully: YES\n";
        echo "    - Writable: " . (is_writable($complianceDir) ? "YES" : "NO (PERMISSION DENIED)") . "\n";
    } else {
        echo "    - Created successfully: NO (PERMISSION DENIED)\n";
    }
}

// 3. Run Schema Sync Verification
echo "\n3. SCHEMA SYNC VERIFICATION:\n";
try {
    ensureComplianceSchema($conn);
    echo "  [OK] ensureComplianceSchema executed successfully on live database!\n";
} catch (Throwable $e) {
    echo "  [ERROR] Schema sync failed: " . $e->getMessage() . "\n";
}

// 4. Inspect DB Tables Structure
echo "\n4. COMPLIANCE TABLES STRUCTURE:\n";
$tables = ['compliance', 'compliance_esi', 'compliance_epf', 'compliance_klwf', 'compliance_logs', 'wages', 'workmen', 'contractors'];
foreach ($tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res && $res->num_rows > 0) {
        echo "  [OK] Table '$table' exists.\n";
        // Show columns
        $cols = $conn->query("SHOW COLUMNS FROM `$table`");
        while ($col = $cols->fetch_assoc()) {
            echo "    - {$col['Field']} | {$col['Type']} | Null: {$col['Null']} | Key: {$col['Key']} | Default: {$col['Default']} | Extra: {$col['Extra']}\n";
        }
    } else {
        echo "  [MISSING] Table '$table' DOES NOT EXIST!\n";
    }
    echo "\n";
}

// 5. Test db_count / db_single wrappers to see if they fail or throw error
echo "5. TESTING WRAPPERS:\n";
try {
    $wc = db_count($conn, "SELECT COUNT(*) FROM workmen");
    echo "  - db_count (workmen) count: $wc\n";
    $cc = db_count($conn, "SELECT COUNT(*) FROM contractors");
    echo "  - db_count (contractors) count: $cc\n";
} catch (Throwable $e) {
    echo "  [ERROR] Wrapper testing failed: " . $e->getMessage() . "\n";
}

?>
