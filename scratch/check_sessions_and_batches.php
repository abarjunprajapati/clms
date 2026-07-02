<?php
include __DIR__ . '/../include/config.php';

echo "=== training_class_batches status count ===\n";
$res1 = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM training_class_batches GROUP BY status");
if ($res1) {
    while ($row = mysqli_fetch_assoc($res1)) {
        echo "Status: " . ($row['status'] ?? 'NULL') . " | Count: " . $row['count'] . "\n";
    }
} else {
    echo "Error querying training_class_batches: " . mysqli_error($conn) . "\n";
}

echo "\n=== training_schedule vs training_class_batches ===\n";
$res2 = mysqli_query($conn, "
    SELECT ts.id, ts.batch_number, ts.session_status, cb.status as batch_status 
    FROM training_schedule ts
    LEFT JOIN training_class_batches cb ON cb.batch_number = ts.batch_number
    LIMIT 20
");
if ($res2) {
    while ($row = mysqli_fetch_assoc($res2)) {
        echo "Session ID: {$row['id']} | Batch No: {$row['batch_number']} | Session Status: {$row['session_status']} | Batch Status: " . ($row['batch_status'] ?? 'NULL') . "\n";
    }
} else {
    echo "Error querying training_schedule: " . mysqli_error($conn) . "\n";
}
