<?php
// =====================================
// LIVE SERVER CONFIGURATION TEMPLATE
// =====================================
// 
// Instructions:
// 1. Edit this file with your LIVE SERVER database details
// 2. Replace include/config.php on live server with this
// 3. DO NOT commit this to git (contains passwords)
//
// Ask your hosting provider for:
// - Database Host (usually localhost or specific IP)
// - Database Name (given when database created)
// - Database Username (given when user created)
// - Database Password (that you set or were given)
// =====================================

date_default_timezone_set('Asia/Kolkata');

// LIVE SERVER DATABASE CREDENTIALS
// ================================
$Servername  = "localhost";        // CHANGE THIS - Ask hosting provider
$Username    = "sachin";        // CHANGE THIS - Your DB username
$Password    = "a";  // CHANGE THIS - Your DB password
$Dbname      = "new_clms";  // CHANGE THIS - Your DB name

if (defined('CLMS_CONFIG_ONLY')) {
    error_log('[CLMS] config.live.php loaded with CLMS_CONFIG_ONLY; returning early.');
    return;
}

// If DB helper functions are already defined by another config file, skip rest.
if (function_exists('db_execute')) {
    error_log('[CLMS] DB helpers already defined; skipping config.live.php helpers.');
    return;
}

if (!function_exists('clms_has_open_mysqli_connection')) {
    function clms_has_open_mysqli_connection($connection) {
        if (!$connection instanceof mysqli) {
            return false;
        }
        try {
            return @mysqli_ping($connection);
        } catch (Throwable $e) {
            return false;
        }
    }
}

$has_existing_connection = isset($conn) && clms_has_open_mysqli_connection($conn);

// Establish connection
if (!$has_existing_connection) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $driver = new mysqli_driver();
    $driver->report_mode = MYSQLI_REPORT_OFF;
    $conn = mysqli_init();
    if ($conn) {
        @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $connected = @mysqli_real_connect($conn, $Servername, $Username, $Password, $Dbname);
        if (!$connected) {
            @mysqli_close($conn);
            $conn = false;
        }
    }
}

if (!$conn) {
    $last_db_error = mysqli_connect_error();
    error_log('[DB CONNECT FAILED] host=' . $Servername . ' user=' . $Username . ' db=' . $Dbname . ' error=' . $last_db_error);
    $display_message = stripos($last_db_error, 'too many connections') !== false
        ? 'Database server is busy. Please try again in a minute.'
        : 'Database connection failed. Please contact administrator.';
    $is_api_request = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false;
    if ($is_api_request && php_sapi_name() !== 'cli') {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'message' => $display_message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    die("Connection failed: " . $display_message);
}

if (!$has_existing_connection && php_sapi_name() !== 'cli') {
    register_shutdown_function(function () use (&$conn) {
        if ($conn instanceof mysqli) {
            @$conn->close();
        }
    });
}

// SMS configuration
if (!defined('SMS_PROVIDER')) define('SMS_PROVIDER', 'fast2sms');
if (!defined('FAST2SMS_API_KEY')) define('FAST2SMS_API_KEY', 'YOUR_FAST2SMS_API_KEY');
if (!defined('MSG91_AUTH_KEY')) define('MSG91_AUTH_KEY', 'YOUR_MSG91_AUTH_KEY');
if (!defined('SMS_SENDER_ID')) define('SMS_SENDER_ID', 'CLMSYS');
if (!defined('SMS_DEV_MODE')) define('SMS_DEV_MODE', true); // ENABLE sandbox dev mode for testing

// If you have more database servers, you can add secondary connections here
// $conn_backup = mysqli_connect('backup-host', 'backup_user', 'backup_pass', 'backup_db');

// Array key helper for compatibility
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}

// DB Helper Functions (these are the same in dev and production)
if (!function_exists('db_count')) {
function db_count($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $error = mysqli_error($conn);
        trigger_error("Prepare failed for count: $error | Query: $sql", E_USER_ERROR);
        return 0;
    }
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        trigger_error("Execute failed for count: $error | Query: $sql", E_USER_ERROR);
        return 0;
    }
    $result = mysqli_stmt_get_result($stmt);
    $row = $result->fetch_assoc();
    mysqli_stmt_close($stmt);
    $firstKey = array_key_first($row);
    return (int)($row[$firstKey] ?? $row['c'] ?? 0);
}
}

if (!function_exists('db_fetch_all')) {
function db_fetch_all($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $error = mysqli_error($conn);
        trigger_error("Prepare failed: $error | Query: $sql", E_USER_ERROR);
        return [];
    }
    if ($types && !empty($params)) {
        // Create proper references for mysqli_stmt_bind_param
        $refs = [];
        foreach ($params as &$param) {
            $refs[] = &$param;
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $types], $refs));
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        trigger_error("Execute failed: $error | Query: $sql", E_USER_ERROR);
        return [];
    }
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $data;
}
}

if (!function_exists('db_single')) {
function db_single($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $error = mysqli_error($conn);
        trigger_error("Prepare failed: $error | Query: $sql", E_USER_ERROR);
        return [];
    }
    if ($types && !empty($params)) {
        // Create proper references for mysqli_stmt_bind_param
        $refs = [];
        foreach ($params as &$param) {
            $refs[] = &$param;
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $types], $refs));
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        trigger_error("Execute failed (single): $error | Query: $sql", E_USER_ERROR);
        return [];
    }
    $result = mysqli_stmt_get_result($stmt);
    $row = $result->fetch_assoc();
    mysqli_stmt_close($stmt);
    return $row;
}
}

if (!function_exists('db_execute')) {
function db_execute($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        $error = mysqli_error($conn);
        trigger_error("Prepare failed: $error | Query: $sql", E_USER_ERROR);
        return false;
    }
    if ($types && !empty($params)) {
        // Create proper references for mysqli_stmt_bind_param
        $refs = [];
        foreach ($params as &$param) {
            $refs[] = &$param;
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt, $types], $refs));
    }
    $success = mysqli_stmt_execute($stmt);
    if (!$success) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        trigger_error("Execute failed (execute): $error | Query: $sql", E_USER_ERROR);
        return false;
    }
    mysqli_stmt_close($stmt);
    return $success;
}
}
?>

