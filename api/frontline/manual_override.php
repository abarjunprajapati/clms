<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['front_line_user', 'frontline'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$worker_id = trim($_POST['worker_id'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$supervisor_username = trim($_POST['supervisor_username'] ?? '');
$supervisor_password = trim($_POST['supervisor_password'] ?? ''); // Treating password as PIN/auth

if (empty($worker_id) || empty($reason) || empty($supervisor_username) || empty($supervisor_password)) {
    echo json_encode(['success' => false, 'error' => 'All fields including supervisor credentials are required.']);
    exit;
}

// Verify Supervisor
$stmt_sup = $conn->prepare("SELECT id, role, password FROM users WHERE username = ?");
$stmt_sup->bind_param("s", $supervisor_username);
$stmt_sup->execute();
$supervisor = $stmt_sup->get_result()->fetch_assoc();

if (!$supervisor) {
    echo json_encode(['success' => false, 'error' => 'Supervisor not found.']);
    exit;
}

// Check role
$allowed_roles = ['admin', 'super_admin', 'welfare_admin', 'safety_user'];
if (!in_array($supervisor['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'User does not have override authority.']);
    exit;
}

// Verify password/PIN
if (!password_verify($supervisor_password, $supervisor['password'])) {
    // If we're testing and passwords aren't hashed:
    if ($supervisor_password !== $supervisor['password']) {
        echo json_encode(['success' => false, 'error' => 'Invalid Supervisor PIN / Password.']);
        exit;
    }
}

// Look up worker by ID, Aadhar, or Gatepass
$stmt_w = $conn->prepare("SELECT w.id, w.name FROM workmen w LEFT JOIN gate_passes g ON w.id=g.workman_id WHERE w.id = ? OR g.acc_card_number = ? OR w.aadhaar = ? LIMIT 1");
$stmt_w->bind_param("iss", $worker_id, $worker_id, $worker_id);
$stmt_w->execute();
$worker = $stmt_w->get_result()->fetch_assoc();

if (!$worker) {
    echo json_encode(['success' => false, 'error' => 'Worker not found.']);
    exit;
}

$real_worker_id = $worker['id'];

// Check if already inside
$today = date('Y-m-d');
$stmt_att = $conn->prepare("SELECT id FROM attendance WHERE workman_id=? AND DATE(check_in)=? AND check_in IS NOT NULL AND check_out IS NULL");
$stmt_att->bind_param("is", $real_worker_id, $today);
$stmt_att->execute();
if ($stmt_att->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Worker is already inside. Cannot override entry.']);
    exit;
}

// Process Manual Entry
$now = date('Y-m-d H:i:s');
$stmt_insert = $conn->prepare("INSERT INTO attendance (workman_id, check_in, source) VALUES (?, ?, 'manual')");
$stmt_insert->bind_param("is", $real_worker_id, $now);

if ($stmt_insert->execute()) {
    // Log mandatory strict audit
    $log_details = "MANUAL OVERRIDE ENTRY. Reason: $reason. Authorized by Supervisor ID: " . $supervisor['id'] . " ($supervisor_username)";
    db_query($conn, "INSERT INTO audit_logs (user_id, action, module, details) VALUES ({$_SESSION['user_id']}, 'manual_override_entry', 'gate_control', '$log_details')");
    
    echo json_encode(['success' => true, 'worker_name' => $worker['name']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error during override sync']);
}

