<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../../include/session.php';

// Only Contractors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    sendResponse(false, [], 'Unauthorized access.');
}

$data = getApiInput();
$search_term = trim((string)($data['search_term'] ?? '')); // Aadhaar or Temp ID
$requesting_contractor_id = $_SESSION['user_id'];

if (empty($search_term)) {
    sendResponse(false, [], 'Please provide Workman Aadhaar or Temp ID.');
}

try {
    // Find workman
    $stmt = $conn->prepare("SELECT id, contractor_id, is_in_common_pool FROM workmen WHERE aadhaar = ? OR temp_id = ?");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        sendResponse(false, [], 'Workman not found.');
    }
    
    $workman = $res->fetch_assoc();
    $workman_id = $workman['id'];
    $existing_contractor_id = $workman['contractor_id'];
    
    if ($existing_contractor_id == $requesting_contractor_id) {
        sendResponse(false, [], 'Workman is already attached to you.');
    }
    
    // Check if there's already a pending request
    $chk = $conn->prepare("SELECT id FROM noc_requests WHERE workman_id = ? AND noc_status IN ('pending', 'approved_by_contractor')");
    $chk->bind_param("i", $workman_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        sendResponse(false, [], 'An active NOC request already exists for this workman.');
    }

    $conn->begin_transaction();
    
    if ($workman['is_in_common_pool'] == 1) {
        // If in common pool, no need for existing contractor NOC. Directly goes to Welfare User.
        $status = 'approved_by_contractor'; // Skip contractor approval
        $existing_contractor_id = NULL; // Optional: set to null since they are in common pool
    } else {
        $status = 'pending'; // Needs existing contractor approval
    }

    $insert = $conn->prepare("INSERT INTO noc_requests (workman_id, to_contractor_id, from_contractor_id, noc_status) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiis", $workman_id, $requesting_contractor_id, $existing_contractor_id, $status);
    $insert->execute();

    $conn->commit();
    sendResponse(true, [], 'NOC Request submitted successfully. Status: ' . $status);

} catch (\Throwable $e) {
    if (isset($conn)) $conn->rollback();
    file_put_contents(__DIR__ . '/debug.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
    sendResponse(false, [], 'Error: ' . $e->getMessage());
}
