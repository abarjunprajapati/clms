<?php
/**
 * schedule_training.php
 * Schedule safety training sessions for contractors
 * Returns: { success: true, data: [...] }
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    
    $application_id = $input['application_id'] ?? null;
    $venue = trim($input['venue'] ?? '');
    $date = trim($input['date'] ?? '');
    $time = trim($input['time'] ?? '');
    $trainer = trim($input['trainer'] ?? '');
    $capacity = intval($input['capacity'] ?? 50);
    
    // Validation
    if (!$application_id) {
        apiError('application_id is required', 400);
    }
    if (!$venue) {
        apiError('venue is required', 400);
    }
    if (!$date) {
        apiError('date is required', 400);
    }
    
    // Validate date format
    $dateObj = date_create($date);
    if (!$dateObj || $dateObj === false) {
        apiError('Invalid date format. Use YYYY-MM-DD', 400);
    }
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Generate session ID
    $session_id = 'TS-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand())), 0, 6));
    
    // Insert training session
    $stmt = $conn->prepare("
        INSERT INTO training_sessions 
        (id, application_id, venue, date, time, trainer, capacity, status, enrolled_count, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 0, NOW())
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ssssssi', $session_id, $application_id, $venue, $date, $time, $trainer, $capacity);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // Update workflow status to training_pending
    $updateStmt = $conn->prepare("
        UPDATE application_workflow 
        SET current_stage = 'training', training_status = 'scheduled', updated_at = NOW()
        WHERE application_id = ?
    ");
    
    if ($updateStmt) {
        $updateStmt->bind_param('s', $application_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Also update annexure2a status if exists
    $annStmt = $conn->prepare("
        UPDATE annexure2a 
        SET workflow_status = 'training_pending', updated_at = NOW()
        WHERE application_id = ?
    ");
    
    if ($annStmt) {
        $annStmt->bind_param('s', $application_id);
        $annStmt->execute();
        $annStmt->close();
    }
    
    // Return success
    $result = [
        'session_id' => $session_id,
        'application_id' => $application_id,
        'venue' => $venue,
        'date' => $date,
        'time' => $time,
        'trainer' => $trainer,
        'capacity' => $capacity,
        'status' => 'scheduled'
    ];
    
    apiSuccess($result, 'Training session scheduled successfully');
    
} catch (Exception $e) {
    apiError($e->getMessage(), 500);
}
?>

