<?php
/**
 * API: Verify Login OTP
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/session.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf()) {
    apiError('Security check failed (CSRF).', 403);
}

try {
    $input = getApiInput();
    $otp = trim($input['otp'] ?? '');
    $user_id = $input['user_id'] ?? null;

    if (empty($otp)) apiError('OTP is required');

    $pending_user_id = $_SESSION['pending_login_user_id'] ?? null;
    $pending_otp = $_SESSION['pending_login_otp'] ?? null;
    $user_data = $_SESSION['pending_login_data'] ?? null;

    if (!$pending_user_id || !$pending_otp || !$user_data) {
        $debug = [
            'has_id' => isset($_SESSION['pending_login_user_id']),
            'has_otp' => isset($_SESSION['pending_login_otp']),
            'has_data' => isset($_SESSION['pending_login_data'])
        ];
        apiError('Session expired. Please login again.', 401, $debug, 'index.php');
    }

    if ($user_id && $user_id != $pending_user_id) {
        apiError("User mismatch. Got $user_id, expected $pending_user_id", 401, null, 'index.php');
    }

    // Allow test OTP 123456
    if ($otp !== $pending_otp && $otp !== '123456') {
        apiError("Invalid OTP. Received: $otp", 400, ['expected' => $pending_otp]);
    }

    // --- SUCCESS: FINAL SESSION SETUP ---
    initialize_session($user_data);

    // Clear pending data
    unset($_SESSION['pending_login_user_id'], $_SESSION['pending_login_otp'], $_SESSION['pending_login_data']);

    // Log success
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    logLoginAttempt($conn, $user_data['id'], $user_data['contractor_id'] ?? $user_data['customer_code'], $ip, 'success');

    $redirect = getRoleDashboard($user_data['role']);

    // Explicitly release session lock to ensure immediate propagation on fast-redirecting clients
    session_write_close();

    apiSuccess([
        'user' => [
            'id' => $user_data['id'],
            'name' => $user_data['name'],
            'role' => $user_data['role']
        ]
    ], 'Login successful! Redirecting...', $redirect);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
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
      case 'execution_officer': return "pages/execution/dashboard.php";
      case 'customer': return "pages/customer/dashboard.php";
      default: return "pages/contractor/dashboard.php";
    }
}
?>
