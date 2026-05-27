<?php
/**
 * helpers.php - Unified Backend I/O Helper
 */

// Prevent multiple includes
if (defined('HELPERS_LOADED')) return;
define('HELPERS_LOADED', true);

// Capture all error output
$jsonErrorBuffer = '';

// Standardize PHP configuration for APIs
if (php_sapi_name() !== 'cli') {
    ini_set('display_errors', 0); // Disable HTML error output
    error_reporting(E_ALL);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
}

/**
 * Global Error Handler - captures warnings/notices
 */
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false; // Respect error suppression (@)
    }
    global $jsonErrorBuffer;
    $types = [
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
    ];
    $type = $types[$errno] ?? 'PHP Error';
    $jsonErrorBuffer .= "[$type] $errstr in $errfile:$errline\n";
    return true; // Prevent internal PHP handler
});

/**
 * Shutdown function for Fatal Errors
 */
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            "success" => false,
            "message" => "Fatal Server Error",
            "error"   => $error['message'],
            "debug"   => "Fatal error in {$error['file']}:{$error['line']}"
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
});

/**
 * Standardized JSON Response
 */
function sendResponse($success, $data = [], $message = "Success", $debug = null, $redirect = "") {
    global $jsonErrorBuffer;
    
    // Standard structure
    $response = [
        "success" => (bool)$success,
        "message" => $message,
        "redirect" => $redirect,
        "data"    => $data
    ];

    if ($jsonErrorBuffer) {
        $response['php_errors'] = trim($jsonErrorBuffer);
    }
    
    if ($debug) {
        $response['debug'] = $debug;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Unified Input Parsing (JSON Body + GET + POST)
 */
function getApiInput() {
    $json = json_decode(file_get_contents("php://input"), true);
    $input = is_array($json) ? $json : [];
    return array_merge($_GET, $_POST, $input);
}

/**
 * Standard Application ID extraction
 */
function getApplicationId($input) {
    return $input['application_id'] ?? $input['id'] ?? null;
}

/**
 * Backward compatibility functions
 */
function apiError($message, $statusCode = 400, $debug = null, $redirect = "") {
    if (!headers_sent()) {
        http_response_code($statusCode);
    }
    sendResponse(false, [], $message, $debug, $redirect);
}

function apiSuccess($data = [], $message = 'Success', $redirect = "") {
    sendResponse(true, $data, $message, null, $redirect);
}

function validateApplicationId($data) {
    $application_id = getApplicationId($data);
    if (!$application_id || $application_id === 'undefined') {
        apiError("application_id required", 400);
    }
    return $application_id;
}

function generateOtp($digits = 6) {
    $max = (int) str_repeat('9', $digits);
    return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
}

function maskMobile($mobile) {
    $clean = preg_replace('/[^0-9]/', '', $mobile);
    if (strlen($clean) >= 10) {
        return substr($clean, 0, 2) . str_repeat('*', max(0, strlen($clean) - 6)) . substr($clean, -4);
    }
    return $mobile;
}

function sendSMS($mobile, $message) {
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (empty($mobile)) {
        return [ 'success' => false, 'message' => 'Invalid mobile number' ];
    }

    $provider = defined('SMS_PROVIDER') ? SMS_PROVIDER : 'fast2sms';
    $senderId = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'CLMSYS';

    if ($provider === 'fast2sms') {
        if (!defined('FAST2SMS_API_KEY') || FAST2SMS_API_KEY === 'YOUR_FAST2SMS_API_KEY' || empty(FAST2SMS_API_KEY)) {
            return [ 'success' => false, 'message' => 'Fast2SMS API key not configured' ];
        }

        $payload = [
            'sender_id' => $senderId,
            'message' => $message,
            'language' => 'english',
            'route' => 'q',
            'numbers' => $mobile
        ];

        $ch = curl_init('https://www.fast2sms.com/dev/bulkV2');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . FAST2SMS_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [ 'success' => false, 'message' => 'Fast2SMS curl error: ' . $curlError ];
        }

        if ($httpCode !== 200) {
            return [ 'success' => false, 'message' => "Fast2SMS HTTP {$httpCode}: {$response}" ];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            return [ 'success' => false, 'message' => 'Fast2SMS invalid response: ' . $response ];
        }

        if (!empty($result['return']) && $result['return'] === true) {
            return [ 'success' => true, 'message' => 'SMS sent successfully' ];
        }

        return [ 'success' => false, 'message' => 'Fast2SMS API failure: ' . ($result['message'] ?? $response) ];
    }

    if ($provider === 'msg91') {
        if (!defined('MSG91_AUTH_KEY') || MSG91_AUTH_KEY === 'YOUR_MSG91_AUTH_KEY' || empty(MSG91_AUTH_KEY)) {
            return [ 'success' => false, 'message' => 'MSG91 API key not configured' ];
        }

        $url = 'https://api.msg91.com/api/v5/otp';
        $payload = [
            'template_id' => 'YOUR_MSG91_TEMPLATE_ID',
            'mobile' => $mobile,
            'authkey' => MSG91_AUTH_KEY,
            'message' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [ 'success' => false, 'message' => 'MSG91 curl error: ' . $curlError ];
        }

        if ($httpCode !== 200) {
            return [ 'success' => false, 'message' => "MSG91 HTTP {$httpCode}: {$response}" ];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            return [ 'success' => false, 'message' => 'MSG91 invalid response: ' . $response ];
        }

        if (!empty($result['type']) && $result['type'] === 'success') {
            return [ 'success' => true, 'message' => 'SMS sent successfully' ];
        }

        return [ 'success' => false, 'message' => 'MSG91 API failure: ' . ($result['message'] ?? $response) ];
    }

    return [ 'success' => false, 'message' => 'Unsupported SMS provider: ' . $provider ];
}
