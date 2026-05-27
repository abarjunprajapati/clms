<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
require_once '../../include/config.php';

$contractor_id = $_SESSION['contractor_id'] ?? 0;

$sql = "SELECT ws.application_id, ws.current_status, a.contractor_id 
        FROM workflow_status ws
        JOIN applications a ON ws.application_id = a.id
        WHERE a.contractor_id = ?
        ORDER BY ws.updated_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $contractor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$applications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => [
        'applications' => $applications
    ]
]);

