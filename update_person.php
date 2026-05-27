<?php
include 'include/config.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';

if (!$type || !in_array($type, ['workman','supervisor','representative']) || !$id) {
  echo json_encode(['success' => false, 'error' => 'Invalid params']);
  exit;
}

$table = $type . 's';
$setParts = [];
$params = [];
$types = '';

foreach ($_POST as $key => $value) {
  if (in_array($key, ['type','id'])) continue;
  $setParts[] = "`$key` = ?";
  $params[] = $value;
  $types .= 's';
}

$setParts[] = '`updated_at` = NOW()';
$params[] = date('Y-m-d H:i:s');
$types .= 's';

$sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE `id` = ?";
$params[] = $id;
$types .= 's';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute() && $stmt->affected_rows > 0) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>


