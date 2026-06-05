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

function notificationTableExists($conn, $table) {
    if (!$conn || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }
    $safe = mysqli_real_escape_string($conn, $table);
    $result = @mysqli_query($conn, "SHOW TABLES LIKE '$safe'");
    return $result && mysqli_num_rows($result) > 0;
}

function notificationSetting($conn, $key, $fallback = '') {
    if (!$conn || !notificationTableExists($conn, 'system_settings')) {
        return $fallback;
    }
    $row = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1", 's', [$key]);
    $value = $row['setting_value'] ?? null;
    return ($value === null || $value === '') ? $fallback : $value;
}

function notificationEnv($name, $fallback = '') {
    $value = getenv($name);
    return ($value === false || $value === '') ? $fallback : $value;
}

function notificationLog($conn, $recipient, $channel, $type, $subject, $message, $status, $error = '', $recipientName = '') {
    if (!$conn) {
        return;
    }
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notification_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(100),
        recipient_name VARCHAR(100),
        channel ENUM('sms','email','push','system') DEFAULT 'system',
        type VARCHAR(50),
        subject VARCHAR(200),
        message TEXT,
        status ENUM('sent','delivered','failed','queued') DEFAULT 'queued',
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_channel (channel),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (notificationTableExists($conn, 'notification_logs')) {
        db_execute(
            $conn,
            "INSERT INTO notification_logs (recipient, recipient_name, channel, type, subject, message, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            'ssssssss',
            [
                substr((string)$recipient, 0, 100),
                substr((string)$recipientName, 0, 100),
                $channel,
                substr((string)$type, 0, 50),
                substr((string)$subject, 0, 200),
                (string)$message,
                $status,
                (string)$error
            ]
        );
    }
}

function emailHeaderEncode($value) {
    $value = trim((string)$value);
    return preg_match('/[^\x20-\x7E]/', $value)
        ? '=?UTF-8?B?' . base64_encode($value) . '?='
        : $value;
}

function smtpRead($socket) {
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtpCommand($socket, $command, array $expectedCodes) {
    if ($command !== '') {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtpRead($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new Exception("SMTP command failed ($command): " . trim($response));
    }
    return $response;
}

function smtpDataBody($data) {
    $data = str_replace(["\r\n", "\r"], "\n", (string)$data);
    $lines = explode("\n", $data);
    foreach ($lines as &$line) {
        if (isset($line[0]) && $line[0] === '.') {
            $line = '.' . $line;
        }
    }
    return implode("\r\n", $lines);
}

function sendEmailViaSmtp($to, $subject, $message, $from, $fromName) {
    global $conn;

    $host = notificationEnv('EMAIL_SMTP_HOST', notificationSetting($conn ?? null, 'email_smtp_host', defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : ''));
    $port = (int)notificationEnv('EMAIL_SMTP_PORT', notificationSetting($conn ?? null, 'email_smtp_port', defined('EMAIL_SMTP_PORT') ? EMAIL_SMTP_PORT : 587));
    $secure = strtolower(trim(notificationEnv('EMAIL_SMTP_SECURE', notificationSetting($conn ?? null, 'email_smtp_secure', defined('EMAIL_SMTP_SECURE') ? EMAIL_SMTP_SECURE : 'tls'))));
    $username = notificationEnv('EMAIL_SMTP_USERNAME', notificationSetting($conn ?? null, 'email_smtp_username', defined('EMAIL_SMTP_USERNAME') ? EMAIL_SMTP_USERNAME : ''));
    $password = notificationEnv('EMAIL_SMTP_PASSWORD', notificationSetting($conn ?? null, 'email_smtp_password', defined('EMAIL_SMTP_PASSWORD') ? EMAIL_SMTP_PASSWORD : ''));

    if ($host === '' || $username === '' || $password === '') {
        return [ 'success' => false, 'message' => 'SMTP host/username/password not configured' ];
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return [ 'success' => false, 'message' => "SMTP connect failed: $errstr ($errno)" ];
    }

    stream_set_timeout($socket, 30);
    try {
        $banner = smtpRead($socket);
        if ((int)substr($banner, 0, 3) !== 220) {
            throw new Exception('SMTP banner error: ' . trim($banner));
        }

        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        smtpCommand($socket, "EHLO $serverName", [250]);

        if ($secure === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('SMTP STARTTLS negotiation failed');
            }
            smtpCommand($socket, "EHLO $serverName", [250]);
        }

        smtpCommand($socket, 'AUTH LOGIN', [334]);
        smtpCommand($socket, base64_encode($username), [334]);
        smtpCommand($socket, base64_encode($password), [235]);
        smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . emailHeaderEncode($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . emailHeaderEncode($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit'
        ];
        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . smtpDataBody($message) . "\r\n.\r\n");
        $response = smtpRead($socket);
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, [250], true)) {
            throw new Exception('SMTP DATA failed: ' . trim($response));
        }

        smtpCommand($socket, 'QUIT', [221, 250]);
        fclose($socket);
        return [ 'success' => true, 'message' => 'Email sent successfully via SMTP' ];
    } catch (Throwable $e) {
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return [ 'success' => false, 'message' => $e->getMessage() ];
    }
}

