<?php
$c = mysqli_connect('localhost', 'root', '', 'new_clms');
$sql = "INSERT INTO roles (role_name, description, is_system) 
        VALUES ('execution_officer', 'Monitoring authority for project execution and workforce supervision.', 1) 
        ON DUPLICATE KEY UPDATE role_name=role_name";
if (mysqli_query($c, $sql)) {
    echo "Execution Officer role added to roles table.\n";
} else {
    echo "Error: " . mysqli_error($c) . "\n";
}
?>
