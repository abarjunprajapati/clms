<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor','super_admin']);
header('Content-Type: application/json; charset=utf-8');

$work_order = $_GET['work_order'] ?? '';
$work_order = trim($work_order);
try {
    $rows = [];
    if ($work_order !== '') {
        $safe = mysqli_real_escape_string($conn, $work_order);
        // If work_orders table exists and has project_name
        $res = mysqli_query($conn, "SHOW TABLES LIKE 'work_orders'");
        if ($res && mysqli_num_rows($res) > 0) {
            $sql = "SELECT id, project_name AS text, project_code AS code FROM work_orders WHERE work_order_no = '$safe' LIMIT 100";
            $r = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($r)) {
                $rows[] = ['id' => $row['id'] ?? $row['code'] ?? $row['text'], 'text' => $row['text'] ?? $row['code'] ?? $row['id']];
            }
        } else {
            // Fallback: return work_order as single project entry
            $rows[] = ['id' => $work_order, 'text' => $work_order];
        }
    }
    echo json_encode(['data' => $rows], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['data'=>[], 'error' => $e->getMessage()]);
}
