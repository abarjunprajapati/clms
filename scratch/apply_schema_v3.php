<?php
$conn = mysqli_connect("localhost", "root", "", "new_clms");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$sql = file_get_contents(__DIR__ . "/../sql/enterprise_governance_v3.sql");

if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    echo "SUCCESS: Schema updated.\n";
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}
?>
