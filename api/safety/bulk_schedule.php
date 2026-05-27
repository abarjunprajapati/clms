<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/workflow_engine.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

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
    foreach ($request_ids as $req_id) {
        $req = db_single($conn, "SELECT * FROM training_requests WHERE id=?", 'i', [$req_id]);
        if (!$req) continue;

        $workman_id = $req['workman_id'];

        // 1. Map worker to session
        db_execute($conn, "INSERT INTO training_session_workers (session_id, workman_id) VALUES (?, ?)", 'ii', [$session_id, $workman_id]);

        // 2. Update training_requests status
        db_execute($conn, "UPDATE training_requests SET status='scheduled' WHERE id=?", 'i', [$req_id]);

        // 3. Update workman training_status
        db_execute($conn, "UPDATE workmen SET training_status='training_scheduled' WHERE id=?", 'i', [$workman_id]);
        
        // 4. Log action
        logAuditAction($conn, $_SESSION['user_id'], $_SESSION['role'], "scheduled_training", "safety", "Workman ID: $workman_id assigned to session $session_id");
    }

    // 5. Update session enrolled count
    db_execute($conn, "UPDATE training_schedule SET enrolled_count = enrolled_count + ? WHERE id=?", 'ii', [$needed, $session_id]);

    mysqli_commit($conn);
    header("Location: ../../pages/safety/training_requests.php?success=Scheduled $needed workers");
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: ../../pages/safety/training_requests.php?error=" . urlencode($e->getMessage()));
}

