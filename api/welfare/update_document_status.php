<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'acc', 'pass_user']);
require_once '../../include/config.php';
require_once '../api_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Invalid request method', 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (empty($data['document_id']) || empty($data['status'])) {
    apiError('Document ID and status are required');
}

$doc_id = (int)$data['document_id'];
$status = $data['status']; // 'approved' or 'rejected'

if (!in_array($status, ['approved', 'rejected'])) {
    apiError('Invalid status value');
}

$sql = "UPDATE documents SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
$success = db_execute($conn, $sql, 'sii', [$status, $_SESSION['user_id'] ?? 0, $doc_id]);

if ($success) {
    apiSuccess(['status' => $status], 'Document status updated successfully');
} else {
    apiError('Failed to update document status');
}
?>
