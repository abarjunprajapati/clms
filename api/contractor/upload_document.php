<?php
session_start();
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['contractor', 'customer'], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

try {
    require_once __DIR__ . '/../../include/customer_portal_context.php';
    clms_get_portal_contractor($conn);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests are allowed.");
    }

    $user_id = $_SESSION['user_id'];
    $doc_type = $_POST['doc_type'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if (empty($doc_type)) {
        throw new Exception("Document type is required.");
    }

    // Get contractor ID
    $stmt = $conn->prepare("SELECT id FROM contractors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $contractor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$contractor) {
        throw new Exception("Contractor profile not found.");
    }
    $c_id = $contractor['id'];

    if (!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed or no file selected.");
    }

    $file = $_FILES['doc_file'];
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception("File size exceeds 5MB limit.");
    }

    $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) {
        throw new Exception("Only PDF, JPG, and PNG files are allowed.");
    }

    $upload_dir = '../../uploads/contractor_docs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = $doc_type . '_' . $c_id . '_' . time() . '.' . $ext;
    $target_path = $upload_dir . $filename;
    $db_path = '../../uploads/contractor_docs/' . $filename; // Store relative path for UI

    // Ensure metadata column exists
    $conn->query("ALTER TABLE contractor_documents ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER remarks");

    // Capture metadata
    $metadata = [];
    if ($doc_type === 'workmen_compensation') {
        $metadata['valid_from'] = $_POST['wc_valid_from'] ?? null;
        $metadata['valid_to'] = $_POST['wc_valid_to'] ?? null;
        $metadata['workers_covered'] = $_POST['wc_workers'] ?? null;
        $metadata['rate_of_wages'] = $_POST['wc_wages'] ?? null;
        $metadata['exceeds_esic'] = $_POST['wc_esic_override'] ?? 'No';
    } else if ($doc_type === 'cla_license') {
        $metadata['license_no'] = $_POST['cla_license_no'] ?? null;
        $metadata['issue_date'] = $_POST['cla_issue_date'] ?? null;
        if (!empty($_POST['cla_issue_date'])) {
            $metadata['expiry_date'] = date('Y-m-d', strtotime('+1 year -1 day', strtotime($_POST['cla_issue_date'])));
        }
    }
    
    $meta_json = !empty($metadata) ? json_encode($metadata) : null;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Check if document already exists
        $check = $conn->prepare("SELECT id FROM contractor_documents WHERE contractor_id = ? AND doc_type = ?");
        $check->bind_param("is", $c_id, $doc_type);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $sql = "UPDATE contractor_documents SET file_path = ?, original_name = ?, status = 'pending', remarks = ?, metadata = ?, uploaded_at = NOW() WHERE id = ?";
            $upd = $conn->prepare($sql);
            $upd->bind_param("ssssi", $db_path, $file['name'], $remarks, $meta_json, $existing['id']);
            $upd->execute();
            $upd->close();
        } else {
            $sql = "INSERT INTO contractor_documents (contractor_id, doc_type, file_path, original_name, status, remarks, metadata, uploaded_at) VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())";
            $ins = $conn->prepare($sql);
            $ins->bind_param("isssss", $c_id, $doc_type, $db_path, $file['name'], $remarks, $meta_json);
            $ins->execute();
            $ins->close();
        }

        // Snapshot ECP details into history when Workmen Compensation policy document is uploaded
        if ($doc_type === 'workmen_compensation') {
            $c_details = db_single($conn, "SELECT ecp_number, ecp_valid_from, ecp_valid_to, workers_ecp FROM contractors WHERE id = ?", 'i', [$c_id]);
            if ($c_details) {
                db_execute($conn, 
                    "INSERT INTO contractor_ecp_history (contractor_id, ecp_number, ecp_valid_from, ecp_valid_to, workers_ecp, file_path) VALUES (?,?,?,?,?,?)",
                    'isssis', [$c_id, $c_details['ecp_number'], $c_details['ecp_valid_from'], $c_details['ecp_valid_to'], $c_details['workers_ecp'], $db_path]
                );
            }
        }

        echo json_encode(["success" => true, "message" => "Document uploaded successfully."]);
    } else {
        throw new Exception("Failed to save uploaded file.");
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>

