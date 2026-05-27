<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';
require_once '../../include/NotificationEngine.php';

require_role(['pass_issuer', 'pass_user', 'admin', 'welfare', 'welfare_user', 'welfare_admin']);

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = $input['workman_id'] ?? 0;
$pass_type = $input['pass_type'] ?? ''; // 'temporary' or 'permanent'
$valid_from = $input['valid_from'] ?? date('Y-m-d');
$valid_to = $input['valid_to'] ?? '';
$remarks = $input['remarks'] ?? '';

if (!$workman_id || !in_array($pass_type, ['temporary', 'permanent'])) {
    json_response(false, null, 'Invalid request parameters');
}

    $workman = db_single($conn, "SELECT w.*, c.user_id as contractor_user_id FROM workmen w JOIN contractors c ON w.contractor_id = c.id WHERE w.id = ?", "i", [$workman_id]);
    if (!$workman) {
        json_response(false, null, 'Workman not found');
    }

    if ($workman['is_blocked']) {
        json_response(false, null, 'Worker is blocked. Cannot issue pass.');
    }

    if ($workman['pass_issuer_verified'] != 1) {
        json_response(false, null, 'Final document verification pending.');
    }

    mysqli_begin_transaction($conn);

    try {
        if ($pass_type === 'temporary') {
            if (!$valid_to) throw new Exception("Validity end date is required for temporary pass.");
            
            $year = date('Y');
            $count = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE temp_pass_no LIKE 'TEMP-$year-%'") + 1;
            $temp_pass_no = sprintf("TEMP-%s-%05d", $year, $count);
            
            db_execute($conn, "UPDATE workmen SET status = 'temporary_issued', temp_pass_status = 1, temp_pass_no = ?, temp_valid_from = ?, temp_valid_to = ? WHERE id = ?", "sssi", [$temp_pass_no, $valid_from, $valid_to, $workman_id]);
            
            db_execute($conn, "INSERT INTO pass_history (workman_id, pass_type, valid_from, valid_to) VALUES (?, 'temporary', ?, ?)", "iss", [$workman_id, $valid_from, $valid_to]);
            
            // Mark the gate pass request as issued so it disappears from the pending queue
            db_execute($conn, "UPDATE gate_pass_request_workers SET status = 'issued', updated_at = NOW() WHERE workman_id = ? AND status = 'approved'", "i", [$workman_id]);

            $msg = "Temporary pass issued for " . $workman['name'] . " valid until " . $valid_to;
        } else {
            // Permanent / ACC
            $year = date('Y');
            // Simple sequential number for demo, ideally would be more robust
            $count = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE acc_number LIKE 'ACC-$year-%'") + 1;
            $acc_number = sprintf("ACC-%s-%05d", $year, $count);
            
            db_execute($conn, "UPDATE workmen SET status = 'acc_generated', acc_number = ?, biometric_status = 'pending' WHERE id = ?", "si", [$acc_number, $workman_id]);
            
            db_execute($conn, "INSERT INTO pass_history (workman_id, pass_type, valid_from) VALUES (?, 'permanent', ?)", "is", [$workman_id, $valid_from]);
            
            $msg = "ACC generated for " . $workman['name'] . ": " . $acc_number . ". Biometric enrollment pending.";
        }

        // Notify contractor
        if (!empty($workman['contractor_user_id'])) {
            NotificationEngine::trigger($conn, $workman['contractor_user_id'], "Pass Issued", $msg, 'info');
        }

    mysqli_commit($conn);
    json_response(true, ['acc_number' => $acc_number ?? null], $msg);

} catch (Exception $e) {
    mysqli_rollback($conn);
    json_response(false, null, $e->getMessage());
}

