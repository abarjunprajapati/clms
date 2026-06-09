<?php
// api/contractor/confirm_training.php
// Contractor confirms a scheduled training
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor', 'customer', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/customer_portal_context.php';
header('Content-Type: application/json');

clms_get_portal_contractor($conn);

function contractor_confirm_table_exists($conn, $table) {
    $safeTable = clms_db_real_escape_string($conn, $table);
    $res = clms_db_query($conn, "SHOW TABLES LIKE '$safeTable'");
    return $res && clms_db_num_rows($res) > 0;
}

function contractor_confirm_column_exists($conn, $table, $column) {
    if (!contractor_confirm_table_exists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = clms_db_real_escape_string($conn, $column);
    $res = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && clms_db_num_rows($res) > 0;
}

function contractor_confirm_ensure_column($conn, $table, $column, $definition) {
    if (contractor_confirm_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    clms_db_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

if (contractor_confirm_table_exists($conn, 'training_requests')) {
    contractor_confirm_ensure_column($conn, 'training_requests', 'scheduled_session_id', 'INT NULL');
}

$data = json_decode(file_get_contents('php://input'), true);
$req_id           = (int)($data['request_id'] ?? 0);
$contractor_remarks = $data['contractor_remarks'] ?? '';

if (!$req_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// Verify ownership
$req = db_single($conn, "SELECT tr.*, c.user_id as contractor_user_id FROM training_requests tr JOIN contractors c ON tr.contractor_id = c.id WHERE tr.id=?", 'i', [$req_id]);
if (!$req || $req['contractor_user_id'] != $user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
if (!in_array($req['status'], ['scheduled', 'contractor_confirmed'], true)) {
    echo json_encode(['success' => false, 'error' => 'This request is not ready for contractor confirmation']);
    exit;
}

clms_db_begin_transaction($conn);
try {
    if (empty($req['scheduled_date']) || empty($req['scheduled_venue'])) {
        throw new Exception('Safety schedule is incomplete. Please contact Safety Department.');
    }

    $final_time = $req['scheduled_time'] ?: ($req['scheduled_shift'] === 'morning' ? '09:00:00' : '14:00:00');
    $session = null;
    if (!empty($req['scheduled_session_id'])) {
        $session = db_single($conn,
            "SELECT id, session_status FROM training_schedule WHERE id = ? LIMIT 1",
            'i',
            [(int)$req['scheduled_session_id']]
        );
    }
    if (!$session) {
        $session = db_single($conn,
            "SELECT id, session_status FROM training_schedule
             WHERE session_date = ? AND LOWER(TRIM(location)) = LOWER(TRIM(?)) AND session_time = ?
               AND LOWER(COALESCE(session_status, 'open')) <> 'cancelled'
             LIMIT 1",
            'sss',
            [$req['scheduled_date'], trim($req['scheduled_venue']), $final_time]
        );
    }
    if (!$session) {
        db_execute(
            $conn,
            "INSERT INTO training_schedule (session_date, session_time, location, capacity, trainer_name, batch_number, training_type, session_status, created_at)
             VALUES (?, ?, ?, 30, ?, ?, 'induction', 'open', NOW())",
            'sssss',
            [
                $req['scheduled_date'],
                $final_time,
                trim($req['scheduled_venue']),
                (string)($req['instructor'] ?? ''),
                (string)($req['batch_number'] ?? '')
            ]
        );
        $session = ['id' => clms_db_insert_id($conn), 'session_status' => 'open'];
    }
    if (strtolower((string)($session['session_status'] ?? 'open')) === 'cancelled') {
        throw new Exception('This training session has been cancelled by Safety.');
    }
    $session_id = (int)$session['id'];

    $ok = db_execute($conn,
        "UPDATE training_requests SET contractor_confirmed=1, contractor_remarks=?, status='contractor_confirmed', scheduled_session_id=?, updated_at=NOW() WHERE id=?",
        'sii', [$contractor_remarks, $session_id, $req_id]
    );
    if (!$ok) {
        throw new Exception('Unable to confirm training schedule.');
    }

    // Update workmen table status
    db_execute($conn, 
        "UPDATE workmen SET safety_training_status = 'TRAINING_CONFIRMED', training_status = 'scheduled' WHERE id = ?", 
        'i', [$req['workman_id']]
    );
    // Assign worker to the batch only after contractor confirmation.
    $existingMap = db_single(
        $conn,
        "SELECT id FROM training_session_workers WHERE workman_id = ? AND training_request_id = ? LIMIT 1",
        'ii',
        [$req['workman_id'], $req_id]
    );
    if ($existingMap) {
        db_execute(
            $conn,
            "UPDATE training_session_workers SET session_id = ?, attendance_status = 'pending', result = 'pending' WHERE id = ?",
            'ii',
            [$session_id, (int)$existingMap['id']]
        );
    } else {
        db_execute(
            $conn,
            "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
             VALUES (?, ?, ?, 'pending', 'pending', NOW())",
            'iii',
            [$session_id, $req['workman_id'], $req_id]
        );
    }
    db_execute(
                $conn,
                "UPDATE training_schedule
                 SET enrolled_count = (
                     SELECT COUNT(*)
                     FROM training_session_workers tsw
                     JOIN training_requests tr ON tr.id = tsw.training_request_id
                     WHERE tsw.session_id = ? AND tr.status = 'contractor_confirmed'
                 )
                 WHERE id = ?",
                'ii',
                [$session_id, $session_id]
            );

    // Notify safety dept (get a safety user to notify - or just log it)
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details) VALUES (?,?,?,?)",
        'isss', [$user_id, 'training_confirmed', 'training_requests', "Request ID $req_id confirmed by contractor with remarks: $contractor_remarks"]
    );
    clms_db_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Training confirmed. Safety team has been notified.']);
} catch (Throwable $e) {
    clms_db_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
