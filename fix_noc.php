<?php
require 'include/config.php';

// Find workmen whose contractor_id is actually a user_id
$res = $conn->query("
    SELECT w.id, w.contractor_id, c.id AS correct_contractor_id 
    FROM workmen w
    JOIN contractors c ON w.contractor_id = c.user_id
    WHERE w.contractor_id NOT IN (SELECT id FROM contractors)
");

$fixed = 0;
while($row = $res->fetch_assoc()) {
    $conn->query("UPDATE workmen SET contractor_id = {$row['correct_contractor_id']} WHERE id = {$row['id']}");
    $fixed++;
}

echo "Fixed $fixed workmen.";
?>
