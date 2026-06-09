<?php
require_once 'c:/xampp/htdocs/clms/include/config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 1; // Default to 1 if not set
echo "Session User ID: $user_id\n";

$contractor = db_single($conn, "SELECT id, contractor_name, application_no FROM contractors WHERE user_id = ?", 'i', [$user_id]);
if ($contractor) {
    echo "Contractor Found: " . $contractor['contractor_name'] . " (ID: " . $contractor['id'] . ")\n";
    echo "Application No: " . $contractor['application_no'] . "\n";
    
    $workers = db_fetch_all($conn, "SELECT id, name, training_status, contractor_id FROM workmen WHERE contractor_id = ?", 'i', [$contractor['id']]);
    echo "Workers for this contractor: " . count($workers) . "\n";
    foreach($workers as $w) {
        echo " - Worker ID: " . $w['id'] . ", Name: " . $w['name'] . ", Training Status: " . $w['training_status'] . "\n";
    }
} else {
    echo "Contractor NOT found for User ID $user_id\n";
}

