<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

checkAuth(['safety_user', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/safety/retraining.php?error=" . urlencode("Invalid request method"));
    exit;
}

function requestRetrainingColumnExists($conn, $table, $column) {
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    return $result && mysqli_num_rows($result) > 0;
}

$workman_id = intval($_POST['workman_id'] ?? 0);

if (!$workman_id) {
    header("Location: ../../pages/safety/retraining.php?error=Invalid worker");
    exit;
}

$resultTable = mysqli_query($conn, "SHOW TABLES LIKE 'training_results'");
if ($resultTable && mysqli_num_rows($resultTable) > 0) {
    $attempt = db_single(
        $conn,
        "SELECT MIN(DATE(created_at)) AS first_training_date,
                SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS attempts_30
         FROM training_results
         WHERE workman_id = ?",
        'i',
        [$workman_id]
    );
    $attempts = (int)($attempt['attempts_30'] ?? 0);
    $firstDate = $attempt['first_training_date'] ?? null;
    $days = $firstDate ? floor((strtotime(date('Y-m-d')) - strtotime($firstDate)) / 86400) : 0;
    if ($attempts >= 3) {
        header("Location: ../../pages/safety/retraining.php?error=" . urlencode("Maximum Attempt Reached"));
        exit;
    }
    if ($firstDate && $days > 30) {
        header("Location: ../../pages/safety/retraining.php?error=" . urlencode("Retest Period Expired"));
        exit;
    }
}

$setParts = [];
if (requestRetrainingColumnExists($conn, 'workmen', 'training_status')) {
    $setParts[] = "training_status='training_pending'";
}
if (requestRetrainingColumnExists($conn, 'workmen', 'safety_training_status')) {
    $setParts[] = "safety_training_status='PENDING_TRAINING'";
}
if (requestRetrainingColumnExists($conn, 'workmen', 'eligibility_status')) {
    $setParts[] = "eligibility_status='NOT ELIGIBLE'";
}
if (requestRetrainingColumnExists($conn, 'workmen', 'updated_at')) {
    $setParts[] = "updated_at=NOW()";
}

if (!$setParts) {
    header("Location: ../../pages/safety/retraining.php?error=Training status columns not found");
    exit;
}

$sql = "UPDATE workmen SET " . implode(', ', $setParts) . " WHERE id=?";
if (db_execute($conn, $sql, 'i', [$workman_id])) {
    // Also update any formal request to pending if exists
    if (mysqli_query($conn, "SHOW TABLES LIKE 'training_requests'") && requestRetrainingColumnExists($conn, 'training_requests', 'status')) {
        $updatedAt = requestRetrainingColumnExists($conn, 'training_requests', 'updated_at') ? ", updated_at=NOW()" : "";
        db_execute(
            $conn,
            "UPDATE training_requests SET status='pending' $updatedAt WHERE workman_id=? AND status IN ('completed','passed','failed') ORDER BY id DESC LIMIT 1",
            'i',
            [$workman_id]
        );
    }
    
    header("Location: ../../pages/safety/retraining.php?success=Worker status reset to pending");
} else {
    header("Location: ../../pages/safety/retraining.php?error=" . mysqli_error($conn));
}

