<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$workman_id = intval($_POST['workman_id'] ?? 0);

if (!$workman_id) {
    header("Location: ../../pages/safety/retraining.php?error=Invalid worker");
    exit;
}

// Reset status to pending so they can be scheduled again
$sql = "UPDATE workmen SET training_status='training_pending' WHERE id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $workman_id);

if (mysqli_stmt_execute($stmt)) {
    // Also update any formal request to pending if exists
    mysqli_query($conn, "UPDATE training_requests SET status='pending' WHERE workman_id=$workman_id AND status='completed' ORDER BY id DESC LIMIT 1");
    
    header("Location: ../../pages/safety/retraining.php?success=Worker status reset to pending");
} else {
    header("Location: ../../pages/safety/retraining.php?error=" . mysqli_error($conn));
}

