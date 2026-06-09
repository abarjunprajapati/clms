<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';
require_once '../../include/temporary_pass_validity.php';

require_role(['pass_issuer', 'pass_user', 'admin', 'super_admin', 'welfare_user', 'welfare_admin']);

function extendPassColumnExists($table, $column) {
    global $conn;
    $table = str_replace('`', '``', $table);
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = $input['workman_id'] ?? 0;
$new_valid_to = $input['new_valid_to'] ?? '';
$extension_reason = trim((string)($input['reason'] ?? $input['extension_reason'] ?? ''));
$declaration_ref = trim((string)($input['declaration_ref'] ?? $input['declaration_reference'] ?? ''));
$officer_rank = trim((string)($input['officer_rank'] ?? ''));

if (!$workman_id || !$new_valid_to) {
    json_response(false, null, 'Invalid parameters');
}

$workman = db_single($conn, "SELECT * FROM workmen WHERE id = ?", "i", [$workman_id]);
if (!$workman) json_response(false, null, 'Workman not found');

$old_valid_to = $workman['temp_valid_to'] ?? $workman['valid_to'] ?? null;
$valid_from = $workman['temp_valid_from'] ?? $workman['valid_from'] ?? null;
$hasTempValidTo = extendPassColumnExists('workmen', 'temp_valid_to');
$hasValidTo = extendPassColumnExists('workmen', 'valid_to');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$new_valid_to)) {
    json_response(false, null, 'Please enter a valid extension date.');
}
if (!$old_valid_to || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$old_valid_to)) {
    json_response(false, null, 'Existing temporary pass validity is missing.');
}
if (!$valid_from || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$valid_from)) {
    json_response(false, null, 'Existing temporary pass start date is missing.');
}
if (strtotime($new_valid_to) <= strtotime($old_valid_to)) {
    json_response(false, null, 'Extension date must be after current valid-to date.');
}

$maxDays = clms_get_temporary_pass_validity_days($conn, $valid_from);
$maxAllowedTo = date('Y-m-d', strtotime($valid_from . ' +' . ($maxDays - 1) . ' days'));
$needsOfficerDeclaration = strtotime($new_valid_to) > strtotime($maxAllowedTo);
if ($needsOfficerDeclaration) {
    $rankOk = (bool)preg_match('/\b(GM|GENERAL\s+MANAGER|CGM|DIRECTOR|CMD|CHAIRMAN)\b/i', $officer_rank);
    if ($declaration_ref === '' || !$rankOk) {
        json_response(false, null, 'Extension beyond configured temporary pass validity requires declaration reference from GM or above.');
    }
}

if ($extension_reason === '') {
    $extension_reason = $needsOfficerDeclaration ? 'Temporary pass extended with officer declaration.' : 'Temporary pass validity extended.';
}

foreach ([
    'extension_reason' => 'TEXT NULL',
    'declaration_reference' => 'VARCHAR(255) NULL',
    'approved_rank' => 'VARCHAR(100) NULL',
] as $column => $definition) {
    if (!extendPassColumnExists('pass_history', $column)) {
        @clms_db_query($conn, "ALTER TABLE pass_history ADD COLUMN `$column` $definition");
    }
}

clms_db_begin_transaction($conn);
try {
    if (($workman['status'] ?? '') === 'temporary_issued' && $hasTempValidTo) {
        db_execute($conn, "UPDATE workmen SET temp_valid_to = ? WHERE id = ?", "si", [$new_valid_to, $workman_id]);
    } elseif ($hasValidTo) {
        db_execute($conn, "UPDATE workmen SET valid_to = ? WHERE id = ?", "si", [$new_valid_to, $workman_id]);
    } else {
        throw new Exception('Pass validity column missing in workmen table.');
    }
    
    db_execute($conn, "INSERT INTO pass_history
                      (workman_id, pass_type, valid_from, valid_to, extended_from, extended_to, extension_reason, declaration_reference, approved_rank)
                      VALUES (?, 'temporary', ?, ?, ?, ?, ?, ?, ?)",
                      "isssssss", [$workman_id, $valid_from, $new_valid_to, $old_valid_to, $new_valid_to, $extension_reason, $declaration_ref, $officer_rank]);
    
    clms_db_commit($conn);
    json_response(true, null, 'Pass validity extended successfully to ' . $new_valid_to);
} catch (Exception $e) {
    clms_db_rollback($conn);
    json_response(false, null, $e->getMessage());
}