function sendSMS($mobile, $message) {
    global $conn;

    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (empty($mobile)) {
        return [ 'success' => false, 'message' => 'Invalid mobile number' ];
    }

    $configuredEnabled = notificationSetting($conn ?? null, 'sms_enabled', defined('SMS_DEV_MODE') && SMS_DEV_MODE ? '0' : '1');
    $smsEnabled = in_array(strtolower((string)$configuredEnabled), ['1', 'true', 'yes', 'on'], true);
    $devMode = defined('SMS_DEV_MODE') && SMS_DEV_MODE;

    if (!$smsEnabled && !$devMode) {
        notificationLog($conn ?? null, $mobile, 'sms', 'sms', '', $message, 'failed', 'SMS notifications disabled');
        return [ 'success' => false, 'message' => 'SMS notifications disabled' ];
    }

    $provider = strtolower(trim(notificationSetting($conn ?? null, 'sms_provider', defined('SMS_PROVIDER') ? SMS_PROVIDER : 'fast2sms')));
    $senderId = notificationSetting($conn ?? null, 'sms_sender_id', defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'CLMSYS');
    $apiKey = notificationEnv('SMS_API_KEY', notificationSetting($conn ?? null, 'sms_api_key', ''));

    if ($provider === 'fast2sms') {
        if ($apiKey === '' && defined('FAST2SMS_API_KEY')) {
            $apiKey = FAST2SMS_API_KEY;
        }
        if ($apiKey === '' || in_array($apiKey, ['YOUR_API_KEY', 'YOUR_FAST2SMS_API_KEY'], true)) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'Fast2SMS API key not configured');
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
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'Fast2SMS curl error: ' . $curlError);
            return [ 'success' => false, 'message' => 'Fast2SMS curl error: ' . $curlError ];
        }

        if ($httpCode !== 200) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', "Fast2SMS HTTP {$httpCode}: {$response}");
            return [ 'success' => false, 'message' => "Fast2SMS HTTP {$httpCode}: {$response}" ];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'Fast2SMS invalid response: ' . $response);
            return [ 'success' => false, 'message' => 'Fast2SMS invalid response: ' . $response ];
        }

        if (!empty($result['return']) && $result['return'] === true) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'sent');
            return [ 'success' => true, 'message' => 'SMS sent successfully' ];
        }

        notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'Fast2SMS API failure: ' . ($result['message'] ?? $response));
        return [ 'success' => false, 'message' => 'Fast2SMS API failure: ' . ($result['message'] ?? $response) ];
    }

    if ($provider === 'msg91') {
        if ($apiKey === '' && defined('MSG91_AUTH_KEY')) {
            $apiKey = MSG91_AUTH_KEY;
        }
        if ($apiKey === '' || in_array($apiKey, ['YOUR_API_KEY', 'YOUR_MSG91_AUTH_KEY'], true)) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'MSG91 API key not configured');
            return [ 'success' => false, 'message' => 'MSG91 API key not configured' ];
        }

        $templateId = notificationEnv('MSG91_TEMPLATE_ID', notificationSetting($conn ?? null, 'msg91_template_id', 'YOUR_MSG91_TEMPLATE_ID'));
        $url = 'https://api.msg91.com/api/v5/otp';
        $payload = [
            'template_id' => $templateId,
            'mobile' => $mobile,
            'authkey' => $apiKey,
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
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'MSG91 curl error: ' . $curlError);
            return [ 'success' => false, 'message' => 'MSG91 curl error: ' . $curlError ];
        }

        if ($httpCode !== 200) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', "MSG91 HTTP {$httpCode}: {$response}");
            return [ 'success' => false, 'message' => "MSG91 HTTP {$httpCode}: {$response}" ];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'MSG91 invalid response: ' . $response);
            return [ 'success' => false, 'message' => 'MSG91 invalid response: ' . $response ];
        }

        if (!empty($result['type']) && $result['type'] === 'success') {
            notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'sent');
            return [ 'success' => true, 'message' => 'SMS sent successfully' ];
        }

        notificationLog($conn ?? null, $mobile, 'sms', 'otp', '', $message, 'failed', 'MSG91 API failure: ' . ($result['message'] ?? $response));
        return [ 'success' => false, 'message' => 'MSG91 API failure: ' . ($result['message'] ?? $response) ];
    }

    notificationLog($conn ?? null, $mobile, 'sms', 'sms', '', $message, 'failed', 'Unsupported SMS provider: ' . $provider);
    return [ 'success' => false, 'message' => 'Unsupported SMS provider: ' . $provider ];
}

