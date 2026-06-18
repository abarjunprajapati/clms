<?php
include __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payment_flow.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Live DB Fix & Sync Script\n";
echo "=========================\n\n";

// 1. Drop unique key uq_training_fee_source
try {
    $res = mysqli_query($conn, "ALTER TABLE training_fee_masters DROP KEY uq_training_fee_source");
    if ($res) {
        echo "SUCCESS: Unique key 'uq_training_fee_source' dropped successfully!\n";
    } else {
        echo "FAILED: " . mysqli_error($conn) . "\n";
    }
} catch (Throwable $e) {
    echo "NOTICE/ERROR: " . $e->getMessage() . "\n";
}

// 2. Perform initial sync of active fee setting to fee master table
try {
    $activeFee = clms_training_fee_per_worker($conn);
    clms_sync_setting_to_fee_master($conn, $activeFee);
    echo "SUCCESS: Synced active setting ($activeFee) to training_fee_masters!\n";
} catch (Throwable $e) {
    echo "SYNC ERROR: " . $e->getMessage() . "\n";
}

// 3. Check what rows exist now
$res2 = mysqli_query($conn, "SELECT * FROM training_fee_masters");
if ($res2) {
    echo "\nCurrent Rows in training_fee_masters:\n";
    while ($row = mysqli_fetch_assoc($res2)) {
        echo "ID: {$row['id']} | Source: {$row['fee_source']} | Amount: {$row['amount']} | Status: {$row['status']} | From: {$row['from_date']} | To: {$row['to_date']}\n";
    }
}
?>
