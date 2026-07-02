<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => "PHP Error ($errno): $errstr in $errfile on line $errline"]);
    exit;
});
set_exception_handler(function($e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => "Uncaught Exception: " . $e->getMessage()]);
    exit;
});
try {
    require_once __DIR__ . '/../../include/auth.php';
    checkAuth(['contractor', 'customer', 'super_admin']);
    require_once __DIR__ . '/../../include/config.php';
    require_once __DIR__ . '/../../include/workflow_engine.php';

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    $request_id = intval($_POST['request_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'Cancelled by contractor');

    if (!$request_id) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Missing request ID']);
        exit;
    }

    $contractor_id = $_SESSION['contractor_id'] ?? 0;
    if (($_SESSION['role'] ?? '') === 'super_admin') {
        $contractor_id = 0; // Bypass for super admin
    }

    // Fetch the request and worker info
    $sql = "
        SELECT tr.*, w.email, w.name as worker_name, c.contractor_name as contractor_name
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
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    if ($contractor_id) {
        $stmt->bind_param("ii", $request_id, $contractor_id);
    } else {
        $stmt->bind_param("i", $request_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Request not found, already scheduled, or access denied.']);
        exit;
    }

    $request = $result->fetch_assoc();

    // Update status to cancelled
    $stmt = $conn->prepare("UPDATE training_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    if (!$stmt->execute()) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to cancel request in database']);
        exit;
    }

    // Update workman training_status to cancelled
    try {
        $stmt2 = $conn->prepare("UPDATE workmen SET training_status = 'cancelled' WHERE id = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $request['workman_id']);
            $stmt2->execute();
        }
    } catch (Exception $e) {
        // Ignore if workmen table does not allow 'cancelled'
    }

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

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'message' => 'Training request cancelled successfully. Workman notified via email.']);
    exit;

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Server Exception: ' . $e->getMessage()]);
    exit;
}
