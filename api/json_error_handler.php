<?php
/**
 * Global JSON Error Handler for CLMS API endpoints
 * Include this at the TOP of every API file (before any output) to ensure
 * PHP errors/warnings/notices are captured and returned as JSON instead of HTML.
 * 
 * Usage: require_once 'json_error_handler.php';
 */

// Prevent multiple initializations
if (defined('JSON_ERROR_HANDLER_LOADED')) return;
define('JSON_ERROR_HANDLER_LOADED', true);

// Capture all error output
$jsonErrorBuffer = '';

// Set JSON header early
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

// Custom error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$jsonErrorBuffer) {
    $types = [
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_DEPRECATED => 'Deprecated',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_USER_DEPRECATED => 'User Deprecated',
        E_STRICT => 'Strict',
    ];
    $type = $types[$errno] ?? 'Error';
    $jsonErrorBuffer .= "[$type] $errstr in $errfile:$errline\n";
    // Don't execute PHP internal error handler
    return true;
});

// Capture fatal errors via shutdown function
register_shutdown_function(function () use (&$jsonErrorBuffer) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $jsonErrorBuffer .= "[Fatal] {$error['message']} in {$error['file']}:{$error['line']}\n";
    }
    
    // If there were any captured errors and nothing was output yet, send JSON error
    if ($jsonErrorBuffer !== '' && !defined('JSON_ERROR_HANDLED')) {
        define('JSON_ERROR_HANDLED', true);
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'debug' => $jsonErrorBuffer,
            'hint' => 'Check PHP error logs or enable display_errors for details'
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// Helper to flush any captured errors (call at end of successful API scripts)
function jsonErrorFlush() {
    global $jsonErrorBuffer;
    $jsonErrorBuffer = '';
}


