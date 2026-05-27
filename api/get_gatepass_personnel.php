<?php
/**
 * get_gatepass_personnel.php - Fetch personnel qualified for gate pass
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $input = getApiInput();
    // Validate application_id if required by standard, though not strictly filtering by it yet
    $application_id = getApplicationId($input);

    $sql = "
        SELECT
            tr.workman_id AS id,
            tr.name,
            tr.trade AS role,
            COALESCE(w.tempId, CONCAT('TMP-', tr.workman_id)) AS tempId,
            tr.result AS training,
            tr.attendance_status
        FROM training_results tr
        LEFT JOIN workmen w ON w.id = tr.workman_id
        WHERE LOWER(tr.result) = 'qualified'
          AND LOWER(tr.attendance_status) = 'present'
        ORDER BY tr.name ASC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    apiSuccess($rows);
} catch (Exception $e) {
    apiError($e->getMessage());
}
?>

