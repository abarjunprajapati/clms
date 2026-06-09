<?php
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db_compat.php';
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}

// BASE_URL is handled by session.php

// --- DATABASE CREDENTIALS ---
$Servername  = "127.0.0.1";
$Username  = "root";
$Password  = "";
$Dbname = "new_clms";
$DbDriver = $DbDriver ?? 'mysql'; // mysql or sqlsrv

// Dynamic Live Server Override
// Create a file 'include/config_credentials.php' on the live server with your production database credentials.
// This prevents development file updates from overwriting your live database password!
$credentials_file = __DIR__ . '/config_credentials.php';
if (file_exists($credentials_file)) {
    include $credentials_file;
} else {
    // Dynamic domain check fallback (using config.live.php in root if present)
    $live_template = dirname(__DIR__) . '/config.live.php';
    if (file_exists($live_template) && isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'teleconsystems.com') !== false && stripos(__DIR__, 'xampp') === false) {
        include $live_template;
    }
}

$conn = clms_db_connect($DbDriver, $Servername, $Username, $Password, $Dbname);
if (!$conn || !empty($conn->connect_error)) {
    $is_api_request = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false;
    if ($is_api_request && php_sapi_name() !== 'cli') {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please contact administrator.',
            'error' => $conn->connect_error ?? 'Unknown database connection error'
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    die("Connection failed: " . ($conn->connect_error ?? 'Unknown database connection error'));
}

// SMS configuration - set your provider and key here
if (!defined('SMS_PROVIDER')) define('SMS_PROVIDER', 'fast2sms');
if (!defined('FAST2SMS_API_KEY')) define('FAST2SMS_API_KEY', 'YOUR_FAST2SMS_API_KEY');
if (!defined('MSG91_AUTH_KEY')) define('MSG91_AUTH_KEY', 'YOUR_MSG91_AUTH_KEY');
if (!defined('SMS_SENDER_ID')) define('SMS_SENDER_ID', 'CLMSYS');
if (!defined('SMS_DEV_MODE')) define('SMS_DEV_MODE', true); // set to false in production
if (!defined('EMAIL_DEV_MODE')) define('EMAIL_DEV_MODE', true); // set to false in production
if (!defined('EMAIL_ENABLED')) define('EMAIL_ENABLED', true);
if (!defined('EMAIL_DEMO_RECIPIENT')) define('EMAIL_DEMO_RECIPIENT', 'arjunprajapati8595@gmail.com');
if (!defined('EMAIL_FROM')) define('EMAIL_FROM', 'no-reply@clms.local');
if (!defined('EMAIL_FROM_NAME')) define('EMAIL_FROM_NAME', 'CLMS');
if (!defined('EMAIL_MAILER')) define('EMAIL_MAILER', 'smtp'); // smtp or mail
if (!defined('EMAIL_SMTP_HOST')) define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
if (!defined('EMAIL_SMTP_PORT')) define('EMAIL_SMTP_PORT', 587);
if (!defined('EMAIL_SMTP_SECURE')) define('EMAIL_SMTP_SECURE', 'tls'); // tls, ssl, or none
if (!defined('EMAIL_SMTP_USERNAME')) define('EMAIL_SMTP_USERNAME', '');
if (!defined('EMAIL_SMTP_PASSWORD')) define('EMAIL_SMTP_PASSWORD', '');

// Standardize PHP configuration for APIs
if (php_sapi_name() !== 'cli') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// DB Helper Functions for Prepared Statements (Compatible with & without mysqlnd)
if (!function_exists('db_count')) {
function db_count($conn, $sql, $types = '', $params = []) {
    try {
        $stmt = clms_db_prepare($conn, $sql);
        if ($stmt === false) return 0;
        if ($types && !empty($params)) clms_db_stmt_bind_param($stmt, $types, ...$params);
        if (!clms_db_stmt_execute($stmt)) { clms_db_stmt_close($stmt); return 0; }
        
        $row = [];
        if (true) {
            $result = clms_db_stmt_get_result($stmt);
            if ($result) $row = $result->fetch_assoc();
        } else {
            clms_db_stmt_store_result($stmt);
            $meta = clms_db_stmt_result_metadata($stmt);
            if ($meta) {
                $res = []; $refs = [];
                while ($field = clms_db_fetch_field($meta)) $refs[] = &$res[$field->name];
                call_user_func_array([$stmt, 'bind_result'], $refs);
                if (clms_db_stmt_fetch($stmt)) {
                    foreach($res as $k=>$v) $row[$k] = $v;
                }
            }
        }
        clms_db_stmt_close($stmt);
        if (!$row) return 0;
        $firstKey = array_key_first($row);
        return (int)($row[$firstKey] ?? $row['c'] ?? 0);
    } catch (Throwable $e) { return 0; }
}
}

if (!function_exists('db_fetch_all')) {
function db_fetch_all($conn, $sql, $types = '', $params = []) {
    try {
        $stmt = clms_db_prepare($conn, $sql);
        if ($stmt === false) return [];
        if ($types && !empty($params)) clms_db_stmt_bind_param($stmt, $types, ...$params);
        if (!clms_db_stmt_execute($stmt)) { clms_db_stmt_close($stmt); return []; }
        
        $data = [];
        if (true) {
            $result = clms_db_stmt_get_result($stmt);
            if ($result) while ($row = $result->fetch_assoc()) $data[] = $row;
        } else {
            clms_db_stmt_store_result($stmt);
            $meta = clms_db_stmt_result_metadata($stmt);
            if ($meta) {
                $fields = [];
                while ($field = clms_db_fetch_field($meta)) $fields[] = $field->name;
                while (true) {
                    $row = []; $refs = [];
                    foreach ($fields as $f) $refs[] = &$row[$f];
                    call_user_func_array([$stmt, 'bind_result'], $refs);
                    if (!clms_db_stmt_fetch($stmt)) break;
                    $copy = []; foreach($row as $k=>$v) $copy[$k] = $v;
                    $data[] = $copy;
                }
            }
        }
        clms_db_stmt_close($stmt);
        return $data;
    } catch (Throwable $e) { return []; }
}
}

if (!function_exists('db_single')) {
function db_single($conn, $sql, $types = '', $params = []) {
    try {
        $stmt = clms_db_prepare($conn, $sql);
        if ($stmt === false) return null;
        if ($types && !empty($params)) clms_db_stmt_bind_param($stmt, $types, ...$params);
        if (!clms_db_stmt_execute($stmt)) { clms_db_stmt_close($stmt); return null; }
        
        $row = null;
        if (true) {
            $result = clms_db_stmt_get_result($stmt);
            if ($result) $row = $result->fetch_assoc();
        } else {
            clms_db_stmt_store_result($stmt);
            $meta = clms_db_stmt_result_metadata($stmt);
            if ($meta) {
                $fields = [];
                while ($field = clms_db_fetch_field($meta)) $fields[] = $field->name;
                $res = []; $refs = [];
                foreach ($fields as $f) $refs[] = &$res[$f];
                call_user_func_array([$stmt, 'bind_result'], $refs);
                if (clms_db_stmt_fetch($stmt)) {
                    $row = []; foreach($res as $k=>$v) $row[$k] = $v;
                }
            }
        }
        clms_db_stmt_close($stmt);
        return $row;
    } catch (Throwable $e) { return null; }
}
}

if (!function_exists('db_execute')) {
function db_execute($conn, $sql, $types = '', $params = []) {
    try {
        $stmt = clms_db_prepare($conn, $sql);
        if ($stmt === false) return false;
        if ($types && !empty($params)) clms_db_stmt_bind_param($stmt, $types, ...$params);
        $success = clms_db_stmt_execute($stmt);
        clms_db_stmt_close($stmt);
        return $success;
    } catch (Throwable $e) { return false; }
}
}

if (php_sapi_name() !== 'cli') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
