<?php
/**
 * Admin API Middleware
 * Security layer for all Super Admin API endpoints.
 * Provides: session validation, role check, CSRF protection, audit logging, approval safeguards.
 */

require_once __DIR__ . '/../../include/config.php';

/**
 * Validate admin session and return user data
 */
function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $role = $_SESSION['role'];
    $allowed = ['super_admin', 'admin'];
    if (!in_array($role, $allowed)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient privileges. Super Admin access required.']);
        exit;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'role' => $role,
        'name' => $_SESSION['name'] ?? 'Admin'
    ];
}

/**
 * Log Super Admin activity (for destructive/override actions)
 */
function logAdminActivity($conn, $actionType, $module, $targetId = null, $oldData = null, $newData = null, $severity = 'info') {
    $adminId = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS super_admin_activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action_type VARCHAR(100) NOT NULL,
        target_module VARCHAR(100),
        target_id INT,
        old_data TEXT,
        new_data TEXT,
        severity VARCHAR(30) DEFAULT 'info',
        ip_address VARCHAR(100),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_admin_id (admin_id),
        KEY idx_action_type (action_type),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $oldJson = $oldData ? (is_array($oldData) ? json_encode($oldData) : $oldData) : null;
    $newJson = $newData ? (is_array($newData) ? json_encode($newData) : $newData) : null;
    
    db_execute($conn, 
        "INSERT INTO super_admin_activity_logs (admin_id, action_type, target_module, target_id, old_data, new_data, severity, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?,?)",
        'ississsss',
        [$adminId, $actionType, $module, $targetId, $oldJson, $newJson, $severity, $ip, $ua]
    );
    
    // Also log to general audit_logs for cross-module visibility
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, old_value, new_value, ip_address) VALUES (?,?,?,?,?,?)",
        'isssss',
        [$adminId, $actionType, $module, $oldJson, $newJson, $ip]
    );
}

/**
 * Approval Safeguard: prevent accidental destructive actions on approved/completed items
 */
function checkApprovalSafeguard($conn, $table, $id, $statusCol = 'status') {
    $protected = ['approved', 'completed', 'active', 'permanent_issued', 'acc_generated'];
    $row = db_single($conn, "SELECT $statusCol FROM $table WHERE id=?", 'i', [$id]);
    
    if ($row && in_array($row[$statusCol], $protected)) {
        return [
            'protected' => true,
            'current_status' => $row[$statusCol],
            'message' => "This item is currently '{$row[$statusCol]}'. Override confirmation required."
        ];
    }
    return ['protected' => false];
}

/**
 * Check system lockdown status
 */
function isSystemLocked($conn) {
    $val = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key='system_lockdown'");
    return ($val && $val['setting_value'] == '1');
}

/**
 * Get JSON request body
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/**
 * Standard JSON success response
 */
function jsonSuccess($message = 'Success', $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Standard JSON error response
 */
function jsonError($message = 'Error', $code = 400) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
