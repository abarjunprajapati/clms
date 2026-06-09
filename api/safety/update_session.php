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
    $table = str_replace('`', '``', $table);
    $column = clms_db_real_escape_string($conn, $column);
    $res = clms_db_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && clms_db_num_rows($res) > 0;
}

function safetySessionNotifyContractors($conn, $sessionId, $message) {
    if (!safetySessionColumnExists($conn, 'notifications', 'user_id') ||
        !safetySessionColumnExists($conn, 'notifications', 'message') ||
        !safetySessionColumnExists($conn, 'notifications', 'type') ||
        !safetySessionColumnExists($conn, 'notifications', 'is_read')) {
        return;
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
        db_execute(
            $conn,
            "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'training_schedule_update', 0)",
            'is',
            [(int)$row['user_id'], $message]
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safetySessionJson(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$sessionId = (int)($input['session_id'] ?? 0);
$action = strtolower(trim((string)($input['action'] ?? $input['schedule_action'] ?? '')));
if ($action === 'update') $action = 'reschedule';
$reason = trim((string)($input['reason'] ?? $input['change_reason'] ?? 'Updated by Safety.'));

if (!$sessionId || !in_array($action, ['cancel', 'reschedule'], true)) {
    safetySessionJson(['success' => false, 'message' => 'Session and valid action are required.'], 422);
}

foreach ([
    'session_status' => "VARCHAR(50) DEFAULT 'open'",
    'remarks' => 'TEXT NULL',
    'updated_at' => 'DATETIME NULL',
    'batch_number' => 'VARCHAR(100) NULL',
    'capacity' => 'INT DEFAULT 30',
    'training_type' => "VARCHAR(100) DEFAULT 'Safety Induction'",
] as $column => $definition) {
    if (!safetySessionColumnExists($conn, 'training_schedule', $column)) {
        @clms_db_query($conn, "ALTER TABLE training_schedule ADD COLUMN `$column` $definition");
    }
}

$session = db_single($conn, "SELECT * FROM training_schedule WHERE id = ? LIMIT 1", 'i', [$sessionId]);
if (!$session) {
    safetySessionJson(['success' => false, 'message' => 'Session not found.'], 404);
}

$currentStatus = strtolower((string)($session['session_status'] ?? 'open'));
if ($currentStatus === 'completed') {
    safetySessionJson(['success' => false, 'message' => 'Completed session cannot be modified.'], 409);
}

$linked = db_fetch_all(
    $conn,
    "SELECT tsw.workman_id, tsw.training_request_id
     FROM training_session_workers tsw
     WHERE tsw.session_id = ?",
    'i',
    [$sessionId]
);

clms_db_begin_transaction($conn);
try {
    if ($action === 'cancel') {
        safetySessionNotifyContractors($conn, $sessionId, "Safety training session has been cancelled. Reason: $reason");
        db_execute(
            $conn,
            "UPDATE training_schedule SET session_status = 'cancelled', remarks = ?, updated_at = NOW() WHERE id = ?",
            'si',
            [$reason, $sessionId]
        );

        foreach ($linked as $row) {
            $requestId = (int)($row['training_request_id'] ?? 0);
            $workmanId = (int)($row['workman_id'] ?? 0);
            if ($requestId > 0) {
                db_execute(
                    $conn,
                    "UPDATE training_requests
                     SET status = 'pending',
                         contractor_confirmed = 0,
                         scheduled_session_id = NULL,
                         scheduled_date = NULL,
                         scheduled_shift = NULL,
                         scheduled_venue = NULL,
                         scheduled_time = NULL,
                         safety_remarks = ?,
                         updated_at = NOW()
                     WHERE id = ?",
                    'si',
                    [$reason, $requestId]
                );
            }
            if ($workmanId > 0) {
                db_execute(
                    $conn,
                    "UPDATE workmen
                     SET training_status = 'training_pending',
                         safety_training_status = 'PENDING_TRAINING'
                     WHERE id = ?",
                    'i',
                    [$workmanId]
                );
            }
        }
        db_execute($conn, "DELETE FROM training_session_workers WHERE session_id = ?", 'i', [$sessionId]);
        clms_db_commit($conn);
        safetySessionJson(['success' => true, 'message' => 'Training session cancelled. Workers returned to scheduling queue.']);
    }

    $date = trim((string)($input['session_date'] ?? ''));
    $time = trim((string)($input['session_time'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $trainer = trim((string)($input['trainer_name'] ?? ($session['trainer_name'] ?? '')));
    $batch = trim((string)($input['batch_number'] ?? ($session['batch_number'] ?? '')));
    $capacity = max(1, (int)($input['capacity'] ?? ($session['capacity'] ?? 30)));
    $trainingType = trim((string)($input['training_type'] ?? ($session['training_type'] ?? 'Safety Induction')));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) || $location === '') {
        throw new Exception('Training date, time and venue are required for reschedule.');
    }
    if (strlen($time) === 5) $time .= ':00';
    $shift = ((int)substr($time, 0, 2) < 12) ? 'morning' : 'evening';

    db_execute(
        $conn,
        "UPDATE training_schedule
         SET session_date = ?, session_time = ?, location = ?, capacity = ?, trainer_name = ?, batch_number = ?, training_type = ?,
             session_status = 'open', remarks = ?, updated_at = NOW()
         WHERE id = ?",
        'sssissssi',
        [$date, $time, $location, $capacity, $trainer, $batch, $trainingType, $reason, $sessionId]
    );

    foreach ($linked as $row) {
        $requestId = (int)($row['training_request_id'] ?? 0);
        $workmanId = (int)($row['workman_id'] ?? 0);
        if ($requestId > 0) {
            db_execute(
                $conn,
                "UPDATE training_requests
                 SET status = 'scheduled',
                     contractor_confirmed = 0,
                     scheduled_session_id = ?,
                     scheduled_date = ?,
                     scheduled_shift = ?,
                     scheduled_venue = ?,
                     scheduled_time = ?,
                     safety_remarks = ?,
                     updated_at = NOW()
                 WHERE id = ?",
                'isssssi',
                [$sessionId, $date, $shift, $location, $time, $reason, $requestId]
            );
        }
        if ($workmanId > 0) {
            db_execute(
                $conn,
                "UPDATE workmen
                 SET training_status = 'scheduled',
                     safety_training_status = 'TRAINING_SCHEDULED'
                 WHERE id = ?",
                'i',
                [$workmanId]
            );
        }
    }

    // Require contractor reconfirmation after a date/time/venue change.
    db_execute($conn, "DELETE FROM training_session_workers WHERE session_id = ?", 'i', [$sessionId]);
    if (safetySessionColumnExists($conn, 'training_schedule', 'enrolled_count')) {
        db_execute($conn, "UPDATE training_schedule SET enrolled_count = 0 WHERE id = ?", 'i', [$sessionId]);
    }

    safetySessionNotifyContractors($conn, $sessionId, "Safety training schedule updated: " . date('d M Y', strtotime($date)) . " at " . substr($time, 0, 5) . ", venue: $location.");
    clms_db_commit($conn);
    safetySessionJson(['success' => true, 'message' => 'Training session rescheduled. Contractors must confirm the updated schedule.']);
} catch (Throwable $e) {
    clms_db_rollback($conn);
    safetySessionJson(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
