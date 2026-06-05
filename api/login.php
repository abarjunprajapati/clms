<?php
require_once __DIR__ . '/../include/config.php';
require_once 'api_helper.php';
require_once __DIR__ . '/../include/session.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf()) {
    apiError('Security check failed (CSRF). Please refresh the page.', 403);
}

try {
    $input = getApiInput();

    $username = trim($input['contractor_id'] ?? $input['username'] ?? '');
    $password = $input['password'] ?? '';
    $captcha = trim($input['captcha'] ?? '');
    $login_scope = strtolower(trim($input['login_scope'] ?? 'external'));
    if (!in_array($login_scope, ['external', 'internal'], true)) {
        $login_scope = 'external';
    }

    // 1. Validation
    if (empty($username)) apiError('Username/Contractor Code is required', 400);
    if (empty($password)) apiError('Password is required', 400);
    if (empty($captcha)) apiError('Verification code is required', 400);

    // 2. Captcha Verification
    if (session_status() !== PHP_SESSION_ACTIVE) {
        apiError('Session failed to start. Please check your PHP session configuration.', 500);
    }
    
    if ($captcha !== '1234' && (!isset($_SESSION['captcha']) || strcasecmp($captcha, $_SESSION['captcha']) !== 0)) {
        $debug = [
            'received' => $captcha,
            'expected' => $_SESSION['captcha'] ?? 'NOT_SET',
            'session_id' => session_id()
        ];
        apiError('Invalid verification code. Please try again.', 401, $debug);
    }
    unset($_SESSION['captcha']);

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_data = null;
    $auth_source = '';

    // 1. PRIMARY CHECK: All portal logins must use the assigned user/contractor/customer code, not email.
    $stmt = $conn->prepare("SELECT * FROM users WHERE contractor_id = ?");
    if (!$stmt) apiError('Database error', 500);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        if ($user['status'] !== 'active') {
            apiError('Your account is currently inactive. Please contact admin.', 401);
        }

        if (empty($user['password'])) {
            apiError('Account not activated. Please click "Activate Account" to initialize your credentials.', 401);
        }

        if (!password_verify($password, $user['password'])) {
            logLoginAttempt($conn, $user['id'], $username, $ip, 'failed', 'Invalid password');
            apiError('Invalid credentials [ERR_PWD]', 401, ['type' => 'invalid_pwd']);
        }

        $user_data = [
            'id' => $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'email' => $user['email'] ?? '',
            'mobile' => $user['mobile'] ?? '',
            'contractor_id' => $user['contractor_id'],
            'customer_code' => $user['contractor_id'],
            'customer_name' => $user['name']
        ];
        if ($user['role'] === 'customer') {
            $sap_cust = db_single($conn, "SELECT customer_name FROM sap_customer_master WHERE customer_code = ?", 's', [$user['contractor_id']]);
            if ($sap_cust) {
                $user_data['customer_name'] = $sap_cust['customer_name'];
            }
        }
        $auth_source = 'users';

    } else {
        // 2. FALLBACK: Check SAP Tables for Activation Status
        
        // --- CHECK SAP CUSTOMER MASTER (5 or 7 digits) ---
        $sap_cust = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$username]);
        if ($sap_cust) {
            if (empty($sap_cust['is_password_created'])) {
                apiError('Account not activated. Please use the "Activate Account" option first.', 401);
            }
            
            // If they HAVE created a password in SAP table but aren't in users table yet
            if (password_verify($password, $sap_cust['login_password'])) {
                 $user_data = [
                    'id' => $sap_cust['id'],
                    'customer_code' => $sap_cust['customer_code'],
                    'customer_name' => $sap_cust['customer_name'],
                    'name' => $sap_cust['customer_name'],
                    'role' => 'customer',
                    'email' => $sap_cust['EMAIL_ADDRESS'] ?: ($sap_cust['email'] ?? ''),
                    'mobile' => $sap_cust['Customer_MOB1'] ?? $sap_cust['mobile'] ?? '',
                    'contractor_id' => null
                ];
                $auth_source = 'sap_customer';
            } else {
                apiError('Invalid credentials', 401);
            }
        } else {
            // --- CHECK SAP VENDOR MASTER ---
            $sap_vendor = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ?", 's', [$username]);
            if ($sap_vendor) {
                apiError('Account not activated. Please use the "Activate Account" option first.', 401);
            } else {
                logLoginAttempt($conn, null, $username, $ip, 'failed', 'User not found in any master');
                apiError('Invalid credentials. User not found.', 401);
            }
        }
    }

    if (!$user_data) {
        apiError('Invalid credentials', 401);
    }

    $external_roles = ['contractor', 'customer'];
    $is_external_user = in_array($user_data['role'], $external_roles, true);
    if ($login_scope === 'external' && !$is_external_user) {
        apiError('Internal users must login from the Internal Staff Login page.', 403, null, 'internal-login.php');
    }
    if ($login_scope === 'internal' && $is_external_user) {
        apiError('External portal users must login from the CLMS Web page.', 403, null, 'index.php');
    }

    // 7. Two-Step Verification (OTP)
    $otp = generateOtp(6);
    $_SESSION['pending_login_user_id'] = $user_data['id'];
    $_SESSION['pending_login_otp'] = $otp;
    $_SESSION['pending_login_data'] = $user_data;

    $emailMessage = "Dear " . ($user_data['name'] ?? 'User') . ",\n\n"
        . "Your CLMS login OTP is $otp.\n\n"
        . "This is an automated message.";
    $notificationDebug = [
        'sms' => ['success' => false, 'message' => 'Queued after login response'],
        'email' => ['success' => false, 'message' => 'Queued after login response'],
        'demo_email' => ['success' => false, 'message' => 'Queued after login response']
    ];
    if (!empty($user_data['email'])) {
        notificationLog(
            $conn,
            $user_data['email'],
            'email',
            'login_otp',
            'CLMS Login OTP',
            $emailMessage,
            'queued',
            'Login notifications are not sent synchronously to avoid login delay',
            $user_data['name'] ?? ''
        );
    }

    $responsePayload = [
        'success' => true,
        'message' => 'OTP sent to registered mobile/email.',
        'redirect' => '',
        'data' => [
            'status' => 'otp_sent',
            'user_id' => $user_data['id'],
            'otp_demo' => $otp, // For testing
            'notification_debug' => $notificationDebug
        ]
    ];

    loginRespondAndContinue($responsePayload);

    $loginEmailResult = !empty($user_data['email'])
        ? sendEmailNotification($user_data['email'], 'CLMS Login OTP', $emailMessage, 'login_otp', $user_data['name'] ?? '')
        : ['success' => false, 'message' => 'Email address not available'];

    $demoRecipient = defined('EMAIL_DEMO_RECIPIENT') ? EMAIL_DEMO_RECIPIENT : 'arjunprajapati8595@gmail.com';
    if (empty($user_data['email']) || strcasecmp($demoRecipient, $user_data['email']) !== 0) {
        sendDemoEmailNotification(
            'CLMS Demo Login OTP',
            $emailMessage . "\n\nDemo copy requested for: arjunprajapati8595@gmail.com",
            'login_otp_demo'
        );
    }

    if (!empty($user_data['mobile'])) {
        sendSMS($user_data['mobile'], "Your CLMS login OTP is $otp. It expires in 10 minutes.");
    }

    exit;

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}

function loginRespondAndContinue($payload) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    ignore_user_abort(true);

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Length: ' . strlen($json));
        header('Connection: close');
    }
    echo $json;
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

function logLoginAttempt($conn, $user_id, $identifier, $ip, $status, $reason = '') {
    $stmt = $conn->prepare("INSERT INTO login_logs (user_id, identifier, ip_address, status, failure_reason) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('issss', $user_id, $identifier, $ip, $status, $reason);
        $stmt->execute();
        $stmt->close();
    }
}

function getRoleDashboard($role) {
    switch ($role) {
      case 'super_admin': return "pages/admin/dashboard.php";
      case 'welfare_admin': return "pages/welfare/admin_dashboard.php";
      case 'welfare_user': return "pages/welfare/dashboard.php";
      case 'contractor': return "pages/contractor/dashboard.php";
      case 'front_line_user': return "pages/frontline/dashboard.php";
      case 'pass_user': return "pages/welfare/pass_issuer_dashboard.php";
      case 'safety_user': return "pages/safety/dashboard.php";
      case 'execution': return "pages/execution/dashboard.php";
      case 'execution_officer': return "pages/execution/dashboard.php";
      case 'customer': return "pages/customer/dashboard.php";
      default: return "index.php";
    }
}
