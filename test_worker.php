<?php
require 'include/config.php';
$w = db_single($conn, "SELECT id, contractor_id, temp_id, name, status, training_status, safety_training_status, safety_language, training_valid_till FROM workmen WHERE temp_id='TEMP-000058'");
print_r($w);
if ($w) {
    $tr = db_fetch_all($conn, "SELECT id, status, contractor_id FROM training_requests WHERE workman_id=" . (int)$w['id']);
    print_r($tr);
}
echo "\nDone.";
?>
