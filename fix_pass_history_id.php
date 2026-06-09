<?php
/**
 * fix_pass_history_id.php
 * Fixes the pass_history table by adding AUTO_INCREMENT to the id column
 * and setting it as PRIMARY KEY
 */

include __DIR__ . '/include/config.php';

echo "Fixing pass_history table...\n";

$sql = "ALTER TABLE pass_history 
        MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
        ADD CONSTRAINT fk_pass_history_workman_id FOREIGN KEY (workman_id) REFERENCES workmen(id) ON DELETE CASCADE";

if (mysqli_query($conn, $sql)) {
    echo "✓ pass_history table fixed successfully\n";
    echo "  - id column now has AUTO_INCREMENT\n";
    echo "  - id column is now PRIMARY KEY\n";
} else {
    echo "✗ Error fixing pass_history table:\n";
    echo mysqli_error($conn) . "\n";
    exit(1);
}

// Also verify the table structure
echo "\nVerifying table structure...\n";
$result = mysqli_query($conn, "DESCRIBE pass_history");
echo "<pre>\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo sprintf("%-15s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
}
echo "</pre>\n";

echo "\n✓ Migration complete!\n";
?>
