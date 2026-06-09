<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) die("Connection failed");

// 1. Get existing status enum for contractors
$res = mysqli_query($conn, "SHOW COLUMNS FROM contractors LIKE 'status'");
$row = mysqli_fetch_assoc($res);
$type = $row['Type']; // e.g. enum('active','inactive',...)

// Extract values
preg_match_all("/'([^']+)'/", $type, $matches);
$existingValues = $matches[1];

// Add 'blocked' if not exists
if (!in_array('blocked', $existingValues)) {
    $existingValues[] = 'blocked';
}

$newEnum = "ENUM('" . implode("','", $existingValues) . "')";

// 2. Generate and run the safe ALTER TABLE
$sql = "ALTER TABLE contractors 
    MODIFY COLUMN status $newEnum DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS block_reason VARCHAR(255),
    ADD COLUMN IF NOT EXISTS block_remarks TEXT,
    ADD COLUMN IF NOT EXISTS blocked_by INT,
    ADD COLUMN IF NOT EXISTS blocked_at DATETIME,
    ADD COLUMN IF NOT EXISTS activated_by INT,
    ADD COLUMN IF NOT EXISTS activated_at DATETIME";

echo "Executing: $sql\n";
if (mysqli_query($conn, $sql)) {
    echo "Contractors table updated successfully.\n";
} else {
    echo "Error updating contractors: " . mysqli_error($conn) . "\n";
}

// 3. Ensure contractor_block_history has action_type
$res = mysqli_query($conn, "SHOW TABLES LIKE 'contractor_block_history'");
if (mysqli_num_rows($res) > 0) {
    $res2 = mysqli_query($conn, "DESCRIBE contractor_block_history");
    $hasActionType = false;
    $hasAction = false;
    while($r = mysqli_fetch_assoc($res2)) {
        if ($r['Field'] == 'action_type') $hasActionType = true;
        if ($r['Field'] == 'action') $hasAction = true;
    }
    
    if (!$hasActionType && $hasAction) {
        mysqli_query($conn, "ALTER TABLE contractor_block_history CHANGE action action_type ENUM('BLOCK', 'UNBLOCK')");
    } else if (!$hasActionType) {
        mysqli_query($conn, "ALTER TABLE contractor_block_history ADD action_type ENUM('BLOCK', 'UNBLOCK') AFTER contractor_id");
    }
    
    // Add other missing columns
    $cols = [
        'remarks' => 'TEXT AFTER reason',
        'action_at' => 'DATETIME AFTER action_by',
        'ip_address' => 'VARCHAR(100) AFTER action_at',
        'sync_status' => 'VARCHAR(50) AFTER ip_address'
    ];
    foreach($cols as $col => $def) {
        mysqli_query($conn, "ALTER TABLE contractor_block_history ADD COLUMN IF NOT EXISTS $col $def");
    }
    echo "History table updated.\n";
}

// 4. Ensure workmen has blocked_source
mysqli_query($conn, "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS blocked_source ENUM('contractor', 'safety', 'disciplinary', 'manual') AFTER source");
echo "Workmen table updated.\n";

?>
