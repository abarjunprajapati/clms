<?php
session_start();
require_once __DIR__ . '/../include/auth.php';
checkAuth(['contractor', 'customer', 'super_admin']);
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/customer_portal_context.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed.');
    }

    $worker_id = (int)($_POST['worker_id'] ?? 0);
    if (!$worker_id) {
        throw new Exception('Worker ID is required.');
    }

    $role = $_SESSION['role'] ?? '';
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    clms_get_portal_contractor($conn);
    $params = [$worker_id];
    $types = 'i';
    $where = 'id = ?';

    if (in_array($role, ['contractor', 'customer'], true)) {
        $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$user_id]);
        if (!$contractor) {
            throw new Exception('Contractor record not found.');
        }
        $where .= ' AND contractor_id = ?';
        $types .= 'i';
        $params[] = (int)$contractor['id'];
    }

    $worker = db_single($conn, "SELECT id, name FROM workmen WHERE $where LIMIT 1", $types, $params);
    if (!$worker) {
        throw new Exception('Worker not found or not allowed to delete.');
    }

    $status = $_POST['status'] ?? '';
    if ($status !== 'active' && $status !== 'inactive') {
        $current = db_single($conn, "SELECT worker_status FROM workmen WHERE $where LIMIT 1", $types, $params);
        $status = ($current && $current['worker_status'] === 'active') ? 'inactive' : 'active';
    }

    $updateTypes = 's' . $types;
    $updateParams = array_merge([$status], $params);

    $updated = db_execute($conn, "UPDATE workmen SET worker_status = ? WHERE $where", $updateTypes, $updateParams);
    if (!$updated) {
        throw new Exception('Unable to update worker status.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Worker status updated successfully to ' . $status . '.'
    ]);
} catch (Throwable $e) {
    error_log('[DELETE_WORKMAN_4A] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
