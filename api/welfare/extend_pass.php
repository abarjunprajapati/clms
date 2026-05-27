<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';

require_role(['pass_issuer', 'pass_user', 'admin', 'super_admin', 'welfare_user', 'welfare_admin']);

function extendPassColumnExists($table, $column) {
    global $conn;
    $table = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = $input['workman_id'] ?? 0;
$new_valid_to = $input['new_valid_to'] ?? '';

if (!$workman_id || !$new_valid_to) {
    json_response(false, null, 'Invalid parameters');
}

$workman = db_single($conn, "SELECT * FROM workmen WHERE id = ?", "i", [$workman_id]);
if (!$workman) json_response(false, null, 'Workman not found');

$old_valid_to = $workman['temp_valid_to'] ?? $workman['valid_to'] ?? null;
$valid_from = $workman['temp_valid_from'] ?? $workman['valid_from'] ?? null;
$hasTempValidTo = extendPassColumnExists('workmen', 'temp_valid_to');
$hasValidTo = extendPassColumnExists('workmen', 'valid_to');

mysqli_begin_transaction($conn);
try {
    if (($workman['status'] ?? '') === 'temporary_issued' && $hasTempValidTo) {
        db_execute($conn, "UPDATE workmen SET temp_valid_to = ? WHERE id = ?", "si", [$new_valid_to, $workman_id]);
    } elseif ($hasValidTo) {
        db_execute($conn, "UPDATE workmen SET valid_to = ? WHERE id = ?", "si", [$new_valid_to, $workman_id]);
    } else {
        throw new Exception('Pass validity column missing in workmen table.');
    }
    
    db_execute($conn, "INSERT INTO pass_history (workman_id, pass_type, valid_from, valid_to, extended_from, extended_to) 
                      VALUES (?, 'temporary', ?, ?, ?, ?)", 
                      "issss", [$workman_id, $valid_from, $new_valid_to, $old_valid_to, $new_valid_to]);
    
    mysqli_commit($conn);
    json_response(true, null, 'Pass validity extended successfully to ' . $new_valid_to);
} catch (Exception $e) {
    mysqli_rollback($conn);
    json_response(false, null, $e->getMessage());
}

