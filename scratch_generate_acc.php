<?php
require_once 'include/config.php';
require_once 'api/WorkflowEngine.php';

$application_id = 'APP-1';
$userRole = 'admin';
$userId = 1;

$result = WorkflowEngine::performAction($conn, $application_id, 'generate_acc', $userRole, $userId, 'Generating ACC via scratch script');

header('Content-Type: application/json');
echo json_encode($result);
?>
