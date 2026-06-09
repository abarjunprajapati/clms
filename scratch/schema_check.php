<?php
require_once 'include/config.php';
$tables = ['applications', 'worker_blocks', 'annexure2a', 'annexure3a', 'application_workflow'];
foreach ($tables as $t) {
    echo "=== $t ===\n";
    $r = mysqli_query($conn, "DESCRIBE `$t`");
    if (!$r) { echo "  [missing or error]\n"; continue; }
    while ($row = mysqli_fetch_assoc($r)) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
}

