<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor','super_admin']);
header('Content-Type: application/json; charset=utf-8');

$vendor = $_GET['contractor'] ?? ($_SESSION['vendor_code'] ?? '');
$vendor = trim($vendor);
try {
    $rows = [];
    // Prefer work_orders table if present
    $res = clms_db_query($conn, "SHOW TABLES LIKE 'work_orders'");
    if ($res && clms_db_num_rows($res) > 0) {
        // Try to filter by vendor if column exists
        $where = '';
        $colsRes = clms_db_query($conn, "SHOW COLUMNS FROM work_orders LIKE 'contractor_vendor_code'");
        if ($colsRes && clms_db_num_rows($colsRes) > 0 && $vendor !== '') {
            $safe = clms_db_real_escape_string($conn, $vendor);
            $where = " WHERE contractor_vendor_code = '$safe'";
        }
        $sql = "SELECT work_order_no AS id, work_order_no AS text, project_name, department FROM work_orders" . $where . " ORDER BY work_order_no DESC LIMIT 200";
        $r = clms_db_query($conn, $sql);
        while ($row = clms_db_fetch_assoc($r)) {
            $rows[] = $row;
        }
    } else {
        // Fallback to contractors table
        $safeV = clms_db_real_escape_string($conn, $vendor);
        if ($vendor !== '') {
            $r = clms_db_query($conn, "SELECT DISTINCT work_order_no AS id, work_order_no AS text FROM contractors WHERE vendor_code = '$safeV' AND work_order_no <> '' LIMIT 200");
            while ($row = clms_db_fetch_assoc($r)) $rows[] = $row;
        } else {
            // return recent work orders from contractors
            $r = clms_db_query($conn, "SELECT DISTINCT work_order_no AS id, work_order_no AS text FROM contractors WHERE work_order_no <> '' ORDER BY id DESC LIMIT 200");
            while ($row = clms_db_fetch_assoc($r)) $rows[] = $row;
        }
    }

    echo json_encode(['data' => $rows], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['data'=>[], 'error' => $e->getMessage()]);
}
