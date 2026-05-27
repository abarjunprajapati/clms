<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../SapDemo.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests are allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$acc_card_number = trim($input['acc_card_number'] ?? '');
$check_in_time = trim($input['check_in_time'] ?? date('Y-m-d H:i:s'));

if (empty($acc_card_number)) {
    echo json_encode(['success' => false, 'error' => 'ACC card number is required for attendance tracking.']);
    exit;
}

try {
    // Validate if the worker is active, permanent, and NOT blocked
    $worker = db_single(
        $conn,
        "SELECT id, is_blocked, status FROM workmen WHERE acc_card_number = ? LIMIT 1",
        's',
        [$acc_card_number]
    );

    if (!$worker) {
        throw new Exception("Invalid ACC card number. Worker not found.");
    }

    if ((int)$worker['is_blocked'] === 1) {
        throw new Exception("Access Denied: Worker is blocked.");
    }

    if (!in_array($worker['status'], ['acc_generated', 'permanent_active'])) {
        throw new Exception("Access Denied: Worker pass is not active or permanent.");
    }

    // Insert attendance log (assuming an `attendance` table exists based on schema)
    // Structure: id, workman_id, check_in, check_out, date
    $date = date('Y-m-d', strtotime($check_in_time));
    
    // Check if already checked in today
    $exists = db_single($conn, "SELECT id FROM attendance WHERE workman_id = ? AND date = ? LIMIT 1", 'is', [$worker['id'], $date]);
    
    if ($exists) {
        // Assume check out for mock
        db_execute($conn, "UPDATE attendance SET check_out = ? WHERE id = ?", 'si', [$check_in_time, $exists['id']]);
        $msg = "Checked Out successfully";
    } else {
        db_execute(
            $conn, 
            "INSERT INTO attendance (workman_id, check_in, date) VALUES (?, ?, ?)",
            'iss', 
            [$worker['id'], $check_in_time, $date]
        );
        $msg = "Checked In successfully";

        // SAP SYNC
        SapDemo::syncAttendance($conn, $acc_card_number, $date, date('H:i:s', strtotime($check_in_time)), '18:00:00');
    }

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

