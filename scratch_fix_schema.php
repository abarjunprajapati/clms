<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// Check if action_type exists
$res = mysqli_query($conn, "DESCRIBE contractor_block_history");
$hasActionType = false;
$hasAction = false;
$hasRemarks = false;
$hasActionAt = false;
$hasIpAddress = false;
$hasSyncStatus = false;

while ($row = mysqli_fetch_assoc($res)) {
    if ($row['Field'] == 'action_type') $hasActionType = true;
    if ($row['Field'] == 'action') $hasAction = true;
    if ($row['Field'] == 'remarks') $hasRemarks = true;
    if ($row['Field'] == 'action_at') $hasActionAt = true;
    if ($row['Field'] == 'ip_address') $hasIpAddress = true;
    if ($row['Field'] == 'sync_status') $hasSyncStatus = true;
}

$queries = [];
if (!$hasActionType && $hasAction) {
    $queries[] = "ALTER TABLE contractor_block_history CHANGE action action_type ENUM('BLOCK', 'UNBLOCK')";
} else if (!$hasActionType) {
    $queries[] = "ALTER TABLE contractor_block_history ADD action_type ENUM('BLOCK', 'UNBLOCK') AFTER contractor_id";
}

if (!$hasRemarks) $queries[] = "ALTER TABLE contractor_block_history ADD remarks TEXT AFTER reason";
if (!$hasActionAt) $queries[] = "ALTER TABLE contractor_block_history ADD action_at DATETIME AFTER action_by";
if (!$hasIpAddress) $queries[] = "ALTER TABLE contractor_block_history ADD ip_address VARCHAR(100) AFTER action_at";
if (!$hasSyncStatus) $queries[] = "ALTER TABLE contractor_block_history ADD sync_status VARCHAR(50) AFTER ip_address";

// Also ensure contractors table has everything
$res = mysqli_query($conn, "DESCRIBE contractors");
$hasBlockReason = false;
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['Field'] == 'block_reason') $hasBlockReason = true;
}
if (!$hasBlockReason) {
     $queries[] = "ALTER TABLE contractors 
        MODIFY COLUMN status ENUM('active', 'inactive', 'blocked', 'suspended', 'pending', 'draft', 'approved', 'rejected') DEFAULT 'active',
        ADD COLUMN is_blocked TINYINT(1) DEFAULT 0,
        ADD COLUMN block_reason VARCHAR(255),
        ADD COLUMN block_remarks TEXT,
        ADD COLUMN blocked_by INT,
        ADD COLUMN blocked_at DATETIME,
        ADD COLUMN activated_by INT,
        ADD COLUMN activated_at DATETIME";
}

foreach ($queries as $query) {
    echo "Executing: $query\n";
    if (mysqli_query($conn, $query)) {
        echo "Success\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
