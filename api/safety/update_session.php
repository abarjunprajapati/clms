<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

checkAuth(['safety_user', 'super_admin']);
header('Content-Type: application/json; charset=utf-8');

function safetySessionJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function safetySessionColumnExists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && mysqli_num_rows($res) > 0;
}

function safetySessionEnsureStatusColumn($conn) {
    @mysqli_query($conn, "ALTER TABLE training_schedule MODIFY COLUMN session_status VARCHAR(50) DEFAULT 'open'");
}

function safetySessionNotifyContractors($conn, $sessionId, $message) {
    foreach (['user_id', 'message', 'type', 'is_read'] as $column) {
        if (!safetySessionColumnExists($conn, 'notifications', $column)) return;
    }
    $rows = db_fetch_all(
        $conn,
        "SELECT DISTINCT c.user_id
         FROM training_session_workers tsw
         JOIN workmen w ON w.id = tsw.workman_id
         JOIN contractors c ON c.id = w.contractor_id
         WHERE tsw.session_id = ? AND COALESCE(c.user_id, 0) > 0",
        'i',
        [(int)$sessionId]
    );
    foreach ($rows as $row) {
        db_execute($conn, "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'training_schedule_update', 0)", 'is', [(int)$row['user_id'], $message]);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safetySessionJson(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$sessionId = (int)($_POST['session_id'] ?? 0);
$action = strtolower(trim((string)($_POST['schedule_action'] ?? 'update')));
if (!$sessionId) {
    safetySessionJson(['success' => false, 'message' => 'Invalid session.'], 422);
}

$session = db_single($conn, "SELECT * FROM training_schedule WHERE id = ? LIMIT 1", 'i', [$sessionId]);
if (!$session) {
    safetySessionJson(['success' => false, 'message' => 'Session not found.'], 404);
}
if (strtolower((string)($session['session_status'] ?? 'open')) === 'completed') {
    safetySessionJson(['success' => false, 'message' => 'Completed session cannot be changed.'], 409);
}

mysqli_begin_transaction($conn);
try {
    if ($action === 'cancel') {
        $reason = trim((string)($_POST['change_reason'] ?? 'Training session cancelled by Safety.'));
        safetySessionEnsureStatusColumn($conn);
        $updated = db_execute($conn, "UPDATE training_schedule SET session_status = 'cancelled' WHERE id = ?", 'i', [$sessionId]);
        if (!$updated) {
            throw new Exception('Unable to cancel session. Please check training_schedule.session_status column.');
        }

        $statusCheck = db_single($conn, "SELECT session_status FROM training_schedule WHERE id = ? LIMIT 1", 'i', [$sessionId]);
        if (strtolower((string)($statusCheck['session_status'] ?? '')) !== 'cancelled') {
            throw new Exception('Session status was not changed to cancelled.');
        }

        db_execute(
            $conn,
            "UPDATE training_requests tr
             JOIN training_session_workers tsw ON tsw.training_request_id = tr.id
             SET tr.status = 'pending',
                 tr.scheduled_date = NULL,
                 tr.scheduled_shift = NULL,
                 tr.scheduled_venue = NULL,
                 tr.scheduled_time = NULL,
                 tr.safety_remarks = ?,
                 tr.updated_at = NOW()
             WHERE tsw.session_id = ?",
            'si',
            [$reason, $sessionId]
        );

        $sessionDate = trim((string)($session['session_date'] ?? ''));
        $sessionVenue = trim((string)($session['location'] ?? ''));
        $sessionTime = substr((string)($session['session_time'] ?? ''), 0, 5);
        if ($sessionDate !== '' && $sessionVenue !== '') {
            db_execute(
                $conn,
                "UPDATE training_requests tr
                 SET tr.status = 'pending',
                     tr.scheduled_date = NULL,
                     tr.scheduled_shift = NULL,
                     tr.scheduled_venue = NULL,
                     tr.scheduled_time = NULL,
                     tr.safety_remarks = ?,
                     tr.updated_at = NOW()
                 WHERE tr.status IN ('scheduled', 'contractor_confirmed')
                   AND tr.scheduled_date = ?
                   AND LOWER(TRIM(tr.scheduled_venue)) = LOWER(TRIM(?))
                   AND (
                       SUBSTRING(COALESCE(tr.scheduled_time, ''), 1, 5) = ?
                       OR (tr.scheduled_shift = 'morning' AND ? = '09:00')
                       OR (tr.scheduled_shift = 'evening' AND ? = '14:00')
                   )",
                'ssssss',
                [$reason, $sessionDate, $sessionVenue, $sessionTime, $sessionTime, $sessionTime]
            );

            db_execute(
                $conn,
                "UPDATE workmen w
                 JOIN training_requests tr ON tr.workman_id = w.id
                 SET w.training_status = 'training_pending',
                     w.safety_training_status = 'PENDING_TRAINING'
                 WHERE tr.status = 'pending'
                   AND tr.safety_remarks = ?
                   AND tr.updated_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)",
                's',
                [$reason]
            );
        }

        db_execute(
            $conn,
            "UPDATE workmen w
             JOIN training_session_workers tsw ON tsw.workman_id = w.id
             SET w.training_status = 'training_pending',
                 w.safety_training_status = 'PENDING_TRAINING'
             WHERE tsw.session_id = ?",
            'i',
            [$sessionId]
        );
        safetySessionNotifyContractors($conn, $sessionId, "Safety training session has been cancelled. Reason: $reason");
        mysqli_commit($conn);
        safetySessionJson(['success' => true, 'message' => 'Session cancelled and workers returned to scheduling queue.']);
    }

    $date = trim((string)($_POST['session_date'] ?? ''));
    $time = trim((string)($_POST['session_time'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $capacity = max(1, (int)($_POST['capacity'] ?? 30));
    $trainer = trim((string)($_POST['trainer_name'] ?? ''));
    $batch = trim((string)($_POST['batch_number'] ?? ''));
    $type = trim((string)($_POST['training_type'] ?? 'induction'));
    $remarks = trim((string)($_POST['change_reason'] ?? 'Training schedule updated by Safety.'));

    if ($date === '' || $time === '' || $location === '') {
        throw new Exception('Training date, time and venue are required.');
    }

    $shift = ((int)substr($time, 0, 2) >= 14) ? 'evening' : 'morning';

    db_execute(
        $conn,
        "UPDATE training_schedule
         SET session_date = ?, session_time = ?, location = ?, capacity = ?, trainer_name = ?, batch_number = ?, training_type = ?, session_status = 'open'
         WHERE id = ?",
        'sssisssi',
        [$date, $time, $location, $capacity, $trainer, $batch, $type, $sessionId]
    );

    db_execute(
        $conn,
        "UPDATE training_requests tr
         JOIN training_session_workers tsw ON tsw.training_request_id = tr.id
         SET tr.scheduled_date = ?,
             tr.scheduled_shift = ?,
             tr.scheduled_venue = ?,
             tr.scheduled_time = ?,
             tr.batch_number = ?,
             tr.instructor = ?,
             tr.safety_remarks = ?,
             tr.status = 'scheduled',
             tr.updated_at = NOW()
         WHERE tsw.session_id = ?",
        'sssssssi',
        [$date, $shift, $location, $time, $batch, $trainer, $remarks, $sessionId]
    );

    safetySessionNotifyContractors($conn, $sessionId, "Safety training schedule updated: " . date('d M Y', strtotime($date)) . " at $time, venue: $location.");
    mysqli_commit($conn);
    safetySessionJson(['success' => true, 'message' => 'Training schedule updated and contractors notified.']);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    safetySessionJson(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
