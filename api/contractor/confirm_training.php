<?php
// api/contractor/confirm_training.php
// Contractor confirms a scheduled training
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor', 'customer', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/customer_portal_context.php';
header('Content-Type: application/json');

clms_get_portal_contractor($conn);

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
if ($req['status'] !== 'scheduled') {
    echo json_encode(['success' => false, 'error' => 'This request is not in scheduled state']);
    exit;
}

$ok = db_execute($conn,
    "UPDATE training_requests SET contractor_confirmed=1, contractor_remarks=?, status='contractor_confirmed', updated_at=NOW() WHERE id=?",
    'si', [$contractor_remarks, $req_id]
);

if ($ok) {
    // Update workmen table status
    db_execute($conn, 
        "UPDATE workmen SET safety_training_status = 'TRAINING_CONFIRMED', training_status = 'scheduled' WHERE id = ?", 
        'i', [$req['workman_id']]
    );
    // Ensure worker is assigned to a session in training_session_workers
    // This is a safety check in case scheduling didn't map them correctly
    if ($req['scheduled_date'] && $req['scheduled_venue']) {
        $final_time = $req['scheduled_time'] ?: ($req['scheduled_shift'] === 'morning' ? '09:00:00' : '14:00:00');
        $session = db_single($conn, 
            "SELECT id FROM training_schedule WHERE session_date = ? AND location = ? AND session_time = ?", 
            'sss', [$req['scheduled_date'], trim($req['scheduled_venue']), $final_time]
        );
        
        if ($session) {
            $session_id = $session['id'];
            db_execute($conn,
                "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
                 VALUES (?, ?, ?, 'pending', 'pending', NOW())
                 ON DUPLICATE KEY UPDATE session_id = VALUES(session_id)",
                'iii', [$session_id, $req['workman_id'], $req_id]
            );
        }
    }

    // Notify safety dept (get a safety user to notify - or just log it)
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details) VALUES (?,?,?,?)",
        'isss', [$user_id, 'training_confirmed', 'training_requests', "Request ID $req_id confirmed by contractor with remarks: $contractor_remarks"]
    );
    echo json_encode(['success' => true, 'message' => 'Training confirmed. Safety team has been notified.']);
} else {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
}
?>
