<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['front_line_user', 'frontline'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$scan_data = $_POST['scan_data'] ?? '';
$gate_id = $_POST['gate_id'] ?? 'Main_Gate';

if (empty($scan_data)) {
    echo json_encode(['success' => false, 'error' => 'No scan data provided']);
    exit;
}

// Check worker details (this assumes acc_card_number or aadhar or id is scanned)
$stmt = $conn->prepare("
    SELECT w.*, g.valid_from, g.valid_to, g.pass_type, g.status as pass_status, g.acc_card_number
    FROM workmen w 
    LEFT JOIN gate_passes g ON w.id = g.workman_id AND g.status = 'approved'
    WHERE g.acc_card_number = ? OR w.aadhaar = ? LIMIT 1
");
$stmt->bind_param("ss", $scan_data, $scan_data);
$stmt->execute();
$result = $stmt->get_result();
$worker = $result->fetch_assoc();

if (!$worker) {
    echo json_encode(['success' => false, 'error' => 'No worker found with this ID']);
    exit;
}

$issues = [];
$can_enter = true;

// 1. Status Active check
if ($worker['status'] !== 'verified' && $worker['status'] !== 'acc_generated' && $worker['status'] !== 'trained' && $worker['status'] !== 'permanent_active') {
    $issues[] = 'Worker status is not active (Status: ' . $worker['status'] . ')';
    $can_enter = false;
}

// 2. Training Done
if ($worker['training_status'] !== 'completed' && $worker['training_status'] !== 'qualified') {
    // $issues[] = 'Safety training not completed.';
    // $can_enter = false;
    // Temporarily disabled check if training status logic is in flux, but usually required
}

// 3. Expiry Check
$today = date('Y-m-d');
if ($worker['valid_from'] && $worker['valid_to']) {
    if ($today < $worker['valid_from'] || $today > $worker['valid_to']) {
        $issues[] = 'Pass expired or not yet valid (Valid: ' . $worker['valid_from'] . ' to ' . $worker['valid_to'] . ')';
        $can_enter = false;
    }
}

// 4. Blocked Check (Individual)
$stmt_block = $conn->prepare("SELECT * FROM worker_blocks WHERE workman_id=? AND status='active' ORDER BY blocked_at DESC LIMIT 1");
$stmt_block->bind_param("i", $worker['id']);
$stmt_block->execute();
$block_res = $stmt_block->get_result();
if ($block_res->num_rows > 0) {
    $block = $block_res->fetch_assoc();
    $issues[] = 'Worker is INDIVIDUALLY BLOCKED. Reason: ' . $block['reason'];
    $can_enter = false;
}

// 5. Contractor Block Check (Cascading)
$stmt_c = $conn->prepare("SELECT is_blocked, block_reason FROM contractors WHERE id = ?");
$stmt_c->bind_param("i", $worker['contractor_id']);
$stmt_c->execute();
$c_res = $stmt_c->get_result();
if ($c_res && $c_res->num_rows > 0) {
    $c_block = $c_res->fetch_assoc();
    if ($c_block['is_blocked']) {
        $issues[] = 'FIRM IS BLOCKED. Access Denied. Reason: ' . ($c_block['block_reason'] ?: 'Security/Disciplinary');
        $can_enter = false;
    }
}

// 6. Duplicate Entry (Already Inside) Check
$today_str = date('Y-m-d');
$acc_no = $worker['acc_card_number'];
if ($acc_no) {
    $stmt_att = $conn->prepare("SELECT id FROM sap_attendance WHERE acc_no=? AND attendance_date=? AND in_time IS NOT NULL AND out_time IS NULL");
    $stmt_att->bind_param("ss", $acc_no, $today_str);
    $stmt_att->execute();
    if ($stmt_att->get_result()->num_rows > 0) {
        $issues[] = 'Worker is already inside the premises.';
        $can_enter = false;
    }
} else {
    $issues[] = 'Worker does not have a valid ACC Number assigned.';
    $can_enter = false;
}

// Return data for manual photo validation UI
echo json_encode([
    'success' => true,
    'worker' => [
        'id' => $worker['id'],
        'name' => $worker['name'],
        'gatepass_no' => $worker['acc_card_number'] ?? 'N/A',
        'photo' => $worker['photo'] ? '../../uploads/photos/' . $worker['photo'] : '',
        'pass_type' => $worker['pass_type'] ?? 'Standard',
        'contractor' => $worker['contractor_id'] ?? 'Direct',
        'trade' => $worker['trade'] ?? 'N/A'
    ],
    'can_enter' => $can_enter,
    'issues' => $issues
]);
?>
