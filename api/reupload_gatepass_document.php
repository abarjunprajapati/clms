<?php
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/customer_portal_context.php';
require_once __DIR__ . '/api_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Only POST requests are allowed', 405);
}

function gpReuploadColumnExists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

try {
    clms_get_portal_contractor($conn);
    @mysqli_query($conn, "ALTER TABLE gate_pass_requests MODIFY status VARCHAR(30) DEFAULT 'pending'");
    @mysqli_query($conn, "ALTER TABLE gate_pass_request_workers MODIFY status VARCHAR(30) DEFAULT 'pending'");

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $docId = (int)($_POST['doc_id'] ?? 0);

    if (!$docId) {
        throw new Exception('Document ID missing');
    }
    if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please select a document to upload');
    }

    $contractor = $userId ? db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]) : null;
    if (!$contractor) {
        throw new Exception('Contractor registration not found');
    }

    $doc = db_single(
        $conn,
        "SELECT d.id, d.workman_id, d.document_type, d.status, w.contractor_id
         FROM documents d
         JOIN workmen w ON w.id = d.workman_id
         WHERE d.id = ? LIMIT 1",
        'i',
        [$docId]
    );

    if (!$doc || (int)$doc['contractor_id'] !== (int)$contractor['id']) {
        throw new Exception('Document not found for this contractor');
    }

    if (!in_array((string)$doc['status'], ['rejected', 'reupload_required'], true)) {
        throw new Exception('Only rejected documents can be re-uploaded');
    }

    $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        throw new Exception('Invalid file type. Upload PDF, JPG or PNG only.');
    }

    $dir = __DIR__ . '/../uploads/documents/';
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        throw new Exception('Could not create upload directory');
    }
    if (!is_writable($dir)) {
        throw new Exception('Upload directory is not writable');
    }

    $filename = (int)$doc['workman_id'] . '_reupload_' . $docId . '_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($_FILES['document']['tmp_name'], $dir . $filename)) {
        throw new Exception('Could not upload document');
    }

    $setParts = ["file_path = ?", "status = 'pending'"];
    if (gpReuploadColumnExists($conn, 'documents', 'remarks')) {
        $setParts[] = "remarks = ''";
    }
    if (gpReuploadColumnExists($conn, 'documents', 'uploaded_at')) {
        $setParts[] = "uploaded_at = NOW()";
    }
    if (gpReuploadColumnExists($conn, 'documents', 'updated_at')) {
        $setParts[] = "updated_at = NOW()";
    }

    db_execute(
        $conn,
        "UPDATE documents SET " . implode(', ', $setParts) . " WHERE id = ?",
        'si',
        [$filename, $docId]
    );

    $remainingRejected = db_count(
        $conn,
        "SELECT COUNT(*) FROM documents WHERE workman_id = ? AND status IN ('rejected', 'reupload_required')",
        'i',
        [(int)$doc['workman_id']]
    );

    if ($remainingRejected === 0) {
        db_execute(
            $conn,
            "UPDATE gate_pass_request_workers gprw
             JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
             SET gprw.status = 'pending', gpr.status = 'pending', gpr.updated_at = NOW()
             WHERE gprw.workman_id = ? AND gpr.contractor_id = ? AND gpr.status = 'reupload_required'",
            'ii',
            [(int)$doc['workman_id'], (int)$contractor['id']]
        );
    }

    apiSuccess(['doc_id' => $docId], 'Document re-uploaded successfully. It is pending verification again.');
} catch (Throwable $e) {
    error_log('Gate pass document reupload error: ' . $e->getMessage());
    apiError($e->getMessage(), 400);
}
?>
