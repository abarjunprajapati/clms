<?php
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json');

$current_contractor_id = isset($_GET['current_id']) ? (int)$_GET['current_id'] : 0;

$sql = "SELECT id, contractor_name as name, vendor_code FROM contractors 
        WHERE status = 'approved' 
        AND id != ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_contractor_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'] . ' (' . $row['vendor_code'] . ')'
    ];
}

$stmt->close();
echo json_encode($data);
?>

