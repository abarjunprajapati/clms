<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';
require_once '../../include/NotificationEngine.php';
require_once '../../include/temporary_pass_validity.php';

require_role(['pass_issuer', 'pass_user', 'admin', 'welfare', 'welfare_user', 'welfare_admin']);

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = $input['workman_id'] ?? 0;
$pass_type = $input['pass_type'] ?? ''; // 'temporary' or 'permanent'
$valid_from = $input['valid_from'] ?? date('Y-m-d');
$valid_to = $input['valid_to'] ?? '';
$remarks = $input['remarks'] ?? '';

function welfare_issue_training_passed(array $workman): bool {
    $trainingStatus = strtolower(trim((string)($workman['training_status'] ?? '')));
    $safetyTrainingStatus = strtolower(trim((string)($workman['safety_training_status'] ?? '')));

    return in_array($trainingStatus, ['pass', 'passed', 'training_passed', 'qualified', 'completed'], true)
        || in_array($safetyTrainingStatus, ['1', 'pass', 'passed', 'training_passed', 'qualified', 'completed'], true);
}

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

    if (!welfare_issue_training_passed($workman)) {
        json_response(false, null, 'Safety training is not passed. Pass cannot be issued.');
    }

    if (!empty($workman['training_valid_till']) && strtotime($workman['training_valid_till']) < strtotime(date('Y-m-d'))) {
        json_response(false, null, 'Safety training validity has expired. Please complete re-training before issuing pass.');
    }

    clms_db_begin_transaction($conn);

    try {
        if ($pass_type === 'temporary') {
            $approvedRequest = db_single(
                $conn,
                "SELECT gpr.id, gpr.request_no
                 FROM gate_pass_request_workers gprw
                 JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
                 WHERE gprw.workman_id = ?
                   AND LOWER(COALESCE(gprw.status, '')) = 'approved'
                   AND LOWER(COALESCE(gpr.status, '')) = 'approved'
                 ORDER BY COALESCE(gpr.updated_at, gpr.created_at) DESC, gpr.id DESC
                 LIMIT 1",
                "i",
                [$workman_id]
            );
            if (!$approvedRequest) {
                throw new Exception("Approved gate pass request not found for this worker.");
            }

            if (!$valid_to) throw new Exception("Validity end date is required for temporary pass.");
            $maxTempValidityDays = clms_get_temporary_pass_validity_days($conn);
            $durationDays = (int)floor((strtotime($valid_to) - strtotime($valid_from)) / 86400) + 1;
            if ($durationDays < 1) {
                throw new Exception("Temporary pass validity date range is invalid.");
            }
            if ($durationDays > $maxTempValidityDays) {
                throw new Exception("Temporary pass validity cannot exceed {$maxTempValidityDays} days.");
            }
            
            $year = date('Y');
            $count = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE temp_pass_no LIKE 'TEMP-$year-%'") + 1;
            $temp_pass_no = sprintf("TEMP-%s-%05d", $year, $count);
            
            db_execute($conn, "UPDATE workmen SET status = 'temporary_issued', temp_pass_status = 1, temp_pass_no = ?, temp_valid_from = ?, temp_valid_to = ? WHERE id = ?", "sssi", [$temp_pass_no, $valid_from, $valid_to, $workman_id]);
            
            db_execute($conn, "INSERT INTO pass_history (workman_id, pass_type, valid_from, valid_to) VALUES (?, 'temporary', ?, ?)", "iss", [$workman_id, $valid_from, $valid_to]);
            
            // Mark the gate pass request as issued so it disappears from the pending queue
            db_execute($conn, "UPDATE gate_pass_request_workers SET status = 'issued', gatepass_no = ?, updated_at = NOW() WHERE request_id = ? AND workman_id = ? AND status = 'approved'", "sii", [$temp_pass_no, (int)$approvedRequest['id'], $workman_id]);
            $remaining = db_count($conn, "SELECT COUNT(*) FROM gate_pass_request_workers WHERE request_id = ? AND LOWER(COALESCE(status, '')) = 'approved'", "i", [(int)$approvedRequest['id']]);
            if ($remaining === 0) {
                db_execute($conn, "UPDATE gate_pass_requests SET status = 'issued', updated_at = NOW() WHERE id = ?", "i", [(int)$approvedRequest['id']]);
            }

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

    clms_db_commit($conn);
    json_response(true, ['acc_number' => $acc_number ?? null], $msg);

} catch (Exception $e) {
    clms_db_rollback($conn);
    json_response(false, null, $e->getMessage());
}
