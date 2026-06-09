<?php
/** Update Pass Limit for a Contractor */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

$contractorId = $input['contractor_id'] ?? 0;
$maxAllowed = (int)($input['max_allowed'] ?? 0);

if (!$contractorId) jsonError('Contractor ID required');
if ($maxAllowed < 0) jsonError('Max allowed cannot be negative');

// Check if pass_limits row exists
$existing = db_single($conn, "SELECT * FROM pass_limits WHERE contractor_id=?", 'i', [$contractorId]);

if ($existing) {
    $oldMax = $existing['max_allowed'];
    db_execute($conn, "UPDATE pass_limits SET max_allowed=? WHERE contractor_id=?", 'ii', [$maxAllowed, $contractorId]);
    logAdminActivity($conn, 'pass_limit_updated', 'pass_limits', $contractorId, ['max_allowed' => $oldMax], ['max_allowed' => $maxAllowed]);
} else {
    db_execute($conn, "INSERT INTO pass_limits (contractor_id, max_allowed, current_count) VALUES (?,?,0)", 'ii', [$contractorId, $maxAllowed]);
    logAdminActivity($conn, 'pass_limit_created', 'pass_limits', $contractorId, null, ['max_allowed' => $maxAllowed]);
}

jsonSuccess("Pass limit updated to $maxAllowed for contractor #$contractorId");
