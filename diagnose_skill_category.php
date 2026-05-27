<?php
/**
 * Diagnostic Script: skill_category Truncation Issue
 */

ob_start();
session_start();

try {
    require_once __DIR__ . '/include/config.php';

    echo "🔍 SKILL_CATEGORY COLUMN DIAGNOSTIC\n";
    echo "===================================\n\n";

    // 1. Check workmen table structure
    echo "1️⃣ WORKMEN TABLE SCHEMA:\n";
    echo str_repeat("-", 70) . "\n";

    $result = mysqli_query($conn, "SHOW CREATE TABLE workmen");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $schema = $row['Create Table'] ?? '';
        
        // Extract skill_category definition
        if (preg_match('/`skill_category`\s+([^,]+)/i', $schema, $matches)) {
            echo "✓ Column Definition:\n";
            echo "  " . trim($matches[1]) . "\n\n";
        } else {
            echo "❌ Column 'skill_category' NOT FOUND in table definition\n";
            echo "   This column will be created dynamically by ensure_column()\n\n";
        }
    }

    // 2. Check actual column definition from SHOW COLUMNS
    echo "2️⃣ COLUMN INFORMATION (SHOW COLUMNS):\n";
    echo str_repeat("-", 70) . "\n";

    $result = mysqli_query($conn, "SHOW COLUMNS FROM workmen WHERE Field='skill_category'");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "✓ Found:\n";
        foreach ($row as $key => $value) {
            printf("  %-15s: %s\n", $key, $value ?? '(null)');
        }
        echo "\n";
    } else {
        echo "⚠️  Column not yet created in database\n\n";
    }

    // 3. Check sample data
    echo "3️⃣ SAMPLE DATA (First 10 records):\n";
    echo str_repeat("-", 70) . "\n";

    $result = mysqli_query($conn, "SELECT id, name, skill, skill_category FROM workmen LIMIT 10");
    if ($result) {
        $count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            if ($count > 0) echo "\n";
            printf("ID #%d: %s\n", $row['id'], $row['name']);
            printf("  skill: '%s' (%d chars)\n", $row['skill'], strlen($row['skill'] ?? ''));
            printf("  skill_category: '%s' (%d chars)\n", $row['skill_category'], strlen($row['skill_category'] ?? ''));
            $count++;
        }
        if ($count === 0) {
            echo "(No records yet)\n";
        }
        echo "\n";
    }

    // 4. Check for truncation patterns
    echo "4️⃣ DATA LENGTH ANALYSIS:\n";
    echo str_repeat("-", 70) . "\n";

    $result = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_records,
            MAX(CHAR_LENGTH(skill_category)) as max_length,
            MAX(CHAR_LENGTH(skill)) as max_skill_length
        FROM workmen
    ");
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Total records: " . ($row['total_records'] ?? 0) . "\n";
        echo "Max skill_category length: " . ($row['max_length'] ?? 0) . " chars\n";
        echo "Max skill length: " . ($row['max_skill_length'] ?? 0) . " chars\n\n";
    }

    // 5. Test insert to reproduce error
    echo "5️⃣ TEST INSERT (Attempting save_worker_4a flow):\n";
    echo str_repeat("-", 70) . "\n";

    // Simulate what save_worker_4a.php does
    $test_data = [
        'application_no' => 'TEST-' . time(),
        'contractor_id' => 0,
        'name' => 'Test Worker',
        'aadhaar' => 'TEST' . time(),
        'skill_category' => 'Semi Skilled',
    ];

    echo "Testing with:\n";
    foreach ($test_data as $k => $v) {
        printf("  %s = '%s' (%d chars)\n", $k, $v, strlen($v));
    }
    echo "\n";

    // Prepare INSERT
    $columns = implode('`, `', array_keys($test_data));
    $placeholders = implode(', ', array_fill(0, count($test_data), '?'));
    $sql = "INSERT INTO workmen (`{$columns}`) VALUES ({$placeholders})";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "❌ Prepare failed: " . $conn->error . "\n\n";
    } else {
        $types = str_repeat('s', count($test_data));
        $values = array_values($test_data);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            echo "✅ Test INSERT succeeded\n";
            echo "   Inserted ID: " . $stmt->insert_id . "\n\n";
        } else {
            echo "❌ Test INSERT failed: " . $stmt->error . "\n\n";
        }
        $stmt->close();
    }

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "DIAGNOSTIC COMPLETE\n";
    echo str_repeat("=", 70) . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
