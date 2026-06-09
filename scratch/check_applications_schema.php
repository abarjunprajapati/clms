<?php
require 'include/config.php';

$alter = "ALTER TABLE applications CHANGE COLUMN workflow_status status ENUM(
        'draft',
        'submitted',
        'verified',
        'approved',
        'enrolment_done',
        'training_done',
        'gatepass_requested',
        'gatepass_verified',
        'temporary_pass_issued',
        'acc_generated',
        'permanent_pass_issued'
      ) DEFAULT 'draft'";
if ($conn->query($alter)) {
    echo "Status ENUM updated successfully.\n";
} else {
    echo "Alter error: " . $conn->error . "\n";
}

$desc = $conn->query("DESCRIBE applications");
while($r = $desc->fetch_assoc()) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}

