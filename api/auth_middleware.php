<?php
/**
 * Auth Middleware for CLMS
 * Handles strict Role-Based Access Control (RBAC) and permissions
 */

function enforceRole($allowedRoles) {
    require_once __DIR__ . '/../include/session.php';

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => false, 'message' => 'Unauthorized: Please log in']);
        exit();
    }

    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'] ?? 'guest';

    // Proactive Blocking Check for Contractors
    if ($userRole === 'contractor') {
        global $conn;
        if (!$conn) include_once __DIR__ . '/../include/config.php';
        
        $blocked = db_single($conn, "SELECT is_blocked, block_reason FROM contractors WHERE user_id = ?", 'i', [$userId]);
        if ($blocked && $blocked['is_blocked']) {
            session_destroy();
            http_response_code(403);
            echo json_encode([
                'status' => false, 
                'message' => 'Your firm has been BLOCKED by Welfare Section. Reason: ' . ($blocked['block_reason'] ?: 'Security/Disciplinary reasons'),
                'blocked' => true
            ]);
            exit();
        }
    }

    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    // Admin has access to everything by default if not strictly excluded
    if ($userRole === 'admin' || $userRole === 'super_admin') {
        return true;
    }

    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        echo json_encode([
            'status' => false, 
            'message' => "Forbidden: Your role '{$userRole}' does not have permission for this action"
        ]);
        exit();
    }

    return true;
}

function enforcePermission($action) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Permission check usually follows a role check, but we can call enforceRole here if needed
    // or just assume enforceRole was already called.
    
    $userRole = $_SESSION['role'] ?? 'guest';

    // Map specific actions to roles
    $actionPermissions = [
        'manage_users' => ['admin', 'super_admin', 'welfare_admin'],
        'approve_application' => ['admin', 'welfare_admin', 'welfare_user'],
        'verify_application' => ['admin', 'welfare_admin', 'welfare_user'],
        'submit_application' => ['contractor'],
        'manage_training' => ['admin', 'safety_user'],
        'issue_gatepass' => ['admin', 'pass_issuer'],
        'view_monitoring' => ['admin', 'super_admin', 'execution', 'execution_officer', 'welfare_admin', 'safety'],
        'add_observation' => ['admin', 'execution', 'execution_officer'],
        'add_escalation' => ['admin', 'execution', 'execution_officer'],
        'view_productivity' => ['admin', 'execution', 'execution_officer'],
        'view_attendance_exceptions' => ['admin', 'execution', 'execution_officer']
    ];

    if (!isset($actionPermissions[$action])) {
        // Unknown action, default to deny
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => "Forbidden: Unknown action permission '{$action}'"]);
        exit();
    }

    $allowedRoles = $actionPermissions[$action];
    
    if ($userRole === 'admin' || $userRole === 'super_admin') {
        return true;
    }

    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        echo json_encode([
            'status' => false, 
            'message' => "Forbidden: You do not have permission to perform '{$action}'"
        ]);
        exit();
    }

    return true;
}
?>
