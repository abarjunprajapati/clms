<?php
include __DIR__ . '/../include/config.php';

$sqls = [
    // Add shift preference to training_requests
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS preferred_shift ENUM('morning','evening') DEFAULT 'morning' AFTER preferred_date",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS scheduled_date DATE NULL AFTER preferred_shift",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS scheduled_shift ENUM('morning','evening') NULL AFTER scheduled_date",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS scheduled_venue VARCHAR(300) NULL AFTER scheduled_shift",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS scheduled_time VARCHAR(20) NULL AFTER scheduled_venue",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS safety_remarks TEXT NULL AFTER scheduled_time",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS contractor_remarks TEXT NULL AFTER safety_remarks",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS contractor_confirmed TINYINT(1) DEFAULT 0 AFTER contractor_remarks",
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS scheduled_by INT NULL AFTER contractor_confirmed",
    // Update status enum to include new states
    "ALTER TABLE training_requests MODIFY COLUMN status ENUM('pending','scheduled','contractor_confirmed','completed','rejected') DEFAULT 'pending'",
    // Add remarks column if not exists
    "ALTER TABLE training_requests ADD COLUMN IF NOT EXISTS remarks TEXT NULL AFTER contractor_id",
];

foreach ($sqls as $sql) {
    if ($conn->query($sql)) {
        echo "OK: " . substr($sql, 0, 60) . "...\n";
    } else {
        echo "ERR: " . $conn->error . " | " . substr($sql, 0, 60) . "\n";
    }
}
echo "\nMigration complete.\n";
?>
