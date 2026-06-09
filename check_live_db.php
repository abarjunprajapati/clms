<?php
require_once 'include/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "CLMS LIVE DIAGNOSTIC REPORT\n";
echo "===========================\n\n";

// 1. Check Session
echo "1. SESSION DETAILS:\n";
if (empty($_SESSION)) {
    echo "  Session is empty.\n";
} else {
    foreach ($_SESSION as $k => $v) {
        if (is_array($v) || is_object($v)) {
            echo "  $k: " . json_encode($v) . "\n";
        } else {
            echo "  $k: $v\n";
        }
    }
}
echo "\n";

// 2. Fetch User & Contractor mapping
$user_id = $_SESSION['user_id'] ?? null;
echo "2. USER AND CONTRACTOR MAPPING:\n";
if ($user_id) {
    $user_row = db_single($conn, "SELECT * FROM users WHERE id = ?", 'i', [$user_id]);
    if ($user_row) {
        echo "  Logged-in User: ID = {$user_row['id']}, Role = '{$user_row['role']}', Name = '{$user_row['name']}', Email = '{$user_row['email']}'\n";
    } else {
        echo "  Logged-in User ID $user_id not found in users table!\n";
    }
    
    $contractor = db_single($conn, "SELECT * FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    if ($contractor) {
        echo "  Mapped Contractor: ID = {$contractor['id']}, Name = '{$contractor['contractor_name']}', Work Order No = '{$contractor['work_order_no']}', Status = '{$contractor['status']}'\n";
    } else {
        echo "  No contractor mapped for user_id = $user_id in contractors table!\n";
    }
} else {
    echo "  Not logged in (no user_id in session).\n";
}
echo "\n";

// 3. Check Workers Table
echo "3. RECENT WORKERS IN 'workers' TABLE:\n";
$workers = db_fetch_all($conn, "SELECT id, work_order_no, temp_id, name, aadhaar, safety_status, gate_pass_status, created_at FROM workers ORDER BY id DESC LIMIT 5");
if (empty($workers)) {
    echo "  No records found in 'workers' table.\n";
} else {
    foreach ($workers as $w) {
        echo "  ID: {$w['id']} | WO: '{$w['work_order_no']}' | TempID: '{$w['temp_id']}' | Name: '{$w['name']}' | Aadhaar: '{$w['aadhaar']}' | Safety: '{$w['safety_status']}' | GatePass: '{$w['gate_pass_status']}' | Created: {$w['created_at']}\n";
    }
}
echo "\n";

// 4. Check Workmen Table
echo "4. RECENT WORKMEN IN 'workmen' TABLE:\n";
$workmen = db_fetch_all($conn, "SELECT id, temp_id, contractor_id, name, aadhaar, status, created_at FROM workmen ORDER BY id DESC LIMIT 5");
if (empty($workmen)) {
    echo "  No records found in 'workmen' table.\n";
} else {
    foreach ($workmen as $wm) {
        echo "  ID: {$wm['id']} | ContractorID: {$wm['contractor_id']} | TempID: '{$wm['temp_id']}' | Name: '{$wm['name']}' | Aadhaar: '{$wm['aadhaar']}' | Status: '{$wm['status']}' | Created: {$wm['created_at']}\n";
    }
}
echo "\n";

// 5. Total counts
$total_workers = db_count($conn, "SELECT COUNT(*) FROM workers");
$total_workmen = db_count($conn, "SELECT COUNT(*) FROM workmen");
$total_contractors = db_count($conn, "SELECT COUNT(*) FROM contractors");
echo "5. SUMMARY STATISTICS:\n";
echo "  Total Contractors: $total_contractors\n";
echo "  Total Workers: $total_workers\n";
echo "  Total Workmen: $total_workmen\n";
echo "\n===========================\n";
?>
