<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once './include/auth.php';
require_once './include/config.php';
checkAuth(['contractor', 'customer', 'super_admin', 'welfare_admin', 'welfare_user']);

error_log("API HIT: get_representatives - " . json_encode($_REQUEST));

$response = ['success' => false, 'data' => [], 'counts' => []];

try {
    $application_id = $_GET['application_id'] ?? null;
    if (!$application_id && !in_array($_SESSION['role'] ?? '', ['super_admin', 'welfare_admin', 'welfare_user'], true)) {
        throw new Exception('application_id is required');
    }

    $sql = "SELECT * FROM representatives WHERE 1=1";
    $params = [];
    $types = '';

    if ($application_id) {
        $sql .= " AND application_id = ?";
        $params[] = $application_id;
        $types .= 's';
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $response = [
        'success' => true,
        'data' => $data,
        'counts' => ['total' => count($data)]
    ];

} catch (Throwable $e) {
    $response['error'] = $e->getMessage();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
?>


