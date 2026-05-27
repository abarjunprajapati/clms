<?php
session_start();
header('Content-Type: application/json');
include '../../include/config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die(json_encode(['success' => false, 'message' => 'Invalid ID']));
}

$vendor_code = $_SESSION['contractor_id'] ?? '';
$customer_code = $_SESSION['customer_code'] ?? '';

if ($vendor_code) {
    $data = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE id = ? AND vendor_code = ?", 'is', [$id, $vendor_code]);
} else if ($customer_code) {
    $data = db_single($conn, "SELECT * FROM contractor_annexure3a WHERE id = ? AND customer_code = ?", 'is', [$id, $customer_code]);
} else {
    $data = null;
}

if (!$data) {
    die(json_encode(['success' => false, 'message' => 'Submission not found or access denied']));
}

$docs = db_fetch_all($conn, "SELECT * FROM contractor_documents WHERE annexure3a_id = ?", 'i', [$id]);

echo json_encode([
    'success' => true,
    'data' => $data,
    'documents' => $docs
]);
?>
