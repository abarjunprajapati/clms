<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['super_admin']);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => false, 'message' => 'Invalid input']);
    exit;
}

$officerId = (int)($input['officer_id'] ?? 0);
$depts = $input['depts'] ?? [];
$contractors = $input['contractors'] ?? [];

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer ID required']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // 1. Clear existing department assignments
    db_execute($conn, "DELETE FROM execution_officer_departments WHERE execution_officer_id = ?", 'i', [$officerId]);
    // 2. Add new department assignments
    foreach ($depts as $did) {
        db_execute($conn, "INSERT INTO execution_officer_departments (execution_officer_id, department_id) VALUES (?, ?)", 'ii', [$officerId, $did]);
    }

    // 3. Clear existing contractor assignments
    db_execute($conn, "DELETE FROM execution_officer_contractors WHERE execution_officer_id = ?", 'i', [$officerId]);
    // 4. Add new contractor assignments
    foreach ($contractors as $cid) {
        db_execute($conn, "INSERT INTO execution_officer_contractors (execution_officer_id, contractor_id) VALUES (?, ?)", 'ii', [$officerId, $cid]);
    }

    mysqli_commit($conn);
    echo json_encode(['status' => true, 'message' => 'Assignments updated successfully']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
