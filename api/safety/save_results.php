<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../api/WorkflowEngine.php';

header('Content-Type: application/json');

$session_id = intval($_POST['session_id'] ?? 0);
$results = $_POST['result'] ?? [];
$valid_tills = $_POST['valid_till'] ?? [];
$remarks_data = $_POST['result_remarks'] ?? [];

if (!$session_id || empty($results)) {
    echo json_encode(["success" => false, "message" => "No results to save"]);
    exit;
}

// Check session status
$session = db_single($conn, "SELECT session_status FROM training_schedule WHERE id=?", 'i', [$session_id]);
if (!$session || $session['session_status'] == 'completed') {
    echo json_encode(["success" => false, "message" => "Session locked"]);
    exit;
}

mysqli_begin_transaction($conn);
try {
    foreach ($results as $workman_id => $res) {
        // Normalize results to pass/fail
        $res = strtolower($res);
        if ($res === 'passed') $res = 'pass';
        if ($res === 'failed') $res = 'fail';

        $valid = $valid_tills[$workman_id] ?? null;
        $rem = $remarks_data[$workman_id] ?? '';
        
        // Fetch attendance status for this worker in this session
        $mapping = db_single($conn, "SELECT attendance_status FROM training_session_workers WHERE session_id=? AND workman_id=?", 'ii', [$session_id, $workman_id]);
        
        if ($mapping && $mapping['attendance_status'] == 'present') {
            // Update mapping table
            db_execute($conn, "UPDATE training_session_workers SET result=?, valid_till=?, remarks=? WHERE session_id=? AND workman_id=?", 'sssii', [$res, $valid, $rem, $session_id, $workman_id]);
            
            // Update workmen status if result is pass or fail
            $final_status = ($res == 'pass') ? 'training_passed' : 'training_failed';
            $safety_flag = ($res == 'pass') ? 1 : 0;
            db_execute($conn, "UPDATE workmen SET training_status=?, training_valid_till=?, safety_training_status=?, updated_at=NOW() WHERE id=?", 'ssii', [$final_status, $valid, $safety_flag, $workman_id]);
            
            // Sync with training_requests status
            $req_status = ($res == 'pass') ? 'passed' : 'failed';
            db_execute($conn, "UPDATE training_requests SET status = ?, conduct_remarks = ?, updated_at = NOW() WHERE workman_id = ? AND status IN ('scheduled', 'contractor_confirmed')", 'ssi', [$req_status, $rem, $workman_id]);
        } else {
            // If absent, result is automatically failed
            db_execute($conn, "UPDATE training_session_workers SET result='fail', remarks='Marked Fail due to Absence' WHERE session_id=? AND workman_id=?", 'ii', [$session_id, $workman_id]);
            db_execute($conn, "UPDATE workmen SET training_status='training_failed', safety_training_status=0, updated_at=NOW() WHERE id=?", 'i', [$workman_id]);
            
            // Sync with training_requests status
            db_execute($conn, "UPDATE training_requests SET status = 'failed', conduct_remarks = 'Absent in session', updated_at = NOW() WHERE workman_id = ? AND status IN ('scheduled', 'contractor_confirmed')", 'i', [$workman_id]);
        }
    }

    $apps = db_fetch_all(
        $conn,
        "SELECT DISTINCT application_no FROM workmen WHERE id IN (" . implode(',', array_fill(0, count($results), '?')) . ")",
        str_repeat('i', count($results)),
        array_map('intval', array_keys($results))
    );
    foreach ($apps as $app) {
        $appNo = $app['application_no'] ?? '';
        if (empty($appNo)) continue;
        
        $pending = db_count(
            $conn,
            "SELECT COUNT(*) FROM workmen WHERE application_no = ? AND training_status NOT IN ('training_passed','pass','qualified','completed')",
            's',
            [$appNo]
        );
        if ($pending === 0) {
            WorkflowEngine::performAction($conn, $appNo, 'complete_training', $_SESSION['role'], (int)($_SESSION['user_id'] ?? 0), 'All workers passed safety training');
        }
    }
    mysqli_commit($conn);
    echo json_encode(["success" => true, "message" => "Results updated successfully"]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

