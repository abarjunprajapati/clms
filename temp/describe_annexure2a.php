<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'new_clms';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$sql = "DESCRIBE annexure2a";
$res = $conn->query($sql);
if (!$res) {
    die('Error: ' . $conn->error);
}
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $rows]);
?>
