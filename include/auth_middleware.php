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

// ---- CHECK 2c: Blocked User Verification ----
if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'contractor') {
    require_once __DIR__ . '/config.php';
    $stmtBlock = $conn->prepare("SELECT is_blocked, block_reason FROM contractors WHERE id = ?");
    if ($stmtBlock) {
        $stmtBlock->bind_param("i", $_SESSION['user_id']);
        $stmtBlock->execute();
        $resBlock = $stmtBlock->get_result();
        $cData = $resBlock->fetch_assoc();
        $stmtBlock->close();
        if ($cData && !empty($cData['is_blocked'])) {
            destroy_session();
            if ($isApi) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access Denied: Your account has been blocked.', 'code' => 'BLOCKED']);
                exit;
            }
            $reasonParam = !empty($cData['block_reason']) ? '&reason=' . urlencode($cData['block_reason']) : '';
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '../index.php') . '?blocked=1' . $reasonParam);
            exit;
        }
    }
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

/**
 * Load permissions for a role from DB (cached in session for performance)
 */
function load_role_permissions_from_db($role) {
    if (isset($_SESSION['_db_perms_loaded'])) return;
    $_SESSION['_db_perms_loaded'] = true;
    
    // Only load for non-super_admin roles
    if ($role === 'super_admin') return;
    
    try {
        global $conn;
        require_once __DIR__ . '/config.php';
        $user_id = $_SESSION['user_id'] ?? 0;
        
        // Load ROLE-level permissions from DB
        $res = $conn->prepare("SELECT module, can_view, can_create, can_edit, can_delete, can_approve, can_block, can_export, can_override FROM role_permissions WHERE role_name = ?");
        if ($res) {
            $res->bind_param('s', $role);
            $res->execute();
            $rows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
            $_SESSION['_role_perms'] = [];
            foreach ($rows as $r) {
                $_SESSION['_role_perms'][$r['module']] = $r;
            }
        }
        
        // Load USER-level extra permissions from DB (overrides role)
        $res2 = $conn->prepare("SELECT module, can_view, can_create, can_edit, can_delete, can_approve, can_block, can_export, can_override FROM user_extra_permissions WHERE user_id = ?");
        if ($res2) {
            $res2->bind_param('i', $user_id);
            $res2->execute();
            $rows2 = $res2->get_result()->fetch_all(MYSQLI_ASSOC);
            $_SESSION['_user_perms'] = [];
            foreach ($rows2 as $r) {
                $_SESSION['_user_perms'][$r['module']] = $r;
            }
        }
    } catch (Exception $e) {
        // silently fail - don't break page if DB perms fail
    }
}

/**
 * Check if user has a specific permission from DB permissions table
 * Returns null if no DB rule found (fallback to hardcoded RBAC)
 */
function check_db_permission($module, $action = 'can_view') {
    $role = $_SESSION['role'] ?? 'guest';
    if ($role === 'super_admin') return true;
    
    load_role_permissions_from_db($role);
    
    // Check user-level first (takes priority)
    if (!empty($_SESSION['_user_perms'][$module][$action])) {
        return true;
    }
    // Then check role-level
    if (isset($_SESSION['_role_perms'][$module][$action])) {
        return (bool)$_SESSION['_role_perms'][$module][$action];
    }
    return null; // no DB rule — fallback
}

/**
 * Get all modules a user has can_view access to (from DB)
 */
function get_user_accessible_modules() {
    $role = $_SESSION['role'] ?? 'guest';
    if ($role === 'super_admin') return ['*'];
    
    load_role_permissions_from_db($role);
    $modules = [];
    $allPerms = array_merge($_SESSION['_role_perms'] ?? [], $_SESSION['_user_perms'] ?? []);
    foreach ($allPerms as $mod => $p) {
        if (!empty($p['can_view'])) $modules[] = $mod;
    }
    return $modules;
}

function can_user_do($permission) {
    global $clms_rbac_matrix;
    $role = $_SESSION['role'] ?? 'guest';
    if ($role === 'super_admin') return true;
    
    // Parse module.action format e.g. "training.approve"
    $parts = explode('.', $permission);
    if (count($parts) === 2) {
        $mod    = $parts[0];
        $action = 'can_' . $parts[1]; // e.g. can_approve
        $dbCheck = check_db_permission($mod, $action);
        if ($dbCheck !== null) return $dbCheck;
    }
    
    // Fallback to hardcoded matrix
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

    // First check hardcoded role list
    if (in_array($currentRole, $allowedRoles, true)) return;

    // Then check DB: if super_admin granted this user or their role extra permissions, let them through
    if ($currentRole) {
        load_role_permissions_from_db($currentRole);
        
        // If user has personal extra permissions, let them pass
        if (!empty($_SESSION['_user_perms'])) {
            return;
        }
        
        // If the role itself has been granted any permissions in the DB, let them pass the basic check
        // The individual page will use can_user_do() to check the specific module
        if (!empty($_SESSION['_role_perms'])) {
            foreach ($_SESSION['_role_perms'] as $mod => $perms) {
                if (!empty($perms['can_view'])) return;
            }
        }
    }

    if (strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. Unauthorized role.', 'code' => 'FORBIDDEN']);
        exit;
    }
    header('Location: ' . BASE_URL . 'pages/access-denied.php');
    exit;
}

function json_response($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

/**
 * Clear DB permission cache from session (call after Super Admin updates permissions)
 */
function clear_permission_cache() {
    unset($_SESSION['_db_perms_loaded']);
    unset($_SESSION['_role_perms']);
    unset($_SESSION['_user_perms']);
}

/**
 * Quick helper: check if current user can do action on module (DB + RBAC)
 * Usage: perm_check('training', 'approve')  // checks can_approve on training module
 */
function perm_check($module, $action = 'view') {
    return can_user_do($module . '.' . $action);
}
