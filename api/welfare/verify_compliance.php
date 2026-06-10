<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'pass_user']);

header('Content-Type: application/json');

ensureComplianceSchema($conn);
$conn->query("ALTER TABLE compliance MODIFY status ENUM('pending','verified','rejected','reupload_required') DEFAULT 'pending'");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS compliance_notices (
    id INT NOT NULL AUTO_INCREMENT,
    compliance_id INT NOT NULL,
    contractor_id INT NULL,
    notice_type VARCHAR(40) NOT NULL,
    notice_text TEXT NOT NULL,
    issued_by INT NULL,
    issued_at DATETIME NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    PRIMARY KEY (id),
    KEY idx_compliance_notice (compliance_id),
    KEY idx_contractor_notice (contractor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function issueComplianceNotice($conn, $complianceId, $status, $remarks, $userId, $attendanceCount, $challanWorkerCount) {
    $row = db_single($conn, "SELECT contractor_id, type, month_year FROM compliance WHERE id = ? LIMIT 1", 'i', [$complianceId]);
    $contractorId = $row ? (int)($row['contractor_id'] ?? 0) : 0;
    $type = strtoupper((string)($row['type'] ?? 'COMPLIANCE'));
    $month = (string)($row['month_year'] ?? '');
    $noticeType = $status === 'reupload_required' ? 'reupload_notice' : ($status === 'rejected' ? 'rejection_notice' : 'discrepancy_notice');
    $notice = "Compliance notice for $type $month. System attendance count: $attendanceCount. Challan worker count: $challanWorkerCount. Action: $status. Remarks: $remarks";
    db_execute(
        $conn,
        "INSERT INTO compliance_notices (compliance_id, contractor_id, notice_type, notice_text, issued_by, issued_at, status)
         VALUES (?, ?, ?, ?, ?, NOW(), 'open')",
        'iissi',
        [$complianceId, $contractorId, $noticeType, $notice, $userId]
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action !== 'verify_compliance' || $id <= 0 || !in_array($status, ['verified', 'rejected', 'reupload_required'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

if (($status === 'rejected' || $status === 'reupload_required') && empty($remarks)) {
    echo json_encode(['success' => false, 'message' => 'Remarks are required for rejection or re-upload request.']);
    exit;
}

$user_id = $_SESSION['user_id'];

$recordStmt = $conn->prepare("SELECT attendance_count, challan_worker_count FROM compliance WHERE id = ? LIMIT 1");
if (!$recordStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare validation query']);
    exit;
}

$recordStmt->bind_param("i", $id);
$recordStmt->execute();
$attendanceCount = 0;
$challanWorkerCount = 0;
$foundRecord = false;
$recordStmt->bind_result($attendanceCount, $challanWorkerCount);
if ($recordStmt->fetch()) {
    $foundRecord = true;
    $attendanceCount = (int)$attendanceCount;
    $challanWorkerCount = (int)$challanWorkerCount;
}
$recordStmt->close();

if (!$foundRecord) {
    echo json_encode(['success' => false, 'message' => 'Compliance record not found']);
    exit;
}

if ($status === 'verified' && $attendanceCount !== $challanWorkerCount) {
    issueComplianceNotice($conn, $id, 'discrepancy', 'Verification blocked due to attendance/challan mismatch.', $user_id, $attendanceCount, $challanWorkerCount);
    echo json_encode([
        'success' => false,
        'message' => "Worker validation mismatch: system attendance is $attendanceCount, challan paid for $challanWorkerCount. Ask reupload or reject with remarks."
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE compliance SET status = ?, verification_remarks = ?, verified_by = ?, verified_at = NOW() WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("ssii", $status, $remarks, $user_id, $id);
    if ($stmt->execute()) {
        if ($status === 'rejected' || $status === 'reupload_required') {
            issueComplianceNotice($conn, $id, $status, $remarks, $user_id, $attendanceCount, $challanWorkerCount);
        }
        // Log action
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, module, details) VALUES (?, ?, 'compliance_monitor', ?)");
        if ($logStmt) {
            $logAction = "compliance_" . $status;
            $details = "Compliance record ID $id marked as $status. Remarks: $remarks";
            $logStmt->bind_param("iss", $user_id, $logAction, $details);
            $logStmt->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
}
?>
