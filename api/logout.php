<?php
/**
 * CLMS Logout API
 * Destroys server-side session completely.
 */
require_once __DIR__ . '/../include/session.php';

try {
    $role = $_SESSION['role'] ?? '';
    $internalRoles = [
        'super_admin',
        'admin',
        'welfare_admin',
        'welfare_user',
        'welfare',
        'safety_user',
        'safety',
        'front_line_user',
        'frontline',
        'pass_user',
        'pass_issuer',
        'execution_officer',
        'execution'
    ];
    $redirect = in_array($role, $internalRoles, true) ? '../internal-login.php' : '../index.php';

    destroy_session();
    
    // Check if it's an AJAX/API request
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => $redirect
        ]);
    } else {
        header('Location: ' . $redirect);
        exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Logout failed: ' . $e->getMessage()
    ]);
}


