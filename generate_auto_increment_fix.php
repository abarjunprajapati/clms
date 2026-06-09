<?php
require_once 'include/config.php';

header('Content-Type: text/plain');

$sql = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'id' AND EXTRA NOT LIKE '%auto_increment%'";
$result = db_fetch_all($conn, $sql);

$output = "CLMS Database Auto-Increment Fix:\n============================\n\n";
$sql_commands = "";

foreach($result as $row) {
    $table = $row['TABLE_NAME'];
    $col = $row['COLUMN_NAME'];
    $dataType = strtoupper($row['DATA_TYPE']);
    
    // Use correct data type (int, bigint, etc.)
    $typeStr = ($dataType === 'BIGINT') ? 'BIGINT(20)' : 'INT(11)';
    
    $alter = "ALTER TABLE `$table` MODIFY `$col` $typeStr NOT NULL AUTO_INCREMENT;";
    $output .= "Table '$table': Missing auto_increment on '$col'. Fixing...\n";
    
    // Execute locally
    $ok = db_execute($conn, $alter);
    if ($ok) {
        $output .= "  -> SUCCESS\n";
        $sql_commands .= $alter . "\n";
    } else {
        $output .= "  -> FAILED: " . mysqli_error($conn) . "\n";
    }
}

file_put_contents('fix_auto_increment.sql', $sql_commands);
echo $output;
echo "\nGenerated SQL saved to fix_auto_increment.sql\n";
?>
