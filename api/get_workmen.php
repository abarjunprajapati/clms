<?php
/**
 * Get Workmen API
 * Returns workmen filtered by application_id (optional) and/or type (optional).
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    $application_id = validateApplicationId($input);
    $type = $input['type'] ?? null;

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "SELECT * FROM workmen WHERE application_id = ?";
    $params = [$application_id];
    $types = 's';

    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    apiSuccess($data);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>

