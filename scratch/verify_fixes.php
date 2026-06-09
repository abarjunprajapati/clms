<?php
require_once 'include/config.php';
session_start();

// Mock session for testing
$_SESSION['user_id'] = 1; 
$_SESSION['role'] = 'contractor';

echo "Testing Admin Dashboard Queries...\n";
try {
    $recent_logs = db_fetch_all($conn, "SELECT l.*, u.name FROM logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5");
    echo "  Logs count: " . count($recent_logs) . "\n";
} catch (Throwable $e) {
    echo "  Admin Error: " . $e->getMessage() . "\n";
}

echo "\nTesting Contractor Dashboard Queries...\n";
try {
    $user_id = $_SESSION['user_id'];
    $contractor = db_single($conn, "SELECT * FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    if ($contractor) {
        $contractor_id = $contractor['id'];
        echo "  Contractor found: " . $contractor['contractor_name'] . " (ID: $contractor_id)\n";
        
        $pending = db_count($conn, "SELECT COUNT(*) c FROM applications WHERE contractor_id = ? AND status IN ('submitted','verified')", 'i', [$contractor_id]);
        echo "  Pending: $pending\n";
        
        $active_passes = db_count($conn, "
            SELECT COUNT(*) c 
            FROM gate_passes gp 
            JOIN workmen w ON gp.workman_id = w.id 
            WHERE w.contractor_id = ? AND gp.status = 'approved'
        ", 'i', [$contractor_id]);
        echo "  Active passes: $active_passes\n";
    } else {
        echo "  No contractor found for user_id 1\n";
    }
} catch (Throwable $e) {
    echo "  Contractor Error: " . $e->getMessage() . "\n";
}

