<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor', 'customer']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../WorkflowEngine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Only POST allowed');
}

try {
    $gate_pass_id = (int)($_POST['gate_pass_id'] ?? 0);
    $new_valid_to = $_POST['new_valid_to'] ?? '';

    if (!$gate_pass_id || !$new_valid_to) {
        throw new Exception('Missing gate pass ID or new validity date.');
    }

    $pass = db_single($conn, "SELECT * FROM gate_passes WHERE id = ?", "i", [$gate_pass_id]);
    if (!$pass) {
        throw new Exception('Gate pass not found.');
    }

    if ($pass['is_temporary'] != 1) {
        throw new Exception('Only temporary passes can be extended this way.');
    }

    $currentValidTo = $pass['valid_to'];
    if (strtotime($new_valid_to) <= strtotime($currentValidTo)) {
        throw new Exception('New validity date must be after the current validity date.');
    }

    // Check GM declaration upload
    if (!isset($_FILES['gm_declaration']) || $_FILES['gm_declaration']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('GM Declaration file is required for extension.');
    }

    $file = $_FILES['gm_declaration'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
        throw new Exception('Invalid file type for GM declaration. Only PDF/JPG/PNG allowed.');
    }

    $uploadDir = '../../uploads/gate_passes/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $fileName = 'gm_decl_' . $gate_pass_id . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        throw new Exception('Failed to upload GM Declaration');
    }
    $uploadPath = 'uploads/gate_passes/' . $fileName;

    // The logic: Extension must be continuous. By updating valid_to directly, it's inherently continuous.
    // We update valid_to, set is_extended = 1, and save gm_declaration_path
    $stmt = $conn->prepare("UPDATE gate_passes SET valid_to = ?, is_extended = 1, gm_declaration_path = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_valid_to, $uploadPath, $gate_pass_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update gate pass.');
    }

    // Also update workman valid_to
    $conn->query("UPDATE workmen SET valid_to = '{$new_valid_to}', temp_valid_to = '{$new_valid_to}' WHERE id = " . (int)$pass['workman_id']);

    apiSuccess(['gate_pass_id' => $gate_pass_id, 'new_valid_to' => $new_valid_to], 'Temporary pass extended successfully.');
} catch (Exception $e) {
    apiError($e->getMessage());
}
