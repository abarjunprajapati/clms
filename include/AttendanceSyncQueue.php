<?php
class AttendanceSyncQueue {
    public static function queue($conn, $entityType, $entityId, $action, $payload = []) {
        $payloadJson = json_encode($payload);
        $stmt = $conn->prepare("INSERT INTO attendance_sync_queue (entity_type, entity_id, action, payload, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param('siss', $entityType, $entityId, $action, $payloadJson);
            $stmt->execute();
            $stmt->close();
            return true;
        }
        return false;
    }
}
?>
