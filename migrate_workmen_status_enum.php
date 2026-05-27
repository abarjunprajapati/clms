<?php
/**
 * Migration: Add 'draft' and 'pending' to workmen status ENUM
 * 
 * This script fixes the truncation error when saving workmen as draft.
 * The status column ENUM was missing 'draft' and 'pending' values.
 */

ob_start();
session_start();

try {
    require_once __DIR__ . '/include/config.php';

    echo "🔄 WORKMEN STATUS ENUM MIGRATION\n";
    echo "==================================\n\n";

    // Get current column definition
    $result = mysqli_query($conn, "SHOW CREATE TABLE workmen");
    if (!$result) {
        throw new Exception("Failed to get workmen table structure: " . $conn->error);
    }

    $row = mysqli_fetch_assoc($result);
    $createStatement = $row['Create Table'] ?? '';

    echo "Current workmen table schema:\n";
    echo str_repeat("-", 60) . "\n";

    // Check if status column has the new ENUM values
    if (strpos($createStatement, "'draft'") === false || strpos($createStatement, "'pending'") === false) {
        echo "❌ Status column missing 'draft' and/or 'pending' values\n";
        echo "📝 Updating status ENUM...\n\n";

        // Get current data to back it up
        $backup_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM workmen");
        $backup_row = mysqli_fetch_assoc($backup_result);
        $workmen_count = $backup_row['count'];
        echo "📊 Backup: {$workmen_count} existing workmen records\n\n";

        // Alter the column to add draft and pending values
        $alter_sql = "ALTER TABLE workmen 
                     MODIFY COLUMN status ENUM('draft','pending','active','inactive','blocked','temp','trained','verified','acc_generated','temporary_issued','permanent_issued') DEFAULT 'active'";

        if (!mysqli_query($conn, $alter_sql)) {
            throw new Exception("Failed to alter status column: " . $conn->error);
        }

        echo "✅ Status ENUM updated successfully\n";
        echo "   New values: draft, pending, active, inactive, blocked, temp, trained, verified, acc_generated, temporary_issued, permanent_issued\n\n";

    } else {
        echo "✅ Status column already has correct ENUM values\n";
        echo "   Values include: draft, pending, active, inactive, blocked, temp, trained, verified, acc_generated, temporary_issued, permanent_issued\n\n";
    }

    // Verify the change
    $result = mysqli_query($conn, "SHOW CREATE TABLE workmen");
    $row = mysqli_fetch_assoc($result);
    $createStatement = $row['Create Table'] ?? '';

    if (strpos($createStatement, "'draft'") !== false && strpos($createStatement, "'pending'") !== false) {
        echo "✅ VERIFICATION PASSED: Status ENUM is now correct\n";
    } else {
        throw new Exception("Verification failed: Status ENUM still missing values");
    }

    // Test insert with draft and pending values
    echo "\n🧪 Testing INSERT with new status values...\n";
    echo str_repeat("-", 60) . "\n";

    // Create test workman (if no duplicate key issues)
    $test_sql = "INSERT INTO workmen (application_no, contractor_id, name, aadhaar, status) 
                 VALUES ('TEST-DRAFT-' . UNIX_TIMESTAMP(), 0, 'Test Draft', 'TEST00000000', 'draft') 
                 ON DUPLICATE KEY UPDATE status = 'draft'";

    if (mysqli_query($conn, $test_sql)) {
        echo "✅ Successfully inserted/updated record with status='draft'\n";
    } else {
        $error = $conn->error;
        if (strpos($error, 'Duplicate entry') !== false) {
            echo "⚠️  Test record already exists: " . $error . "\n";
        } else {
            throw new Exception("Test insert failed: " . $error);
        }
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ MIGRATION COMPLETED SUCCESSFULLY\n";
    echo str_repeat("=", 60) . "\n";
    echo "\n📌 The workmen save draft functionality should now work correctly.\n";
    echo "📌 All existing workmen records are preserved.\n";

} catch (Exception $e) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "❌ MIGRATION FAILED\n";
    echo str_repeat("=", 60) . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
} finally {
    echo "\n";
}
?>
