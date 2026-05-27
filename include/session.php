<?php
// Prevent client and proxy caching completely
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

// Temporary Global Error Logger to diagnose live server 500 / Fatal Errors
if (php_sapi_name() !== 'cli') {
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../error_debug.log');

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        $logMsg = date('[Y-m-d H:i:s]') . " PHP Warning/Error: $message in $file on line $line" . PHP_EOL;
        file_put_contents(__DIR__ . '/../error_debug.log', $logMsg, FILE_APPEND);
        return false; 
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logMsg = date('[Y-m-d H:i:s]') . " PHP Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}" . PHP_EOL;
            file_put_contents(__DIR__ . '/../error_debug.log', $logMsg, FILE_APPEND);
        }
    });

    set_exception_handler(function ($exception) {
        $logMsg = date('[Y-m-d H:i:s]') . " PHP Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . PHP_EOL;
        $logMsg .= $exception->getTraceAsString() . PHP_EOL;
        file_put_contents(__DIR__ . '/../error_debug.log', $logMsg, FILE_APPEND);
    });
}

/**
 * CLMS Session Manager
 * Safe, single-point session handling.
 * Usage: require_once __DIR__ . '/session.php';
 */

// Global Base URL Detection (available even if session already started)
if (!defined('BASE_URL')) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $root = '/';
    $clms_pos = strpos($script_name, '/clms/');
    if ($clms_pos !== false) {
        $root = '/clms/';
    } else {
        if (preg_match('/^(.*\/)(api|pages|include|ajax|css|js|uploads)\//', $script_name, $matches)) {
            $root = $matches[1];
        } else {
            $dir = dirname($script_name);
            $root = ($dir === DIRECTORY_SEPARATOR || $dir === '\\' || $dir === '/') ? '/' : rtrim($dir, '/\\') . '/';
        }
    }
    define('BASE_URL', $root);
}

// Prevent duplicate session starts
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $path = BASE_URL;
    session_set_cookie_params(0, $path, $cookieParams['domain'], false, true);
    session_start();
}

// Idle timeout (seconds)
define('SESSION_IDLE_TIMEOUT', 30 * 60); // 30 minutes

function refresh_session_activity() {
    $_SESSION['last_activity'] = time();
}

function is_session_timed_out() {
    if (empty($_SESSION['last_activity'])) return false;
    return (time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT;
}

function regenerate_session_safe() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function destroy_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        session_destroy();
    }
}

/**
 * Generate CSRF token if missing.
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from header or POST.
 */
function validate_csrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Ensure all required session keys exist.
 */
function initialize_session(array $user) {
    // Prevent session propagation race conditions on fast AJAX redirects
    // if (session_status() === PHP_SESSION_ACTIVE) {
    //     session_regenerate_id(true);
    // }

    $_SESSION['user_id']        = $user['id'];
    $_SESSION['username']       = $user['username'] ?? $user['email'] ?? $user['contractor_id'] ?? $user['customer_code'];
    $_SESSION['name']           = $user['name'] ?? $_SESSION['username'];
    $_SESSION['role']           = $user['role'];
    $_SESSION['email']          = $user['email'] ?? '';
    $_SESSION['contractor_id']  = $user['contractor_id'] ?? null;
    $_SESSION['customer_code']  = $user['customer_code'] ?? null;
    $_SESSION['customer_name']  = $user['customer_name'] ?? null;
    $_SESSION['vendor_code']    = $_SESSION['contractor_id'];
    $_SESSION['logged_in']      = true;
    $_SESSION['login_time']     = time();
    $_SESSION['last_activity']  = time();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Get normalized current user role from active session
 */
function get_normalized_role() {
    return $_SESSION['role'] ?? null;
}
