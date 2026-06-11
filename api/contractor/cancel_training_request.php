<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor_user', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/workflow_engine.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);
$reason = trim($_POST['reason'] ?? 'Cancelled by contractor');

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Missing request ID']);
    exit;
}

$contractor_id = $_SESSION['contractor_id'] ?? 0;
if ($_SESSION['role'] === 'super_admin') {
    $contractor_id = 0; // Bypass for super admin
}

// Fetch the request and worker info
$sql = "
    SELECT tr.*, w.email, w.name as worker_name, c.name as contractor_name
    FROM training_requests tr
    JOIN workmen w ON tr.workman_id = w.id
    LEFT JOIN contractors c ON w.contractor_id = c.id
    WHERE tr.id = ? AND tr.status IN ('pending', 'welfare_pending', 'exec_pending')
";
$params = [$request_id];
if ($contractor_id) {
    $sql .= " AND tr.contractor_id = ?";
    $params[] = $contractor_id;
}

$stmt = $conn->prepare($sql);
if ($contractor_id) {
    $stmt->bind_param("ii", $request_id, $contractor_id);
} else {
    $stmt->bind_param("i", $request_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Request not found, already scheduled, or access denied.']);
    exit;
}

$request = $result->fetch_assoc();

// Update status to cancelled
$stmt = $conn->prepare("UPDATE training_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $request_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to cancel request in database']);
    exit;
}

// Update workman training_status to cancelled
$stmt = $conn->prepare("UPDATE workmen SET training_status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $request['workman_id']);
$stmt->execute();

// Send professional email to workman
if (!empty($request['email'])) {
    $to = $request['email'];
    $subject = "Cancellation of Safety Training Request";
    $message = "Dear " . htmlspecialchars($request['worker_name']) . ",\n\n";
    $message .= "We regret to inform you that your upcoming safety training request has been cancelled by your contractor (" . htmlspecialchars($request['contractor_name']) . ").\n\n";
    $message .= "Reason for cancellation: " . htmlspecialchars($reason) . "\n\n";
    $message .= "Please contact your contractor or the safety department for further instructions regarding rescheduling.\n\n";
    $message .= "Best Regards,\n" . htmlspecialchars($request['contractor_name']);
    
    $headers = "From: noreply@clms.local\r\n";
    $headers .= "Reply-To: noreply@clms.local\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    @mail($to, $subject, $message, $headers);
}

echo json_encode(['success' => true, 'message' => 'Training request cancelled successfully. Workman notified via email.']);
