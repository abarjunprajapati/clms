<?php
require 'include/config.php';
require 'include/safety_training_control.php';

$res = db_fetch_all($conn, 'SELECT id, language_name FROM training_class_batches WHERE status IN (\'open\', \'scheduled\')');
$unapproved = [];

foreach ($res as $batch) {
    $cand = clms_safety_batch_candidates($conn, $batch['id']);
    foreach ($cand as $c) {
        $w = db_single($conn, 'SELECT safety_enrollment_status FROM workmen WHERE id=?', 'i', [$c['workman_id']]);
        if(strtolower($w['safety_enrollment_status'] ?? '') !== 'approved') {
            $unapproved[] = [
                'batch_id' => $batch['id'],
                'workman_id' => $c['workman_id'],
                'request_id' => $c['training_request_id'],
                'safety_enrollment_status' => $w['safety_enrollment_status'],
                'request_status' => $c['request_status'],
                'ticked' => $c['ticked']
            ];
        }
    }
}
echo json_encode($unapproved, JSON_PRETTY_PRINT);
