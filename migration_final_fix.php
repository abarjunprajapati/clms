<?php
/**
 * CLMS Final Schema Synchronization Migration
 * This script updates the database schema to match the application's current requirements.
 * Run this on your live server to resolve 500 Internal Server Errors.
 */
require_once __DIR__ . '/include/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🚀 CLMS Final Schema Synchronization</h2>";
echo "<p>Starting migration... Please do not close this page.</p><hr>";

function run_query($conn, $sql, $message) {
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✅ $message</p>";
        return true;
    } else {
        echo "<p style='color:red'>❌ Failed: $message<br>Error: " . $conn->error . "</p>";
        return false;
    }
}

// 1. Users Table - Add must_change_password
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'must_change_password'");
if ($check && $check->num_rows === 0) {
    run_query($conn, "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) DEFAULT 0", "Added must_change_password to users table");
} else {
    echo "<p style='color:gray'>⏭ must_change_password already exists in users.</p>";
}

// 2. Workmen Table - Update worker_type enum
run_query($conn, "ALTER TABLE workmen MODIFY COLUMN worker_type ENUM('contractor','supervisor','workman','representative') DEFAULT 'workman'", "Updated workmen.worker_type enum");

// 3. Contractors Table - Update status enum
run_query($conn, "ALTER TABLE contractors MODIFY COLUMN status ENUM('pending','approved','rejected','blocked','suspended') DEFAULT 'pending'", "Updated contractors.status enum");

// 4. Gate Passes Table - Update status enum and add pass_number
run_query($conn, "ALTER TABLE gate_passes MODIFY COLUMN status ENUM('pending','approved','rejected','active','expired','cancelled') DEFAULT 'pending'", "Updated gate_passes.status enum");
$check = $conn->query("SHOW COLUMNS FROM gate_passes LIKE 'pass_number'");
if ($check && $check->num_rows === 0) {
    run_query($conn, "ALTER TABLE gate_passes ADD COLUMN pass_number VARCHAR(100) AFTER id", "Added pass_number to gate_passes table");
} else {
    echo "<p style='color:gray'>⏭ pass_number already exists in gate_passes.</p>";
}

// 5. Document Verifications Table - Update status enum
run_query($conn, "ALTER TABLE document_verifications MODIFY COLUMN status ENUM('pending','approved','rejected','reupload_required','expired','valid') DEFAULT 'pending'", "Updated document_verifications.status enum");

// 6. Annexure3a Table - Add supervisor columns
$cols = [
    'supervisor_name' => "ALTER TABLE annexure3a ADD COLUMN supervisor_name VARCHAR(200) AFTER contractor_id",
    'qualification'   => "ALTER TABLE annexure3a ADD COLUMN qualification VARCHAR(100) AFTER supervisor_name",
    'experience'      => "ALTER TABLE annexure3a ADD COLUMN experience INT AFTER qualification",
    'mobile'          => "ALTER TABLE annexure3a ADD COLUMN mobile VARCHAR(20) AFTER experience",
    'aadhaar'         => "ALTER TABLE annexure3a ADD COLUMN aadhaar VARCHAR(20) AFTER mobile",
    'amenities'       => "ALTER TABLE annexure3a ADD COLUMN amenities TEXT AFTER aadhaar",
    'ref_id'          => "ALTER TABLE annexure3a ADD COLUMN ref_id VARCHAR(50) AFTER amenities"
];

foreach ($cols as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM annexure3a LIKE '$col'");
    if ($check && $check->num_rows === 0) {
        run_query($conn, $sql, "Added $col to annexure3a");
    } else {
        echo "<p style='color:gray'>⏭ $col already exists in annexure3a.</p>";
    }
}

// 7. Compliance Table - Add type column and ensure status enum
$check = $conn->query("SHOW COLUMNS FROM compliance LIKE 'type'");
if ($check && $check->num_rows === 0) {
    run_query($conn, "ALTER TABLE compliance ADD COLUMN type VARCHAR(50) AFTER contractor_id", "Added type to compliance table");
} else {
    echo "<p style='color:gray'>⏭ type already exists in compliance.</p>";
}
run_query($conn, "ALTER TABLE compliance MODIFY COLUMN status ENUM('pending','verified','rejected') DEFAULT 'pending'", "Updated compliance.status enum");

echo "<hr><p style='color:blue;font-weight:bold;'>🎉 Migration Complete! Your database is now synchronized.</p>";
echo "<p><a href='pages/welfare/admin_dashboard.php'>Go to Admin Dashboard</a></p>";
?>
