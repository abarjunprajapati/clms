<?php
require_once 'include/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "CLMS Database Schema Repair Script: Adding AUTO_INCREMENT\n";
echo "=========================================================\n\n";

$tables_to_fix = [
    'work_orders',
    'training_sessions',
    'training_session_workers',
    'verification_checklist',
    'wages',
    'workers',
    'worker_blocks',
    'worker_block_history',
    'worker_transfer_logs',
    'workflow_instances',
    'workflow_logs',
    'workflow_revisions',
    'workflow_status',
    'workman_documents',
    'workman_education',
    'workman_experience',
    'workmen'
];

try {
    foreach ($tables_to_fix as $table) {
        echo "Checking table '$table'...\n";
        
        // 1. Get the column description
        $res = db_fetch_all($conn, "DESCRIBE `$table`");
        if (empty($res)) {
            echo "  -> WARNING: Table '$table' does not exist or could not be described.\n\n";
            continue;
        }
        
        $idCol = null;
        foreach ($res as $col) {
            if ($col['Field'] === 'id') {
                $idCol = $col;
                break;
            }
        }
        
        if (!$idCol) {
            echo "  -> WARNING: No 'id' column found in table '$table'. Skipping.\n\n";
            continue;
        }
        
        // 2. Check if already has auto_increment
        if (strpos(strtolower($idCol['Extra'] ?? ''), 'auto_increment') !== false) {
            echo "  -> ALREADY CORRECT: 'id' is already configured with AUTO_INCREMENT.\n\n";
            continue;
        }
        
        // 3. Determine column type
        $dataType = strtolower($idCol['Type']);
        $typeStr = (strpos($dataType, 'bigint') !== false) ? 'BIGINT(20)' : 'INT(11)';
        
        // 4. Perform the Alteration
        $alterSql = "ALTER TABLE `$table` MODIFY `id` $typeStr NOT NULL AUTO_INCREMENT";
        echo "  -> Action Required: Adding AUTO_INCREMENT using query:\n     $alterSql\n";
        
        $ok = db_execute($conn, $alterSql);
        if ($ok) {
            echo "  -> SUCCESS! Table '$table' was successfully updated.\n\n";
        } else {
            echo "  -> FAILED! Error: " . mysqli_error($conn) . "\n\n";
        }
    }
    
    echo "=========================================================\n";
    echo "Schema repair process completed successfully!\n";
    
} catch (Throwable $e) {
    echo "\nFatal Error during repair: " . $e->getMessage() . "\n";
}
?>
