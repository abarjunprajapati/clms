<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$session_date = $_POST['session_date'] ?? null;
$session_time = $_POST['session_time'] ?? null;
$location = $_POST['location'] ?? null;
$training_type = $_POST['training_type'] ?? 'induction';
$capacity = intval($_POST['capacity'] ?? 30);
$trainer_name = $_POST['trainer_name'] ?? '';
$remarks = $_POST['remarks'] ?? '';

if (!$session_date || !$session_time || !$location) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Auto-generate batch number if not provided
$batch_number = 'BATCH-' . date('Ymd') . '-' . rand(100, 999);

$sql = "INSERT INTO training_schedule (session_date, session_time, location, training_type, capacity, trainer_name, remarks, session_status, batch_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssisss", $session_date, $session_time, $location, $training_type, $capacity, $trainer_name, $remarks, $batch_number);

if ($stmt->execute()) {
    $session_id = $conn->insert_id;
    $stmt->close();

    // AUTO-LINKING LOGIC: Find workers scheduled for this date/location/time and link them
    // Map shift to time if needed for comparison (though here we have the exact time)
    $workers_to_link = db_fetch_all($conn, "
        SELECT id, workman_id FROM training_requests 
        WHERE scheduled_date = ? AND LOWER(TRIM(scheduled_venue)) = LOWER(TRIM(?)) 
        AND (scheduled_time = ? OR (scheduled_shift = 'morning' AND ? = '09:00:00') OR (scheduled_shift = 'evening' AND ? = '14:00:00'))
        AND status IN ('scheduled', 'contractor_confirmed')
    ", 'sssss', [$session_date, $location, $session_time, $session_time, $session_time]);

    foreach ($workers_to_link as $w) {
        db_execute($conn, 
            "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
             VALUES (?, ?, ?, 'pending', 'pending', NOW())
             ON DUPLICATE KEY UPDATE session_id = VALUES(session_id)",
            'iii', [$session_id, $w['workman_id'], $w['id']]
        );
    }
    
    // Update count
    db_execute($conn, "UPDATE training_schedule SET enrolled_count = (SELECT COUNT(*) FROM training_session_workers WHERE session_id = ?) WHERE id = ?", 'ii', [$session_id, $session_id]);

    header("Location: ../../pages/safety/training_schedule.php?success=Session created and " . count($workers_to_link) . " workers linked.");
} else {
    $error = $conn->error;
    $stmt->close();
    // If it's an AJAX call, return JSON, otherwise show error or redirect
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'error' => $error]);
    } else {
        header("Location: ../../pages/safety/training_schedule.php?error=" . urlencode($error));
    }
}

