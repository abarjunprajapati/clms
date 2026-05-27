<?php
header('Content-Type: application/json');
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo json_encode(['success' => true, 'opcache_reset' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'OPcache is not enabled or function does not exist']);
}
