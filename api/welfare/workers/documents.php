<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
        if (!$worker_id) {
            throw new Exception("Worker ID is required.");
        }
        $query = "SELECT * FROM worker_documents WHERE worker_id = $worker_id ORDER BY created_at DESC";
        $result = clms_db_query($conn, $query);
        $docs = [];
        while ($row = clms_db_fetch_assoc($result)) {
            $docs[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $docs]);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // 'upload', 'verify', 'reject'
    $worker_id = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;
    $user_id = 1; // TODO: Get from session

    if (!$worker_id || empty($action)) {
        throw new Exception("Worker ID and action are required.");
    }

    if ($action === 'upload') {
        $document_type = isset($_POST['document_type']) ? clms_db_real_escape_string($conn, $_POST['document_type']) : '';
        $document_number = isset($_POST['document_number']) ? clms_db_real_escape_string($conn, $_POST['document_number']) : '';
        $expiry_date = isset($_POST['expiry_date']) && !empty($_POST['expiry_date']) ? "'".clms_db_real_escape_string($conn, $_POST['expiry_date'])."'" : "NULL";
        
        if (empty($document_type) || !isset($_FILES['file'])) {
            throw new Exception("Document type and file are required.");
        }

        // Handle file upload
        $uploadDir = '../../../uploads/worker_documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . '_' . basename($_FILES['file']['name']);
        $targetFilePath = $uploadDir . $fileName;
        $dbPath = 'uploads/worker_documents/' . $fileName;

        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            throw new Exception("File upload failed.");
        }

        $insertQuery = "INSERT INTO worker_documents (worker_id, document_type, document_number, document_path, expiry_date, uploaded_by)
                        VALUES ($worker_id, '$document_type', '$document_number', '$dbPath', $expiry_date, $user_id)";
                        
        if (!clms_db_query($conn, $insertQuery)) {
            throw new Exception("Failed to save document record: " . clms_db_error($conn));
        }

        echo json_encode(['status' => 'success', 'message' => 'Document uploaded successfully', 'path' => $dbPath]);

    } elseif ($action === 'verify' || $action === 'reject') {
        $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
        $remarks = isset($_POST['remarks']) ? clms_db_real_escape_string($conn, trim($_POST['remarks'])) : '';

        if (!$document_id) {
            throw new Exception("Document ID is required.");
        }
        if ($action === 'reject' && empty($remarks)) {
            throw new Exception("Rejection reason is required.");
        }

        $status = $action === 'verify' ? 'Verified' : 'Rejected';
        $reupload = $action === 'reject' ? 'TRUE' : 'FALSE';

        $updateQuery = "UPDATE worker_documents 
                        SET verification_status = '$status', 
                            verified_by = $user_id, 
                            verified_at = NOW(),
                            rejection_reason = '$remarks',
                            reupload_requested = $reupload
                        WHERE document_id = $document_id";
                        
        if (!clms_db_query($conn, $updateQuery)) {
            throw new Exception("Failed to update document status: " . clms_db_error($conn));
        }

        echo json_encode(['status' => 'success', 'message' => "Document successfully {$status}"]);
    } else {
        throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
