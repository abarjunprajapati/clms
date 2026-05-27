<?php
/**
 * Annexure 3A Data API
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/helpers.php';

try {
    $result = $conn->query("
    SELECT 
        a3.*,
        a3.representative_name as worker_name,
        c.name as contractor_name
    FROM annexure3a a3
    LEFT JOIN contractors c ON a3.contractor_id = c.id
    ");

    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);

} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

