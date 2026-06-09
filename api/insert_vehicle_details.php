<?php
require_once 'json_error_handler.php';

$response = ['success' => false];

try {
    require_once __DIR__ . '/../include/config.php';

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('POST required');
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        http_response_code(400);
        throw new Exception('Invalid JSON input');
    }

    $vehicle_number = trim($input['vehicle_number'] ?? '');
    $vehicle_type = trim($input['vehicle_type'] ?? '');
    $driver_name = trim($input['driver_name'] ?? '');
    $application_id = trim($input['application_id'] ?? '');

    if ($vehicle_number === '' || $vehicle_type === '' || $driver_name === '' || $application_id === '') {
        http_response_code(400);
        throw new Exception('vehicle_number, vehicle_type, driver_name, and application_id are required');
    }

    $createSql = "
        CREATE TABLE IF NOT EXISTS vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_number VARCHAR(50),
            vehicle_type VARCHAR(50),
            driver_name VARCHAR(100),
            application_id VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";

    if (!$conn->query($createSql)) {
        throw new Exception('Vehicle table setup failed: ' . $conn->error);
    }

    $stmt = $conn->prepare("
        INSERT INTO vehicles (vehicle_number, vehicle_type, driver_name, application_id)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ssss', $vehicle_number, $vehicle_type, $driver_name, $application_id);
    if (!$stmt->execute()) {
        throw new Exception('Insert failed: ' . $stmt->error);
    }

    $response = [
        'success' => true,
        'message' => 'Vehicle added successfully',
        'id' => $stmt->insert_id
    ];
    $stmt->close();
} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    $response['error'] = $e->getMessage();
}

jsonErrorFlush();
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

