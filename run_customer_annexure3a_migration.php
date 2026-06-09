<?php
/**
 * Migration: Create customer_annexure3a table
 * This creates the customer registration/deployment form table
 */

require_once __DIR__ . '/include/config.php';

$sql_file = __DIR__ . '/migration_create_customer_annexure3a.sql';

if (!file_exists($sql_file)) {
    die("Migration SQL file not found: $sql_file\n");
}

$sql_content = file_get_contents($sql_file);
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

$executed = 0;
$failed = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    if ($conn->multi_query($statement)) {
        while ($conn->more_results()) {
            $conn->next_result();
        }
        $executed++;
        echo "✓ Executed: " . substr($statement, 0, 80) . "...\n";
    } else {
        $failed++;
        echo "✗ Failed: " . $conn->error . "\n";
        echo "  Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n=== Migration Results ===\n";
echo "Executed: $executed\n";
echo "Failed: $failed\n";

if ($failed === 0) {
    echo "\n✓ Migration completed successfully!\n";
} else {
    echo "\n✗ Migration completed with errors.\n";
}
