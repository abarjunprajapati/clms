<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include 'include/config.php';

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';

if (!$type || !in_array($type, ['workman','supervisor','representative']) || !$id) {
  ob_end_clean();
  echo json_encode(['success' => false, 'error' => 'Invalid params']);
  exit;
}

$table = match($type) {
  'workman' => 'workmen',
  'supervisor' => 'supervisors',
  'representative' => 'representatives',
  default => ''
};
if (!$table) {
  ob_end_clean();
  echo json_encode(['success' => false, 'error' => 'Invalid type']);
  exit;
};
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

$sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE `id` = ?";
$params[] = $id;
$types .= 's';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute() && $stmt->affected_rows > 0) {
  ob_end_clean();
  echo json_encode(['success' => true]);
} else {
  ob_end_clean();
  echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>



