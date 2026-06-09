<?php
include __DIR__ . '/../include/config.php';

echo "=== Contractors list and statuses ===\n";
$res = $conn->query("SELECT id, vendor_code, contractor_name, vendor_name, status, application_no FROM contractors");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Vendor: {$row['vendor_code']} | Name: " . ($row['contractor_name'] ?: $row['vendor_name']) . " | Status: {$row['status']} | AppNo: {$row['application_no']}\n";
}

echo "\n=== Annexure2a workflow statuses ===\n";
$res2 = $conn->query("SELECT id, contractor_id, application_id, workflow_status, submitted_at, updated_at FROM annexure2a");
while ($row = $res2->fetch_assoc()) {
    echo "ID: {$row['id']} | ContractorID: {$row['contractor_id']} | AppID: {$row['application_id']} | WorkflowStatus: {$row['workflow_status']} | Submitted: {$row['submitted_at']} | Updated: {$row['updated_at']}\n";
}
