<?php
require_once 'include/config.php';

// Get current database name
$res = mysqli_query($conn, "SELECT DATABASE() as db");
$row = mysqli_fetch_assoc($res);
$dbname = $row['db'];

echo "<h3>Database: $dbname</h3>";

$sql = "SELECT TABLE_NAME, COLUMN_TYPE FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA='$dbname' AND COLUMN_NAME='id' AND EXTRA NOT LIKE '%auto_increment%'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<p>No tables found missing AUTO_INCREMENT on their `id` column!</p>";
} else {
    echo "<ul>";
    while($row = mysqli_fetch_assoc($result)) {
        $table = $row['TABLE_NAME'];
        $type = $row['COLUMN_TYPE'];
        
        $alter = "ALTER TABLE `$table` MODIFY `id` $type NOT NULL AUTO_INCREMENT;";
        if (mysqli_query($conn, $alter)) {
            echo "<li style='color:green;'>Fixed `$table` (was missing AUTO_INCREMENT on `id`)</li>";
        } else {
            echo "<li style='color:red;'>Error fixing `$table`: " . mysqli_error($conn) . "</li>";
        }
    }
    echo "</ul>";
}

echo "<br><strong>Fix completed!</strong> Please try creating the user again.";
?>
