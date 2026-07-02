<?php
ob_start();
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/training_flow.php';

header('Content-Type: application/json');
clms_training_ensure_schema($conn);

function reEnrollJson($payload, $code = 200) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reEnrollJson(['success' => false, 'error' => 'Invalid request method.'], 405);
}

$userId     = (int)($_SESSION['user_id'] ?? 0);
$workmanId  = (int)($_POST['workman_id'] ?? 0);
$mode       = trim((string)($_POST['mode'] ?? '')); // 'same_po' or 'fresh_po'

if (!$workmanId) {
    reEnrollJson(['success' => false, 'error' => 'Worker ID is required.'], 400);
}

// Verify this contractor owns the worker
$contractorId = 0;
$cRow = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? LIMIT 1", 'i', [$userId]);
if ($cRow) $contractorId = (int)$cRow['id'];

$worker = db_single($conn,
    "SELECT w.id, w.name, w.contractor_id, w.training_status, w.work_order_no, w.execution_training_status, w.execution_training_reviewed_by
     FROM workmen w
     WHERE w.id = ? AND w.contractor_id = ?
     LIMIT 1",
    'ii', [$workmanId, $contractorId]
);

if (!$worker) {
    reEnrollJson(['success' => false, 'error' => 'Worker not found or access denied.'], 403);
}

// Only failed or absent workers can be re-enrolled. Some result screens update
// training_session_workers/training_results before the request row is synced.
$trainingStatus = strtolower(trim((string)($worker['training_status'] ?? '')));
$latestRequestRow = db_single($conn,
    "SELECT id, status FROM training_requests WHERE workman_id = ? ORDER BY id DESC LIMIT 1",
    'i', [$workmanId]
);
$latestStatus = strtolower(trim((string)($latestRequestRow['status'] ?? '')));
$latestSessionResultRow = db_single($conn,
    "SELECT result, training_request_id FROM training_session_workers
     WHERE workman_id = ? AND LOWER(COALESCE(result, 'pending')) NOT IN ('', 'pending')
     ORDER BY id DESC LIMIT 1",
    'i', [$workmanId]
);
$latestResultRow = db_single($conn,
    "SELECT result, training_request_id FROM training_results
     WHERE workman_id = ? AND LOWER(COALESCE(result, '')) <> ''
     ORDER BY id DESC LIMIT 1",
    'i', [$workmanId]
);
$latestResult = strtolower(trim((string)($latestSessionResultRow['result'] ?? $latestResultRow['result'] ?? '')));

$isFailed = in_array($trainingStatus, ['failed', 'fail', 'training_failed', 'absent', 'rejected', 'welfare_rejected', 'correction_required'], true)
         || in_array($latestStatus, ['failed', 'fail', 'training_failed', 'absent', 'rejected', 'welfare_rejected', 'correction_required'], true)
         || in_array($latestResult, ['failed', 'fail', 'absent', 'rejected'], true);

if (!$isFailed) {
    reEnrollJson(['success' => false, 'error' => "Only workers with a failed/absent/rejected result can be re-enrolled. (Status: $latestStatus)"], 400);
}

// Check attempt limit (max 3 in 30 days), including legacy rows whose request status was not synced.
$attemptRow = db_single($conn,
    "SELECT COUNT(DISTINCT attempt_key) AS cnt FROM (
        SELECT CONCAT('req:', tr.id) AS attempt_key
        FROM training_requests tr
        WHERE tr.workman_id = ?
          AND LOWER(COALESCE(tr.status,'pending')) IN ('failed','fail','absent','passed')
          AND tr.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        UNION ALL
        SELECT CONCAT('req:', COALESCE(tsw.training_request_id, tsw.id)) AS attempt_key
        FROM training_session_workers tsw
        WHERE tsw.workman_id = ?
          AND LOWER(COALESCE(tsw.result, '')) IN ('pass', 'passed', 'fail', 'failed')
          AND tsw.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        UNION ALL
        SELECT CONCAT('res:', COALESCE(trr.training_request_id, trr.id)) AS attempt_key
        FROM training_results trr
        WHERE trr.workman_id = ?
          AND LOWER(COALESCE(trr.result, '')) IN ('pass', 'passed', 'fail', 'failed')
          AND trr.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ) attempts",
    'iii', [$workmanId, $workmanId, $workmanId]
);
$attempts = (int)($attemptRow['cnt'] ?? 0);

if ($attempts >= 3) {
    reEnrollJson(['success' => false, 'error' => 'Maximum 3 training attempts allowed within 30 days. This worker cannot be re-enrolled yet.'], 400);
}

if ($mode === 'same_po') {
    // ── SAME PWO/PO: directly push into training queue ─────────────────
    // Check no active/pending request already exists
    $activeRequest = db_single($conn,
        "SELECT id, status FROM training_requests tr
         WHERE tr.workman_id = ?
           AND tr.status IN ('pending_eo','pending_safety','welfare_pending','pending','scheduled','contractor_confirmed')
           AND NOT EXISTS (
               SELECT 1 FROM training_session_workers tsw_done
               WHERE tsw_done.training_request_id = tr.id
                 AND LOWER(COALESCE(tsw_done.result, 'pending')) IN ('pass', 'passed', 'fail', 'failed')
           )
         ORDER BY tr.id DESC LIMIT 1",
        'i', [$workmanId]
    );

    if ($activeRequest) {
        reEnrollJson([
            'success' => false,
            'error' => 'An active training request already exists for this worker (status: ' . htmlspecialchars($activeRequest['status']) . '). Please wait for it to complete.'
        ], 400);
    }

    // Reset worker training status back to failed/training_pending so they can be re-queued
    db_execute($conn,
        "UPDATE workmen SET training_status = 'training_pending', updated_at = NOW() WHERE id = ?",
        'i', [$workmanId]
    );

    // Create new training request using same contractor/PO details
    $eoApproved = strtolower((string)($worker['execution_training_status'] ?? '')) === 'approved'
               && (int)($worker['execution_training_reviewed_by'] ?? 0) > 0;

    $newStatus = $eoApproved ? 'pending_safety' : 'pending_eo';
    $remarks = 'Re-enrolled after training failure. Same work order retained. Attempt ' . ($attempts + 1) . ' of 3.';

    $newReqId = 0;
    $ok = db_execute($conn,
        "INSERT INTO training_requests
            (workman_id, contractor_id, training_type, requested_date, preferred_date, preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
         VALUES (?, ?, 'Safety Induction', CURDATE(), NULL, 'morning', ?, 'contractor_re_enroll', ?, ?, NOW(), NOW())",
        'iisis',
        [(int)$workmanId, (int)$contractorId, $remarks, (int)$userId, $newStatus]
    );

    if ($ok) {
        $newReqId = (int)mysqli_insert_id($conn);
    }

    if ($newReqId) {
        reEnrollJson([
            'success' => true,
            'message' => 'Worker "' . htmlspecialchars($worker['name']) . '" has been re-queued for safety training (Attempt ' . ($attempts + 1) . '/3).',
            'redirect' => 'training_request.php'
        ]);
    } else {
        reEnrollJson(['success' => false, 'error' => 'Failed to create re-enroll request. Please try again.'], 500);
    }

} else {
    // ── FRESH PWO/PO: just return signal to open full form ──────────────
    reEnrollJson([
        'success' => true,
        'mode' => 'open_form',
        'message' => 'Please fill the form with new PWO/PO details.',
        'worker_id' => $workmanId,
        'worker_name' => $worker['name'],
        'work_order_no' => $worker['work_order_no'] ?? ''
    ]);
}
