<?php
require_once 'include/auth.php';
require_once 'include/config.php';
checkAuth(['contractor', 'customer', 'super_admin', 'welfare_admin', 'welfare_user']);
require_csrf();

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if (!$type || !in_array($type, ['workman','supervisor','representative']) || !$id) {
  echo json_encode(['success' => false, 'error' => 'Invalid params']);
  exit;
}

$table = $type . 's';
$setParts = [];
$params = [];
$types = '';
$columns = [];
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
if ($colRes) {
  while ($col = mysqli_fetch_assoc($colRes)) {
    $columns[$col['Field']] = true;
  }
}

foreach ($_POST as $key => $value) {
  if (in_array($key, ['type','id'])) continue;
  if (!preg_match('/^[A-Za-z0-9_]+$/', $key) || !isset($columns[$key])) continue;
  $setParts[] = "`$key` = ?";
  $params[] = $value;
  $types .= 's';
}

$setParts[] = '`updated_at` = NOW()';
if (count($setParts) === 1) {
  echo json_encode(['success' => false, 'error' => 'No valid fields supplied']);
  exit;
}

$sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE `id` = ?";
$params[] = $id;
$types .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute() && $stmt->affected_rows > 0) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>


