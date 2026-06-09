<?php
/**
 * NotificationEngine.php
 * Unified notification hub for Email, SMS, and Dashboard alerts.
 */
class NotificationEngine {
    public static function trigger($conn, $userId, $title, $message, $type = 'info') {
        // 'title' is not in our schema, we combine it with message or ignore
        $fullMessage = ($title ? "[$title] " : "") . $message;
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        if ($stmt) {
            $stmt->bind_param('iss', $userId, $fullMessage, $type);
            $stmt->execute();
            $stmt->close();
        }
        return true;
    }

    public static function sendRoleNotification($conn, $role, $message, $type = 'info') {
        $users = db_fetch_all($conn, "SELECT id FROM users WHERE role = ? OR role = 'admin' OR role = 'super_admin'", 's', [$role]);
        foreach ($users as $u) {
            self::trigger($conn, $u['id'], "System Alert", $message, $type);
        }
        return true;
    }
}
?>

