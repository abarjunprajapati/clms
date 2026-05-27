<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['front_line_user', 'frontline'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$scan_data = $_POST['scan_data'] ?? '';

if (empty($scan_data)) {
    echo json_encode(['success' => false, 'error' => 'No scan data provided']);
    exit;
}

// Fetch worker by acc_card_number or aadhar
$stmt = $conn->prepare("SELECT w.id, w.name FROM workmen w LEFT JOIN gate_passes g ON w.id=g.workman_id WHERE g.acc_card_number = ? OR w.aadhaar = ? LIMIT 1");
$stmt->bind_param("ss", $scan_data, $scan_data);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();

if (!$worker) {
    echo json_encode(['success' => false, 'error' => 'Worker not found.']);
    exit;
}

$worker_id = $worker['id'];
$today = date('Y-m-d');

// Find active attendance record
$stmt_att = $conn->prepare("SELECT id, check_in as entry_time FROM attendance WHERE workman_id=? AND DATE(check_in)=? AND check_out IS NULL ORDER BY check_in DESC LIMIT 1");
$stmt_att->bind_param("is", $worker_id, $today);
$stmt_att->execute();
$att = $stmt_att->get_result()->fetch_assoc();

if (!$att) {
    // Check if there was no entry today, or if they already exited
    echo json_encode(['success' => false, 'error' => 'No active entry record found for today.']);
    exit;
}

// Process exit
$now = date('Y-m-d H:i:s');
$stmt_upd = $conn->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
$stmt_upd->bind_param("si", $now, $att['id']);

if ($stmt_upd->execute()) {
    // Log in audit
    db_query($conn, "INSERT INTO audit_logs (user_id, action, module, details) VALUES ({$_SESSION['user_id']}, 'gate_exit_allowed', 'gate_control', 'Exit logged for worker $worker_id. Duration: Entry at {$att['entry_time']}')");
    
    echo json_encode(['success' => true, 'worker_name' => $worker['name'], 'entry_time' => $att['entry_time'], 'exit_time' => $now]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error during exit sync']);
}

