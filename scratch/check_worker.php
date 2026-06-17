<?php
// Force live config load
$live_template = __DIR__ . '/../config.live.php';
if (file_exists($live_template)) {
    include $live_template;
}
include __DIR__ . '/../include/config.php';

$w = db_single($conn, 'SELECT id, name, execution_training_status, safety_enrollment_status, training_status FROM workmen WHERE id = ?', 'i', [8]);
print_r($w);
?>
