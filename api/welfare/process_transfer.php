<?php
session_start();
header('Content-Type: application/json');
include '../include/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$processed_by = $_SESSION['user_id'] ?? null;
if (!$processed_by) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$worker_id = intval($_POST['worker_id'] ?? 0);
$from_cid = intval($_POST['from_contractor_id'] ?? 0);
$to_cid = intval($_POST['to_contractor_id'] ?? 0);
$noc_reference = trim($_POST['noc_reference'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

if (!$worker_id || !$to_cid || !$noc_reference) {
    die(json_encode(['success' => false, 'message' => 'Mandatory fields missing']));
}

if ($from_cid === $to_cid) {
    die(json_encode(['success' => false, 'message' => 'Source and destination contractors cannot be the same']));
}

// Transaction start
$conn->begin_transaction();

try {
    // 1. Update workman contractor_id
    $stmt1 = $conn->prepare("UPDATE workmen SET contractor_id = ?, status = 'active' WHERE id = ?");
    $stmt1->bind_param("ii", $to_cid, $worker_id);
    $stmt1->execute();

    // 2. Log transfer
    $stmt2 = $conn->prepare("INSERT INTO worker_transfer_logs (worker_id, from_contractor_id, to_contractor_id, transfer_date, noc_reference, remarks, processed_by) VALUES (?, ?, ?, NOW(), ?, ?, ?)");
    $stmt2->bind_param("iiissi", $worker_id, $from_cid, $to_cid, $noc_reference, $remarks, $processed_by);
    $stmt2->execute();

    // 3. Block old gate pass if any (should probably re-verify for new contractor)
    $stmt3 = $conn->prepare("UPDATE gate_passes SET status = 'blocked', remarks = 'Blocked due to transfer' WHERE workman_id = ? AND status = 'active'");
    $stmt3->bind_param("i", $worker_id);
    $stmt3->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Transfer completed']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
