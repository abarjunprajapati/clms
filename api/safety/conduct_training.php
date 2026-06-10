<?php
// api/safety/conduct_training.php
// Safety marks a confirmed training as completed (pass/fail)
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/safety_training_control.php';
header('Content-Type: application/json');

clms_safety_ensure_control_schema($conn);

function conduct_training_table_exists($conn, $table) {
    $safeTable = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
    return $res && mysqli_num_rows($res) > 0;
}

function conduct_training_column_exists($conn, $table, $column) {
    if (!conduct_training_table_exists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && mysqli_num_rows($res) > 0;
}

$data = json_decode(file_get_contents('php://input'), true);
$req_id          = (int)($data['request_id'] ?? 0);
$result          = $data['result'] ?? '';
$conduct_remarks = $data['conduct_remarks'] ?? $data['remarks'] ?? '';

if (!$req_id || !in_array($result, ['passed', 'failed'])) {
    echo json_encode(['success' => false, 'error' => 'Request ID and result (passed/failed) are required']);
    exit;
}

$safety_user_id = $_SESSION['user_id'] ?? 0;

// Get the training request
$req = db_single($conn,
    "SELECT tr.*, w.name as worker_name, c.user_id as contractor_user_id
     FROM training_requests tr
     JOIN workmen w ON tr.workman_id = w.id
     LEFT JOIN contractors c ON tr.contractor_id = c.id
     WHERE tr.id = ?",
    'i', [$req_id]
);

if (!$req || $req['status'] !== 'contractor_confirmed') {
    echo json_encode(['success' => false, 'error' => 'Request not found or contractor confirmation is pending.']);
    exit;
}

$conn->begin_transaction();
try {
    $attendance = $data['attendance'] ?? 'present';

    if ($attendance === 'absent') {
        // Reset to pending so it can be rescheduled
        db_execute($conn,
            "UPDATE training_requests SET status = 'pending', contractor_confirmed = 0, updated_at = NOW() WHERE id = ?",
            'i', [$req_id]
        );
        db_execute($conn,
            "UPDATE training_session_workers SET attendance_status = 'absent', result = 'pending', remarks = ?, created_at = NOW() WHERE training_request_id = ?",
            'si', [$conduct_remarks, $req_id]
        );
        db_execute($conn,
            "UPDATE training_batch_workers SET status = 'absent' WHERE training_request_id = ?",
            'i', [$req_id]
        );
        db_execute($conn,
            "UPDATE workmen SET training_status = 'training_pending', safety_training_status = 'PENDING_TRAINING', updated_at = NOW() WHERE id = ?",
            'i', [$req['workman_id']]
        );
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Worker marked as absent. Request reset to pending."]);
        exit;
    }

    // Update training request status to specific result (passed/failed)
    db_execute($conn,
        "UPDATE training_requests SET 
            status = ?, 
            conduct_remarks = ?, 
            updated_at = NOW() 
         WHERE id = ?",
        'ssi', [$result, $conduct_remarks, $req_id]
    );

    // Update worker training status in workmen table
    if ($result === 'passed') {
        $training_status = 'PASS';
        $eligibility = 'ELIGIBLE';
        $valid_till = date('Y-m-d', strtotime('+1 year'));
        $safety_status_string = 'TRAINING_PASSED';
    } else {
        $training_status = 'FAIL';
        $eligibility = 'NOT ELIGIBLE';
        $valid_till = null;
        $safety_status_string = 'TRAINING_FAILED';
    }
    
    db_execute($conn,
        "UPDATE workmen SET 
            training_status = ?, 
            eligibility_status = ?,
            training_valid_till = ?,
            safety_training_status = ?, 
            updated_at = NOW() 
         WHERE id = ?",
        'ssssi', [$training_status, $eligibility, $valid_till, $safety_status_string, $req['workman_id']]
    );

    // Sync with session mapping table if exists
    db_execute($conn,
        "UPDATE training_session_workers SET 
            attendance_status = ?, 
            result = ?, 
            remarks = ?,
            created_at = NOW()
         WHERE training_request_id = ?",
        'sssi', [$attendance, ($result === 'passed' ? 'pass' : 'fail'), $conduct_remarks, $req_id]
    );

    // Record result in training_results table
    $app_no = '';
    if (conduct_training_column_exists($conn, 'contractors', 'application_no')) {
        $app_no = db_single($conn, "SELECT application_no FROM contractors WHERE id = ?", 'i', [$req['contractor_id']])['application_no'] ?? '';
    }
    db_execute($conn,
        "INSERT INTO training_results (workman_id, application_no, result, recorded_by, created_at)
         VALUES (?, ?, ?, ?, NOW())",
        'issi', [$req['workman_id'], $app_no, $result, $safety_user_id]
    );
    db_execute($conn,
        "UPDATE training_batch_workers SET status = ?, scheduled_at = COALESCE(scheduled_at, NOW()) WHERE training_request_id = ?",
        'si', [$result, $req_id]
    );

    // Log audit
    if (conduct_training_table_exists($conn, 'audit_logs')) {
        db_execute($conn,
            "INSERT INTO audit_logs (user_id, action, module, details) VALUES (?,?,?,?)",
            'isss', [$safety_user_id, 'training_conducted', 'training_requests',
                "Worker {$req['worker_name']} (ID:{$req['workman_id']}) - Result: $result. Remarks: $conduct_remarks"]
        );
    }

    // Notify contractor
    if ($req['contractor_user_id'] && conduct_training_table_exists($conn, 'notifications')) {
        $resultLabel = ($result === 'passed') ? '✅ PASSED' : '❌ FAILED';
        $msg = "Safety training for {$req['worker_name']} has been conducted. Result: {$resultLabel}."
             . ($result === 'passed' ? ' Worker is now eligible for Gate Pass application.' : ' Please arrange re-training.')
             . ($conduct_remarks ? " Remarks: $conduct_remarks" : '');
        db_execute($conn,
            "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,'training_result',0)",
            'is', [$req['contractor_user_id'], $msg]
        );
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Training marked as $result successfully."]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
