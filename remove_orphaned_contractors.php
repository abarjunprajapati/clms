<?php
/**
 * DB Cleanup - Master Purge of Activated Contractors (Safe Column Check)
 */
include __DIR__ . '/include/config.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line for safety.");
}

echo "Performing MASTER PURGE for activated contractors...\n";

// Find target IDs
$res = mysqli_query($conn, "SELECT id FROM contractors WHERE (status = 'approved' OR activated_at IS NOT NULL) AND (user_id IS NULL OR user_id = '')");
$ids = [];
while($row = mysqli_fetch_assoc($res)) {
    $ids[] = $row['id'];
}

if (empty($ids)) {
    echo "No orphaned approved/activated contractors found.\n";
    exit;
}

$idList = implode(',', $ids);
echo "Targeting Contractor IDs: $idList\n";

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

$tables_to_purge = [
    'training_requests', 'annexure2a', 'annexure3a', 'contractor_annexure2a',
    'workmen', 'gate_pass_requests', 'training_sessions', 'amc_contracts',
    'contractor_documents', 'contractor_block_history', 'contractor_status_history',
    'document_verifications', 'contractor_po_selection', 'contractor_pwo_selection',
    'contractor_so_selection'
];

foreach ($tables_to_purge as $table) {
    $column = ($table === 'contractor_annexure2a' || $table === 'contractor_annexure3a') ? 'id' : 'contractor_id';
    
    // Check if column exists
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "Purging $table...\n";
        mysqli_query($conn, "DELETE FROM `$table` WHERE `$column` IN ($idList)");
    }
}

echo "Purging from contractors table...\n";
mysqli_query($conn, "DELETE FROM contractors WHERE id IN ($idList)");

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

echo "Cleanup completed successfully.\n";
