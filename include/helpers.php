<?php
/**
 * helpers.php - Global API Response Helper
 */

function jsonResponse($success, $data = [], $message = '') {
    // Clear any previous output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "success" => (bool)$success,
        "data" => $data,
        "message" => $message
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

