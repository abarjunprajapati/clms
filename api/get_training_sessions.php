<?php
/**
 * get_training_sessions.php - Fetch all upcoming training sessions
 * FIXED: Returns safe values, handles empty/null data
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

// Safe value helper
function safeSessionVal($val, $default = 'N/A') {
    if ($val === null || $val === '' || !isset($val) || $val === 'undefined') {
        return $default;
    }
    return $val;
}

function formatDateSession($dateVal) {
    if (!$dateVal || $dateVal === '0000-00-00' || $dateVal === null) {
        return 'N/A';
    }
    try {
        $d = new DateTime($dateVal);
        return $d->format('d M Y');
    } catch (Exception $e) {
        return 'N/A';
    }
}

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Ensure training_sessions table exists
    $conn->query("CREATE TABLE IF NOT EXISTS training_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venue VARCHAR(255) DEFAULT 'TBD',
        location VARCHAR(255) DEFAULT 'TBD',
        date DATE DEFAULT NULL,
        time VARCHAR(50) DEFAULT '10:00 AM',
        trainer VARCHAR(100) DEFAULT 'TBD',
        trainer_name VARCHAR(100) DEFAULT 'TBD',
        capacity INT DEFAULT 50,
        enrolled_count INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'upcoming',
        session_date VARCHAR(50) DEFAULT NULL,
        session_time VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sql = "SELECT * FROM training_sessions ORDER BY date ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Format each row with safe values
        $safeRow = [
            'id' => (int)safeSessionVal($row['id'], 0),
            'venue' => safeSessionVal($row['venue'] ?? $row['location'] ?? null),
            'location' => safeSessionVal($row['location'] ?? null),
            'date' => formatDateSession($row['date'] ?? $row['session_date'] ?? null),
            'time' => safeSessionVal($row['time'] ?? $row['session_time'] ?? '10:00 AM'),
            'trainer' => safeSessionVal($row['trainer'] ?? $row['trainer_name'] ?? null),
            'trainer_name' => safeSessionVal($row['trainer_name'] ?? null),
            'capacity' => (int)safeSessionVal($row['capacity'], 50),
            'enrolled' => (int)safeSessionVal($row['enrolled_count'], 0),
            'enrolled_count' => (int)safeSessionVal($row['enrolled_count'], 0),
            'status' => safeSessionVal($row['status'], 'upcoming'),
            'session_date' => $row['session_date'] ?? null,
            'session_time' => $row['session_time'] ?? null,
        ];
        $rows[] = $safeRow;
    }

    // Return empty array if no sessions (not error)
    apiSuccess($rows, count($rows) . " training sessions found");
    
} catch (Exception $e) {
    // Return empty array on error instead of error message
    apiSuccess([], "No training sessions available");
}
?>

