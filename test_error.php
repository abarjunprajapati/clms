<?php
require_once 'include/config.php';

$sql = "INSERT INTO gate_passes (workman_id, pass_type, pass_number, valid_from, valid_to, status, is_temporary, application_no, executing_officer_declaration_path, created_at, updated_at) VALUES (?, ?, '', ?, ?, 'pending', 1, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Prepare failed: " . $conn->error;
} else {
    echo "Prepare success!";
}
?>
