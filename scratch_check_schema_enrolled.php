<?php
include 'include/config.php';

$tables_to_check = [
    'worker_master',
    'worker_qualifications',
    'worker_documents',
    'worker_passes',
    'worker_safety',
    'worker_attendance',
    'worker_audit_logs',
    'worker_block_history',
    'worker_transfer_history',
    'worker_notifications',
    'worker_biometric_sync',
    'worker_pass_print_logs',
    'safety_batches',
    'workmen'
];

$results = [];
foreach ($tables_to_check as $table) {
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        $cols = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $cols[] = $row['Field'];
        }
        $results[$table] = $cols;
    } else {
        $results[$table] = false;
    }
}
echo json_encode($results, JSON_PRETTY_PRINT);