function sendEmailNotification($to, $subject, $message, $type = 'general', $recipientName = '') {
    global $conn;

    $to = trim((string)$to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [ 'success' => false, 'message' => 'Invalid email address' ];
    }

    $configuredEnabled = notificationEnv(
        'EMAIL_ENABLED',
        defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? '1' : '0') : notificationSetting($conn ?? null, 'email_enabled', defined('EMAIL_DEV_MODE') && EMAIL_DEV_MODE ? '1' : '0')
    );
    $emailEnabled = in_array(strtolower((string)$configuredEnabled), ['1', 'true', 'yes', 'on'], true);
    if (!$emailEnabled) {
        notificationLog($conn ?? null, $to, 'email', $type, $subject, $message, 'failed', 'Email notifications disabled', $recipientName);
        return [ 'success' => false, 'message' => 'Email notifications disabled' ];
    }

    $from = notificationEnv('EMAIL_FROM', notificationSetting($conn ?? null, 'email_from', defined('EMAIL_FROM') ? EMAIL_FROM : 'no-reply@clms.local'));
    $fromName = notificationEnv('EMAIL_FROM_NAME', notificationSetting($conn ?? null, 'email_from_name', defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'CLMS'));
    $mailer = strtolower(trim(notificationEnv('EMAIL_MAILER', notificationSetting($conn ?? null, 'email_mailer', defined('EMAIL_MAILER') ? EMAIL_MAILER : 'smtp'))));

    if ($mailer === 'smtp') {
        $result = sendEmailViaSmtp($to, $subject, $message, $from, $fromName);
        notificationLog($conn ?? null, $to, 'email', $type, $subject, $message, !empty($result['success']) ? 'sent' : 'failed', !empty($result['success']) ? '' : ($result['message'] ?? 'SMTP failed'), $recipientName);
        return $result;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . sprintf('%s <%s>', $fromName, $from)
    ];

    $ok = @mail($to, $subject, $message, implode("\r\n", $headers));
    notificationLog($conn ?? null, $to, 'email', $type, $subject, $message, $ok ? 'sent' : 'failed', $ok ? '' : 'PHP mail() returned false', $recipientName);

    return $ok
        ? [ 'success' => true, 'message' => 'Email sent successfully' ]
        : [ 'success' => false, 'message' => 'PHP mail() returned false' ];
}

function sendDemoEmailNotification($subject, $message, $type = 'demo') {
    $recipient = defined('EMAIL_DEMO_RECIPIENT') ? EMAIL_DEMO_RECIPIENT : 'arjunprajapati8595@gmail.com';
    return sendEmailNotification($recipient, $subject, $message, $type, 'Demo Recipient');
}
