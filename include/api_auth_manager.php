<?php
/**
 * API Authentication Manager
 * Handles secure token generation, verification, and device tracking.
 */
include_once __DIR__ . '/config.php';

function generate_api_token($user_id, $device_id) {
    global $conn;
    
    $token = bin2hex(random_bytes(32));
    $refresh_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sql = "INSERT INTO api_tokens (user_id, token, refresh_token, device_id, expires_at) 
            VALUES (?, ?, ?, ?, ?)";
    
    if (db_execute($conn, $sql, 'issss', [$user_id, $token, $refresh_token, $device_id, $expires_at])) {
        return [
            'access_token' => $token,
            'refresh_token' => $refresh_token,
            'expires_in' => 86400
        ];
    }
    return null;
}

function verify_api_token($token) {
    global $conn;
    
    $sql = "SELECT t.*, u.role, u.name 
            FROM api_tokens t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.token = ? AND t.expires_at > NOW()";
    
    $result = db_single($conn, $sql, 's', [$token]);
    
    if ($result) {
        // Log access
        db_execute($conn, "INSERT INTO api_access_logs (user_id, device_id) VALUES (?, ?)", 'is', [$result['user_id'], $result['device_id']]);
        return $result;
    }
    return null;
}

function register_device($user_id, $device_id, $device_name, $os_version) {
    global $conn;
    
    $sql = "INSERT INTO api_devices (user_id, device_id, device_name, os_version) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE last_login = CURRENT_TIMESTAMP, os_version = VALUES(os_version)";
    
    return db_execute($conn, $sql, 'isss', [$user_id, $device_id, $device_name, $os_version]);
}
?>
