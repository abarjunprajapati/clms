<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../../include/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    sendJson(403, 'Unauthorized access.');
}

$data = getJsonInput();
$request_id = (int)($data['request_id'] ?? 0);
$action = $data['action'] ?? ''; // 'approve', 'reject'
$existing_contractor_id = $_SESSION['user_id'];

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    sendJson(400, 'Invalid input.');
}

try {
    $conn->begin_transaction();
    
    // Resolve actual contractors.id
    $c_stmt = $conn->prepare("SELECT id FROM contractors WHERE user_id = ?");
    if (!$c_stmt) throw new Exception("Contractor prepare failed: " . $conn->error);
    $c_stmt->bind_param("i", $existing_contractor_id);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result()->fetch_assoc();
    $db_contractor_id = $c_res ? $c_res['id'] : 0;

    // Verify the request belongs to this contractor
    $stmt = $conn->prepare("SELECT id, workman_id FROM noc_requests WHERE id = ? AND from_contractor_id = ? AND noc_status = 'pending'");
    if (!$stmt) throw new Exception("NOC request prepare failed: " . $conn->error);
    $stmt->bind_param("ii", $request_id, $db_contractor_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    
    if (!$req) {
        sendJson(404, 'NOC request not found or already processed.');
    }
    
    $new_status = ($action === 'approve') ? 'approved_by_contractor' : 'rejected_by_contractor';
    
    $upd = $conn->prepare("UPDATE noc_requests SET noc_status = ? WHERE id = ?");
    if (!$upd) throw new Exception("NOC update prepare failed: " . $conn->error);
    $upd->bind_param("si", $new_status, $request_id);
    $upd->execute();

    // Record NOC issuance date in workmen table if approved
    if ($action === 'approve') {
        // Try adding the column if it doesn't exist, to prevent failure
        $conn->query("ALTER TABLE workmen ADD COLUMN IF NOT EXISTS noc_issued_at TIMESTAMP NULL");
        
        $w_upd = $conn->prepare("UPDATE workmen SET noc_issued_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$w_upd) throw new Exception("Workmen update prepare failed: " . $conn->error);
        $w_upd->bind_param("i", $req['workman_id']);
        $w_upd->execute();
    }

    $conn->commit();
    sendJson(200, 'NOC ' . ucfirst($action) . 'd successfully.');

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    sendJson(500, 'Error: ' . $e->getMessage());
}
