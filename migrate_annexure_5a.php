<?php
/**
 * migrate_annexure_5a.php
 * Upgrades pass_limits table to match Annexure 5/A PDF spec.
 * Adds rule column, override flag, ratio column, and seeds default data.
 * 
 * Run once: http://localhost/clms/migrate_annexure_5a.php
 */
require_once __DIR__ . '/include/config.php';

echo "<h2>🔧 Annexure 5/A — Pass Limits Migration</h2>";

// Step 1: Add missing columns if they don't exist
$columns_to_add = [
    "rule" => "ALTER TABLE pass_limits ADD COLUMN rule VARCHAR(100) NOT NULL DEFAULT 'Fixed' AFTER max_allowed",
    "ratio_per_workmen" => "ALTER TABLE pass_limits ADD COLUMN ratio_per_workmen INT DEFAULT NULL AFTER rule",
    "override_allowed" => "ALTER TABLE pass_limits ADD COLUMN override_allowed TINYINT(1) NOT NULL DEFAULT 1 AFTER ratio_per_workmen",
    "updated_at" => "ALTER TABLE pass_limits ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($columns_to_add as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM pass_limits LIKE '$col'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query($sql)) {
            echo "<p style='color:green'>✅ Added column: <b>$col</b></p>";
        } else {
            echo "<p style='color:red'>❌ Failed to add column $col: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:gray'>⏭ Column <b>$col</b> already exists.</p>";
    }
}

// Step 2: Add unique index on (contractor_id, pass_type) if not present
$idx = $conn->query("SHOW INDEX FROM pass_limits WHERE Key_name = 'idx_contractor_pass_type'");
if ($idx && $idx->num_rows === 0) {
    // Remove any duplicate rows first
    $conn->query("DELETE p1 FROM pass_limits p1 INNER JOIN pass_limits p2 WHERE p1.id > p2.id AND p1.contractor_id = p2.contractor_id AND p1.pass_type = p2.pass_type");
    if ($conn->query("ALTER TABLE pass_limits ADD UNIQUE INDEX idx_contractor_pass_type (contractor_id, pass_type)")) {
        echo "<p style='color:green'>✅ Added unique index (contractor_id, pass_type)</p>";
    } else {
        echo "<p style='color:orange'>⚠ Index may already exist or conflict: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:gray'>⏭ Unique index already exists.</p>";
}

// Step 3: Seed default template rows (contractor_id = 0 = global defaults)
$defaults = [
    ['Contractor',      2,    NULL, 'Fixed',             NULL],
    ['Representative',  1,    NULL, 'Fixed',             NULL],
    ['Supervisor',      NULL, NULL, '1 per 10 workmen',  10  ],
    ['Workman',         NULL, NULL, 'No limit',          NULL]
];

foreach ($defaults as $d) {
    $stmt = $conn->prepare("INSERT INTO pass_limits (contractor_id, pass_type, max_allowed, rule, ratio_per_workmen, override_allowed, current_count) 
                            VALUES (0, ?, ?, ?, ?, 1, 0) 
                            ON DUPLICATE KEY UPDATE rule=VALUES(rule), ratio_per_workmen=VALUES(ratio_per_workmen)");
    $stmt->bind_param("sisi", $d[0], $d[1], $d[3], $d[4]);
    if ($stmt->execute()) {
        echo "<p style='color:blue'>📌 Seeded default: <b>" . $d[0] . "</b> (max=" . ($d[1] ?? 'dynamic') . ", rule=" . $d[3] . ")</p>";
    } else {
        echo "<p style='color:red'>❌ Seed failed for " . $d[0] . ": " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<hr><p style='color:green;font-size:18px;'><b>✅ Annexure 5/A Migration Complete!</b></p>";
echo "<p><a href='pages/welfare/pass_limits.php'>→ Go to Pass Limits Admin</a></p>";
?>

