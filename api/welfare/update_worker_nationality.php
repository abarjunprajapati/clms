<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $workerId = (int)($_POST['worker_id'] ?? 0);
    $nationality = trim((string)($_POST['nationality'] ?? 'Indian'));
    $state = trim((string)($_POST['state'] ?? ''));
    $district = trim((string)($_POST['district'] ?? ''));

    if ($workerId <= 0) {
        throw new Exception('Worker ID is required');
    }
    if ($nationality === '') {
        $nationality = 'Indian';
    }

    @mysqli_query($conn, "ALTER TABLE workmen ADD COLUMN nationality VARCHAR(100) NULL DEFAULT 'Indian'");

    $stmt = $conn->prepare("UPDATE workmen SET nationality = ?, state = ?, district = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('sssi', $nationality, $state, $district, $workerId);
    if (!$stmt->execute()) {
        throw new Exception('Update failed: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Worker nationality updated']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
