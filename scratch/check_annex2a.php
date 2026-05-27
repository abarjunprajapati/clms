<?php
include __DIR__ . '/../include/config.php';
$r = $conn->query("DESCRIBE contractor_annexure2a");
if ($r) {
    while($row = $r->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "No contractor_annexure2a table or error: " . $conn->error . "\n";
}
?>
