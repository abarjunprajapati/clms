<?php
/**
 * SAPSyncQueue.php
 * Handles decoupled SAP S/4 HANA integration via a sync queue.
 */
class SAPSyncQueue {
    public static function queue($conn, $type, $id, $action, $payload) {
        $payloadJson = json_encode($payload);
        $stmt = $conn->prepare("INSERT INTO sap_sync_queue (entity_type, entity_id, action, payload) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssss', $type, $id, $action, $payloadJson);
            $stmt->execute();
            $stmt->close();
            return true;
        }
        return false;
    }
}
?>

