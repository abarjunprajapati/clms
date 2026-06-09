<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['front_line_user', 'frontline'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$worker_id = $_POST['worker_id'] ?? null;
$gate_id = $_POST['gate_id'] ?? 'Main_Gate';

if (!$worker_id) {
    echo json_encode(['success' => false, 'error' => 'Missing worker ID']);
    exit;
}

// Fetch worker and contractor details to log in sap_attendance
$stmt_w = $conn->prepare("SELECT w.acc_card_number, w.name, c.contractor_name FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id WHERE w.id = ?");
$stmt_w->bind_param("i", $worker_id);
$stmt_w->execute();
$w_data = $stmt_w->get_result()->fetch_assoc();

if (!$w_data || !$w_data['acc_card_number']) {
    echo json_encode(['success' => false, 'error' => 'Worker has no ACC Number']);
    exit;
}

$acc_no = $w_data['acc_card_number'];

// Security: Double check they aren't already inside (race condition check)
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT id FROM sap_attendance WHERE acc_no=? AND attendance_date=? AND in_time IS NOT NULL AND out_time IS NULL");
$stmt->bind_param("ss", $acc_no, $today);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Worker is already inside.']);
    exit;
}

// Mark attendance
$now = date('H:i:s');
$stmt_insert = $conn->prepare("INSERT INTO sap_attendance (acc_no, worker_name, contractor_name, attendance_date, in_time, attendance_status, punch_status, sap_sync_status, sync_source) VALUES (?, ?, ?, ?, ?, 'PRESENT', 'IN', 'SYNCED', 'MANUAL_GATE')");
$contractor_name = $w_data['contractor_name'] ?: 'Direct';
$stmt_insert->bind_param("sssss", $acc_no, $w_data['name'], $contractor_name, $today, $now);

if ($stmt_insert->execute()) {
    // Log in audit
    db_query($conn, "INSERT INTO audit_logs (user_id, action, module, details) VALUES ({$_SESSION['user_id']}, 'gate_entry_allowed', 'gate_control', 'Entry at $gate_id for worker $worker_id (ACC: $acc_no)')");
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error during entry sync']);
}

