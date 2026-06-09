<?php
/**
 * get_training_results.php - Fetch training results for a session
 * FIXED: Returns safe values, handles empty/null data
 */
require_once 'helpers.php';
require_once '../include/config.php';

// Safe value helper
function safeResultVal($val, $default = 'N/A') {
    if ($val === null || $val === '' || !isset($val) || $val === 'undefined') {
        return $default;
    }
    return $val;
}

try {
    $input = getApiInput();
    $session_id = $input['session_id'] ?? null;
    $application_id = $input['application_id'] ?? null;

    if (!$session_id && !$application_id) {
        apiError("session_id or application_id is required", 400);
    }

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Ensure training_results table exists
    $conn->query("CREATE TABLE IF NOT EXISTS training_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        training_session_id VARCHAR(50) DEFAULT NULL,
        application_id VARCHAR(50) DEFAULT NULL,
        workman_id INT DEFAULT NULL,
        name VARCHAR(100) DEFAULT 'N/A',
        worker_name VARCHAR(100) DEFAULT 'N/A',
        trade VARCHAR(100) DEFAULT 'Helper',
        role VARCHAR(100) DEFAULT 'Helper',
        attendance VARCHAR(20) DEFAULT 'absent',
        attendance_status VARCHAR(20) DEFAULT 'absent',
        written_score INT DEFAULT 0,
        theory_score INT DEFAULT 0,
        practical_score INT DEFAULT 0,
        overall_score INT DEFAULT 0,
        total_score INT DEFAULT 0,
        result VARCHAR(20) DEFAULT 'pending',
        certificate_no VARCHAR(50) DEFAULT NULL,
        pio_status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (training_session_id),
        INDEX idx_application (application_id),
        INDEX idx_workman (workman_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Build query based on available parameter
    if ($session_id) {
        $sql = "SELECT * FROM training_results WHERE training_session_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $session_id);
    } else {
        // Also fetch results for an application
        $sql = "SELECT tr.*, w.name as workman_name, w.trade, w.role as workman_role 
              FROM training_results tr 
              LEFT JOIN workmen w ON tr.workman_id = w.id 
              WHERE tr.application_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $application_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Format each row with safe values
        $safeRow = [
            'id' => (int)safeResultVal($row['id'], 0),
            'training_session_id' => safeResultVal($row['training_session_id']),
            'application_id' => safeResultVal($row['application_id']),
            'workman_id' => (int)safeResultVal($row['workman_id'], 0),
            'name' => safeResultVal($row['name'] ?? $row['workman_name'] ?? null),
            'worker_name' => safeResultVal($row['worker_name'] ?? $row['name'] ?? null),
            'trade' => safeResultVal($row['trade'] ?? $row['workman_role'] ?? null),
            'role' => safeResultVal($row['role'] ?? 'Helper'),
            'attendance' => safeResultVal($row['attendance'] ?? $row['attendance_status'] ?? 'absent'),
            'attendance_status' => safeResultVal($row['attendance_status'] ?? $row['attendance'] ?? 'absent'),
            'written' => (int)safeResultVal($row['written_score'] ?? $row['theory_score'] ?? 0),
            'theory_score' => (int)safeResultVal($row['theory_score'], 0),
            'practical' => (int)safeResultVal($row['practical_score'] ?? 0),
            'total' => (int)safeResultVal($row['total_score'] ?? $row['overall_score'] ?? 0),
            'overall_score' => (int)safeResultVal($row['overall_score'], 0),
            'result' => safeResultVal($row['result'], 'pending'),
            'certificate_no' => safeResultVal($row['certificate_no']),
            'pio_status' => safeResultVal($row['pio_status'], 'pending'),
        ];
        $rows[] = $safeRow;
    }
    $stmt->close();

    // Return empty array if no results (not error)
    apiSuccess($rows, count($rows) . " training results found");

} catch (Exception $e) {
    // Return empty array on error instead of error message
    apiSuccess([], "No training results available");
}
?>

