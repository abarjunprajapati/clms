<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';

require_role(['pass_issuer', 'admin']);

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = $input['workman_id'] ?? 0;
$new_valid_to = $input['new_valid_to'] ?? '';

if (!$workman_id || !$new_valid_to) {
    json_response(false, null, 'Invalid parameters');
}

$workman = db_single($conn, "SELECT * FROM workmen WHERE id = ?", "i", [$workman_id]);
if (!$workman) json_response(false, null, 'Workman not found');

$old_valid_to = $workman['valid_to'];

mysqli_begin_transaction($conn);
try {
    db_execute($conn, "UPDATE workmen SET valid_to = ? WHERE id = ?", "si", [$new_valid_to, $workman_id]);
    
    db_execute($conn, "INSERT INTO pass_history (workman_id, pass_type, valid_from, valid_to, extended_from, extended_to) 
                      VALUES (?, 'temporary', ?, ?, ?, ?)", 
                      "issss", [$workman_id, $workman['valid_from'], $new_valid_to, $old_valid_to, $new_valid_to]);
    
    mysqli_commit($conn);
    json_response(true, null, 'Pass validity extended successfully to ' . $new_valid_to);
} catch (Exception $e) {
    mysqli_rollback($conn);
    json_response(false, null, $e->getMessage());
}

