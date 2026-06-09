<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'new_clms';
$vendor_code = $_GET['vendor_code'] ?? '1100908';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$sql = "SELECT a.id, a.workflow_status, a.submitted_at, c.vendor_code, c.contractor_name FROM annexure2a a JOIN contractors c ON a.contractor_id = c.id WHERE c.vendor_code = ?";;
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $vendor_code);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $rows]);
?>
