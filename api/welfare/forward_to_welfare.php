<?php
include '../../include/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$application_id = $_POST['application_id'] ?? null;

if (!$application_id) {
    echo json_encode(['success' => false, 'error' => 'Missing application_id']);
    exit;
}

// 🔒 BACKEND VALIDATION: ALL persons must be PIO approved
$check_stmt = $conn->prepare("
    SELECT COUNT(*) as pending_count 
    FROM gate_passes gp 
    JOIN training_results tr ON gp.workman_id = tr.workman_id 
    WHERE gp.application_id = ? AND tr.pio_status != 'approved'
");
$check_stmt->bind_param("s", $application_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();

if ($check_result['pending_count'] > 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'Cannot forward: ' . $check_result['pending_count'] . ' persons not PIO approved'
    ]);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// ✅ ALL APPROVED → UPDATE gate_passes
$stmt = $conn->prepare("
    UPDATE gate_passes 
    SET status = 'verified', approval_level = 2 
    WHERE application_id = ?
");
$stmt->bind_param("s", $application_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'success' => true, 
        'application_id' => $application_id, 
        'message' => 'Forwarded to Welfare - status=verified, approval_level=2'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No matching application or update failed']);
}

$stmt->close();
$conn->close();
?>


