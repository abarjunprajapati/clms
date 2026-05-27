<?php
include 'include/config.php';
$res = $conn->query("ALTER TABLE contractor_documents ADD COLUMN annexure3a_id INT NULL AFTER contractor_id");
if ($res) echo "Success";
else echo "Error: " . $conn->error;
?>
