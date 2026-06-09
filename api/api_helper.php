<?php
/**
 * api_helper.php - Wrapper for helpers.php (Migration path)
 */
require_once __DIR__ . '/helpers.php';

// CORS HEADERS (preserved for compatibility)
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 86400");
    // Content-Type is already set in helpers.php
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
