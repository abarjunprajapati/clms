<?php
/**
 * ANNEXURE 5/A - Initialize Pass Limits Table & Default Rules
 * 
 * यह script database में pass_limits table create करता है
 * और default validation rules set करता है
 * 
 * Execution: php init_pass_limits.php
 */

require_once __DIR__ . '/../../include/config.php';

try {
    echo "====== ANNEXURE 5/A - PASS LIMITS SETUP ======\n\n";

    // ============ CREATE TABLE ============
    $create_sql = "CREATE TABLE IF NOT EXISTS pass_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL DEFAULT 0,
        pass_type VARCHAR(50) NOT NULL,
        max_allowed INT DEFAULT NULL,
        ratio_per_workmen INT DEFAULT 10,
        rule VARCHAR(100) DEFAULT 'Fixed',
        override_allowed BOOLEAN DEFAULT TRUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_limit (contractor_id, pass_type),
        INDEX idx_pass_type (pass_type)
    )";

    if (mysqli_query($conn, $create_sql)) {
        echo "✅ Table 'pass_limits' created/verified\n";
    } else {
        throw new Exception("Failed to create table: " . mysqli_error($conn));
    }

    // ============ CHECK & ADD MISSING COLUMNS ============
    
    // Check if description column exists
    $check_desc = mysqli_query($conn, "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='pass_limits' AND COLUMN_NAME='description'");
    if (mysqli_num_rows($check_desc) === 0) {
        echo "⚠️  Adding missing 'description' column...\n";
        $alter_sql = "ALTER TABLE pass_limits ADD COLUMN description TEXT DEFAULT NULL AFTER rule";
        if (mysqli_query($conn, $alter_sql)) {
            echo "✅ Column 'description' added\n";
        } else {
            echo "⚠️  Could not add description column (may already exist): " . mysqli_error($conn) . "\n";
        }
    }

    // Check if override_allowed column exists
    $check_override = mysqli_query($conn, "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='pass_limits' AND COLUMN_NAME='override_allowed'");
    if (mysqli_num_rows($check_override) === 0) {
        echo "⚠️  Adding missing 'override_allowed' column...\n";
        $alter_sql = "ALTER TABLE pass_limits ADD COLUMN override_allowed BOOLEAN DEFAULT TRUE AFTER description";
        if (mysqli_query($conn, $alter_sql)) {
            echo "✅ Column 'override_allowed' added\n";
        } else {
            echo "⚠️  Could not add override_allowed column: " . mysqli_error($conn) . "\n";
        }
    }

    // ============ CLEAR & INSERT DEFAULT RULES ============
    
    // Delete existing defaults (keep contractor-specific)
    $delete_defaults = "DELETE FROM pass_limits WHERE contractor_id = 0";
    mysqli_query($conn, $delete_defaults);

    $rules = [
        [
            'pass_type' => 'Contractor',
            'max_allowed' => 2,
            'ratio_per_workmen' => NULL,
            'rule' => 'Fixed',
            'description' => 'Maximum 2 contractors per firm/registration'
        ],
        [
            'pass_type' => 'Representative',
            'max_allowed' => 1,
            'ratio_per_workmen' => NULL,
            'rule' => 'Fixed',
            'description' => 'Only 1 official representative per firm'
        ],
        [
            'pass_type' => 'Supervisor',
            'max_allowed' => NULL,
            'ratio_per_workmen' => 10,
            'rule' => 'Ratio',
            'description' => '1 supervisor per 10 workmen + 1 additional'
        ],
        [
            'pass_type' => 'Workman',
            'max_allowed' => NULL,
            'ratio_per_workmen' => NULL,
            'rule' => 'NoLimit',
            'description' => 'No fixed limit (depends on project work order)'
        ]
    ];

    $inserted = 0;
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO pass_limits 
         (contractor_id, pass_type, max_allowed, ratio_per_workmen, rule, description, override_allowed) 
         VALUES (0, ?, ?, ?, ?, ?, TRUE)"
    );

    foreach ($rules as $r) {
        $pass_type = $r['pass_type'];
        $max_allowed = $r['max_allowed'];
        $ratio = $r['ratio_per_workmen'];
        $rule = $r['rule'];
        $desc = $r['description'];

        mysqli_stmt_bind_param($stmt, 'siiss', 
            $pass_type,
            $max_allowed,
            $ratio,
            $rule,
            $desc
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Inserted: $pass_type → Max: " . ($max_allowed ?? 'Unlimited') . 
                 ", Ratio: " . ($ratio ?? 'N/A') . "\n";
            $inserted++;
        } else {
            echo "❌ Failed to insert $pass_type: " . mysqli_stmt_error($stmt) . "\n";
        }
    }

    mysqli_stmt_close($stmt);

    // ============ VERIFICATION ============
    echo "\n📋 Current Rules:\n";
    $result = mysqli_query($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0 ORDER BY pass_type");
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo sprintf(
            "  • %s: Max=%s, Ratio=%s, Rule=%s, Override=%s\n",
            $row['pass_type'],
            $row['max_allowed'] ?? 'Unlimited',
            $row['ratio_per_workmen'] ?? 'N/A',
            $row['rule'],
            $row['override_allowed'] ? 'Yes' : 'No'
        );
    }

    echo "\n✅ Setup Complete! " . $inserted . " rules initialized.\n";

} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    die(1);
}
?>

