<?php
require_once __DIR__ . '/../../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$ids = [50, 51, 52, 53];
echo "=== WORKMEN DETAILS ===\n";
foreach ($ids as $id) {
    $w = db_single($conn, "SELECT id, name, temp_id, contractor_id, execution_training_status, safety_enrollment_status, training_status, safety_training_status, training_approval_doc FROM workmen WHERE id = ?", 'i', [$id]);
    if ($w) {
        print_r($w);
    } else {
        echo "Workman ID $id not found.\n";
    }
}

echo "\n=== TRAINING REQUESTS ===\n";
foreach ($ids as $id) {
    $reqs = db_fetch_all($conn, "SELECT id, workman_id, status, source, remarks, created_at, updated_at FROM training_requests WHERE workman_id = ? ORDER BY id DESC", 'i', [$id]);
    if ($reqs) {
        foreach ($reqs as $r) {
            print_r($r);
        }
    } else {
        echo "No training requests for Workman ID $id.\n";
    }
}
?>
