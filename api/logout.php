<?php
/**
 * CLMS Logout API
 * Destroys server-side session completely.
 */
require_once __DIR__ . '/../include/session.php';

header('Content-Type: application/json');

try {
    destroy_session();
    
    // Check if it's an AJAX/API request
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    } else {
        // Normal browser click: redirect to index
        header('Location: ../index.php');
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Logout failed: ' . $e->getMessage()
    ]);
}


