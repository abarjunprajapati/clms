<?php
/**
 * Migration: Add Annexure 2A Fields to customer_annexure3a Table
 * This adds all the registration-related columns to support complete form
 */

require_once __DIR__ . '/include/config.php';

$sql_file = __DIR__ . '/migration_add_annexure2a_fields_to_customer.sql';

if (!file_exists($sql_file)) {
    die("Migration SQL file not found: $sql_file\n");
}

echo "Running migration: Adding Annexure 2A fields to customer_annexure3a...\n\n";

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

echo "\n" . str_repeat("=", 50) . "\n";
echo "Migration Results:\n";
echo "Executed: $executed statements\n";
echo "Failed: $failed statements\n";

if ($failed === 0) {
    echo "\n✓ Migration completed successfully!\n";
    echo "✓ All Annexure 2A fields are now available in customer_annexure3a table\n";
} else {
    echo "\n✗ Migration completed with errors.\n";
}
