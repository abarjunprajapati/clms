<?php
include __DIR__ . '/include/config.php';
$result = $conn->query("SHOW TABLES LIKE 'master_departments'");
if ($result->num_rows > 0) {
    echo "Table exists\n";
    $depts = $conn->query("SELECT dept_name FROM master_departments");
    while($row = $depts->fetch_assoc()) {
        echo $row['dept_name'] . "\n";
    }
} else {
    echo "Table does not exist\n";
}
?>
