<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/config.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'welfare_admin', 'welfare_user', 'welfare'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$allowed_statuses = ['approved', 'rejected', 'pending'];

if (!$id || !$status || !in_array($status, $allowed_statuses, true)) {
    die(json_encode(['success' => false, 'message' => 'ID or Status missing']));
}

function a3_welfare_column_exists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function a3_welfare_ensure_column($conn, $table, $column, $definition) {
    if (a3_welfare_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
}

a3_welfare_ensure_column($conn, 'contractor_annexure3a', 'approval_reason', 'TEXT NULL');
a3_welfare_ensure_column($conn, 'contractor_annexure3a', 'approval_file', 'VARCHAR(255) NULL');
a3_welfare_ensure_column($conn, 'contractor_annexure3a', 'verified_by', 'INT NULL');
a3_welfare_ensure_column($conn, 'contractor_annexure3a', 'verified_at', 'DATETIME NULL');

$approval_file = null;
if (!empty($_FILES['approval_file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['approval_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        die(json_encode(['success' => false, 'message' => 'Only PDF, JPG or PNG files are allowed.']));
    }
    $uploadDir = __DIR__ . '/../../uploads/approvals/';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0777, true)) {
        die(json_encode(['success' => false, 'message' => 'Unable to create approval upload directory.']));
    }
    $fileName = 'a3_' . $id . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($_FILES['approval_file']['tmp_name'], $uploadDir . $fileName)) {
        die(json_encode(['success' => false, 'message' => 'Approval attachment upload failed.']));
    }
    $approval_file = 'approvals/' . $fileName;
}

$setParts = ['status = ?', 'approval_reason = ?', 'verified_by = ?', 'verified_at = NOW()'];
$types = 'ssi';
$params = [$status, $reason, (int)($_SESSION['user_id'] ?? 0)];
if ($approval_file !== null) {
    $setParts[] = 'approval_file = ?';
    $types .= 's';
    $params[] = $approval_file;
}
$types .= 'i';
$params[] = $id;

$sql = "UPDATE contractor_annexure3a SET " . implode(', ', $setParts) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    if ($status === 'approved') {
        db_execute($conn, "UPDATE contractor_documents SET status = 'verified', updated_at = NOW() WHERE annexure3a_id = ?", 'i', [$id]);
    } elseif ($status === 'rejected') {
        db_execute($conn, "UPDATE contractor_documents SET status = 'rejected', updated_at = NOW() WHERE annexure3a_id = ?", 'i', [$id]);
    }

    // Log in history table
    $a3_data = db_single($conn, "SELECT vendor_code, customer_code, work_order_no, insurance_policy_no, insurance_validity, insurance_workers_count FROM contractor_annexure3a WHERE id = ?", 'i', [$id]);
    if ($a3_data) {
        db_execute($conn,
            "INSERT INTO contractor_annexure3a_history (annexure3a_id, vendor_code, customer_code, work_order_no, insurance_policy_no, insurance_validity, insurance_workers_count, status, reason) VALUES (?,?,?,?,?,?,?,?,?)",
            'isssssiss',
            [$id, $a3_data['vendor_code'], $a3_data['customer_code'], $a3_data['work_order_no'], $a3_data['insurance_policy_no'], $a3_data['insurance_validity'], $a3_data['insurance_workers_count'], $status, $reason ?: 'Status updated by Welfare']
        );

        $contractor = db_single($conn, "SELECT id, application_no FROM contractors WHERE vendor_code = ?", 's', [$a3_data['vendor_code']]);
        if ($contractor && !empty($contractor['application_no'])) {
            $stage = $status === 'approved' ? '3a_approved' : ($status === 'rejected' ? '3a_rejected' : '3a_submitted');
            db_execute(
                $conn,
                "INSERT INTO application_workflow (application_id, contractor_id, current_stage, overall_status)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE current_stage = VALUES(current_stage), overall_status = VALUES(overall_status), updated_at = NOW()",
                'siss',
                [$contractor['application_no'], (int)$contractor['id'], $stage, $stage]
            );
        }
    }
    echo json_encode(['success' => true, 'message' => 'Status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
}
?>
