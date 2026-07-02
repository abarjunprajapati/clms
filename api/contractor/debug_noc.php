<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting debug...<br>";

try {
    require_once __DIR__ . '/../../include/config.php';
    echo "Config loaded.<br>";
    
    require_once __DIR__ . '/../api_helper.php';
    echo "API Helper loaded.<br>";
    
    require_once __DIR__ . '/../../include/session.php';
    echo "Session loaded.<br>";

    echo "Testing sendJson...<br>";
    sendJson(200, "Debug OK");
} catch (\Throwable $e) {
    echo "<b>Fatal Error Caught:</b> " . $e->getMessage();
}
