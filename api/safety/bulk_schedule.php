<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/workflow_engine.php';
require_once __DIR__ . '/../../include/training_flow.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

clms_training_ensure_schema($conn);

$request_ids = $_POST['request_ids'] ?? [];
$session_id = intval($_POST['session_id'] ?? 0);

if (empty($request_ids) || !$session_id) {
    header("Location: ../../pages/safety/training_requests.php?error=Missing selections");
    exit;
}

$session = db_single($conn, "SELECT * FROM training_schedule WHERE id=?", 'i', [$session_id]);
if (!$session || $session['session_status'] !== 'open') {
    header("Location: ../../pages/safety/training_requests.php?error=Invalid session");
    exit;
}

$needed = count($request_ids);
$available = $session['capacity'] - $session['enrolled_count'];

if ($needed > $available) {
    header("Location: ../../pages/safety/training_requests.php?error=Session capacity exceeded (Available: $available)");
    exit;
}

mysqli_begin_transaction($conn);

try {
    $linked = 0;
    foreach ($request_ids as $req_id) {
        $req = db_single(
            $conn,
            "SELECT tr.*
             FROM training_requests tr
             JOIN workmen w ON w.id = tr.workman_id
             WHERE tr.id = ?
               AND (
                   tr.status = 'contractor_confirmed'
                   OR (
                       tr.status = 'pending'
                       AND LOWER(COALESCE(w.execution_training_status, '')) = 'approved'
                       AND LOWER(COALESCE(w.safety_enrollment_status, 'pending')) = 'approved'
                   )
               )
             LIMIT 1",
            'i',
            [$req_id]
        );
        if (!$req) continue;

        $workman_id = $req['workman_id'];

        if ($req['status'] === 'contractor_confirmed') {
            db_execute(
                $conn,
                "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
                 VALUES (?, ?, ?, 'pending', 'pending', NOW())
                 ON DUPLICATE KEY UPDATE session_id = VALUES(session_id)",
                'iii',
                [$session_id, $workman_id, $req_id]
            );
            $linked++;
        } else {
            db_execute(
                $conn,
                "UPDATE training_requests
                 SET scheduled_date = ?, scheduled_shift = CASE WHEN ? >= '14:00:00' THEN 'evening' ELSE 'morning' END,
                     scheduled_venue = ?, scheduled_time = ?, status = 'scheduled', updated_at = NOW()
                 WHERE id = ?",
                'ssssi',
                [$session['session_date'], $session['session_time'], $session['location'], $session['session_time'], $req_id]
            );
        }

        // 3. Update workman training_status
        db_execute($conn, "UPDATE workmen SET training_status='training_scheduled' WHERE id=?", 'i', [$workman_id]);
        
        // 4. Log action
        logAuditAction($conn, $_SESSION['user_id'], $_SESSION['role'], "scheduled_training", "safety", "Workman ID: $workman_id assigned to session $session_id");
    }

    // 5. Update session enrolled count
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

    mysqli_commit($conn);
    header("Location: ../../pages/safety/training_requests.php?success=Scheduled $needed workers; $linked confirmed worker(s) linked to batch");
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: ../../pages/safety/training_requests.php?error=" . urlencode($e->getMessage()));
}

