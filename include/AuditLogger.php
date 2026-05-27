<?php
/**
 * AuditLogger.php
 * Compliance-heavy audit logging for all administrative actions.
 */
class AuditLogger {
    public static function log($conn, $action, $module, $oldVal, $newVal, $remarks = '') {
        $userId = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, module, old_value, new_value, remarks, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $oldJson = is_array($oldVal) ? json_encode($oldVal) : (string)$oldVal;
            $newJson = is_array($newVal) ? json_encode($newVal) : (string)$newVal;
            $stmt->bind_param('issssss', $userId, $action, $module, $oldJson, $newJson, $remarks, $ip);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>

