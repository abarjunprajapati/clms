<?php
include 'include/config.php'; // Use system connection

echo "Current User: " . (isset($Username) ? $Username : 'unknown') . "\n";
echo "Current DB: " . (isset($Dbname) ? $Dbname : 'unknown') . "\n";

// 1. Get existing status enum for contractors
$res = mysqli_query($conn, "SHOW COLUMNS FROM contractors LIKE 'status'");
if (!$res) die("Error: " . mysqli_error($conn));

$row = mysqli_fetch_assoc($res);
$type = $row['Type']; 

preg_match_all("/'([^']+)'/", $type, $matches);
$existingValues = $matches[1];

if (!in_array('blocked', $existingValues)) {
    $existingValues[] = 'blocked';
}

$newEnum = "ENUM('" . implode("','", $existingValues) . "')";

// 2. Add columns with a more granular approach to avoid errors
$columns = [
    "is_blocked TINYINT(1) DEFAULT 0",
    "block_reason VARCHAR(255)",
    "block_remarks TEXT",
    "blocked_by INT",
    "blocked_at DATETIME",
    "activated_by INT",
    "activated_at DATETIME"
];

// Modify status first
mysqli_query($conn, "ALTER TABLE contractors MODIFY COLUMN status $newEnum DEFAULT 'active'");

foreach ($columns as $colDef) {
    $colName = explode(" ", $colDef)[0];
    $check = mysqli_query($conn, "SHOW COLUMNS FROM contractors LIKE '$colName'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE contractors ADD COLUMN $colDef";
        echo "Adding $colName: ";
        if (mysqli_query($conn, $sql)) echo "OK\n";
        else echo "FAIL - " . mysqli_error($conn) . "\n";
    } else {
        echo "$colName already exists.\n";
    }
}

// 3. Update workmen
$check = mysqli_query($conn, "SHOW COLUMNS FROM workmen LIKE 'is_blocked'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE workmen ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
}
$check = mysqli_query($conn, "SHOW COLUMNS FROM workmen LIKE 'blocked_source'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE workmen ADD COLUMN blocked_source ENUM('contractor', 'safety', 'disciplinary', 'manual') AFTER source");
}

echo "Migration Complete.\n";
?>
