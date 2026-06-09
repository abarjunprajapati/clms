<?php
/**
 * get_training_sessions.php - Fetch all upcoming training sessions
 * Updated: Auto-creates table if not exists and returns demo data
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Auto-create training_sessions table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS training_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_name VARCHAR(100) NOT NULL,
            venue VARCHAR(200) NOT NULL,
            trainer_name VARCHAR(100),
            date DATE NOT NULL,
            time TIME NOT NULL,
            capacity INT DEFAULT 30,
            enrolled_count INT DEFAULT 0,
            status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_session_date (date),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Check if table is empty and seed with demo data
    $check = $conn->query("SELECT COUNT(*) as cnt FROM training_sessions");
    $row = $check->fetch_assoc();
    if ($row['cnt'] == 0) {
        // Insert demo training sessions
        $conn->query("INSERT INTO training_sessions (session_name, venue, trainer_name, date, time, capacity, enrolled_count, status) VALUES
            ('Basic Safety Training - Batch 1', 'PWD Training Center, Sector 17', 'Mr. Rajesh Kumar', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', 30, 5, 'scheduled'),
            ('Basic Safety Training - Batch 2', 'PWD Training Center, Sector 17', 'Mr. Rajesh Kumar', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '09:00:00', 30, 0, 'scheduled'),
            ('Advanced Safety Training', 'PWD Training Center, Sector 22', 'Mrs. Priya Singh', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', 20, 12, 'scheduled')");
    }

    // Fetch all training sessions
    $sql = "SELECT * FROM training_sessions ORDER BY date ASC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates for frontend
        if ($row['date']) {
            $row['date'] = date('d M Y', strtotime($row['date']));
        }
        if ($row['time']) {
            $row['time'] = date('h:i A', strtotime($row['time']));
        }
        $rows[] = $row;
    }

    apiSuccess($rows, "Training sessions fetched successfully");

} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}
?>

