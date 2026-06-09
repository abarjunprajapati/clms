<?php
/**
 * Auth Middleware for CLMS
 * Include at top of every protected page/API.
 */

require_once __DIR__ . '/session.php';

// Refresh activity on every protected request
refresh_session_activity();

// Detect if this is an API request
$isApi = (strpos($_SERVER['PHP_SELF'], '/api/') !== false)
      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
      || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// ---- CHECK 1: Not logged in ----
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || empty($_SESSION['logged_in'])) {
    if ($isApi) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.', 'code' => 'NOT_LOGGED_IN']);
        exit;
    }
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
    $redirectParam = $currentUrl ? '?redirect=' . urlencode($currentUrl) : '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $internalLoginPaths = [
        '/pages/admin/',
        '/pages/welfare/',
        '/pages/safety/',
        '/pages/frontline/',
        '/pages/execution/',
        '/pages/amc/',
        '/pages/payments/',
        '/pages/worker/'
    ];
    $loginPage = 'index.php';
    foreach ($internalLoginPaths as $internalPath) {
        if (strpos($scriptName, $internalPath) !== false) {
            $loginPage = 'internal-login.php';
            break;
        }
    }
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '../') . $loginPage . $redirectParam);
    exit;
}

// ---- CHECK 2: Session timeout ----
if (is_session_timed_out()) {
    destroy_session();
    if ($isApi) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Session expired. Please login again.', 'code' => 'SESSION_TIMEOUT']);
        exit;
    }
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '../index.php') . '?timeout=1');
    exit;
}

// ---- CHECK 2b: CSRF for authenticated mutating API requests ----
if ($isApi && !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
    require_csrf();
}

// ---- CHECK 3: Contractor/customer onboarding lock ----
// First login should land on Annexure 2A/3A until welfare approval is complete.
if (!$isApi && in_array($_SESSION['role'] ?? '', ['contractor', 'customer'], true)) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/onboarding_status.php';

    $onboardingRedirect = clms_onboarding_redirect_for_session($conn);
    if ($onboardingRedirect && !clms_onboarding_current_page_allowed($_SESSION['role'], $_SERVER['SCRIPT_NAME'] ?? '')) {
        header('Location: ' . BASE_URL . $onboardingRedirect);
        exit;
    }
}

// Full Enterprise RBAC Matrix
$clms_rbac_matrix = [
    'super_admin' => ['*'],
    'welfare_admin' => [
        'dashboard.view', 'contractor.view', 'contractor.approve', 'contractor.edit',
        'workmen.view', 'workmen.approve', 'workmen.block', 'pass.view', 'pass.approve',
        'acc.view', 'acc.generate', 'reports.view', 'audit.view', 'users.manage'
    ],
    'welfare_user' => [
        'dashboard.view', 'contractor.view', 'contractor.verify', 'workmen.view', 
        'workmen.verify', 'pass.view', 'pass.verify', 'acc.view', 'reports.view'
    ],
    'contractor' => [
        'dashboard.view', 'profile.manage', 'annexure.create', 'annexure.view',
        'workmen.create', 'workmen.edit', 'workmen.view', 'training.request',
        'gatepass.request', 'compliance.upload', 'reports.own'
    ],
    'customer' => [
        'dashboard.view', 'contractor.view_assigned', 'workmen.view_assigned',
        'attendance.view_assigned', 'pass.view_assigned', 'reports.view_assigned'
    ],
    'execution_officer' => [
        'dashboard.view', 'contractor.monitor', 'workmen.monitor',
        'attendance.view', 'observations.create', 'reports.view'
    ],
    'execution' => [
        'dashboard.view', 'contractor.monitor', 'workmen.monitor',
        'attendance.view', 'observations.create', 'reports.view'
    ],
    'front_line_user' => ['gate.validate', 'pass.verify', 'logs.view'],
    'pass_user' => ['pass.issue', 'acc.view', 'acc.generate', 'documents.verify'],
    'safety_user' => ['training.manage', 'training.results', 'reports.view']
];

function can_user_do($permission) {
    global $clms_rbac_matrix;
    $role = $_SESSION['role'] ?? 'guest';
    if (!isset($clms_rbac_matrix[$role])) return false;
    $perms = $clms_rbac_matrix[$role];
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

function require_permission($permission) {
    if (!can_user_do($permission)) {
        if (strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => "Missing permission: $permission", 'code' => 'FORBIDDEN_ACTION']);
            exit;
        }
        header('Location: ' . BASE_URL . 'pages/access-denied.php?perm=' . urlencode($permission));
        exit;
    }
}

function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !validate_csrf()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security check failed (CSRF).', 'code' => 'CSRF_INVALID']);
        exit;
    }
}

function require_role(array $allowedRoles) {
    $currentRole = $_SESSION['role'] ?? null;
    if ($currentRole === 'super_admin') return;
    if (!in_array($currentRole, $allowedRoles, true)) {
        if (strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Unauthorized role.', 'code' => 'FORBIDDEN']);
            exit;
        }
        header('Location: ' . BASE_URL . 'pages/access-denied.php');
        exit;
    }
}

function json_response($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}
