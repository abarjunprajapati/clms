<?php
require_once __DIR__ . '/../include/config.php';

echo "=== Search Anmol ===\n";
$rows = db_fetch_all($conn, "SELECT * FROM workmen WHERE name LIKE '%Anmol%'");
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Name: {$row['name']} | ContractorID: {$row['contractor_id']} | Status: {$row['status']} | WorkerType: {$row['worker_type']} | RoleType: {$row['role_type']}\n";
}
?>
