<?php
/**
 * Unified Auth Wrapper for CLMS
 * Legacy support for checkAuth() and canAction()
 */
require_once __DIR__ . '/auth_middleware.php';

/**
 * Check if the user is authenticated and has the required role.
 * Wraps the new require_role() function.
 */
function checkAuth($allowed_roles = []) {
    if (empty($allowed_roles)) {
        // Just enforce login
        return;
    }
    require_role($allowed_roles);
}

/**
 * Check if user can perform a specific action.
 * Wraps the new can_user_do() function.
 */
function canAction($action) {
    // Map legacy actions to new permissions if necessary
    $map = [
        'approve_contractor' => 'contractor.approve',
        'view_reports' => 'reports.view',
        'manage_users' => 'users.manage',
        'register_worker' => 'workmen.create',
        'upload_docs' => 'compliance.upload'
    ];
    
    $permission = $map[$action] ?? $action;
    return can_user_do($permission);
}

/**
 * Standardize JSON Response (Legacy)
 */
function jsonResponse($success, $data = [], $message = '') {
    json_response($success, $data, $message);
}

/**
 * Safely get logged in User ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}
