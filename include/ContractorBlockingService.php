<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SAPSyncQueue.php';
require_once __DIR__ . '/AttendanceSyncQueue.php';
require_once __DIR__ . '/NotificationEngine.php';

class ContractorBlockingService {
    
    public static function blockContractor($conn, $contractorId, $reason, $remarks, $userId) {
        mysqli_begin_transaction($conn);
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $now = date('Y-m-d H:i:s');

            // 1. Update Contractor Status
            $stmt = $conn->prepare("UPDATE contractors SET status = 'blocked', is_blocked = 1, block_reason = ?, block_remarks = ?, blocked_by = ?, blocked_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Contractor Update Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('ssisi', $reason, $remarks, $userId, $now, $contractorId);
            $stmt->execute();

            // 2. Cascade to Supervisors
            $stmt = $conn->prepare("UPDATE supervisors SET status = 'inactive' WHERE contractor_id = ?");
            if (!$stmt) {
                throw new Exception("Supervisors Update Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('i', $contractorId);
            $stmt->execute();

            // 3. Cascade to Workmen
            $stmt = $conn->prepare("UPDATE workmen SET status = 'blocked', is_blocked = 1, blocked_source = 'contractor' WHERE contractor_id = ?");
            if (!$stmt) {
                throw new Exception("Workmen Update Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('i', $contractorId);
            $stmt->execute();

            // 4. Log History
            $actionType = 'BLOCK';
            $stmt = $conn->prepare("INSERT INTO contractor_block_history (contractor_id, action_type, reason, remarks, action_by, action_at, ip_address, sync_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')");
            if (!$stmt) {
                throw new Exception("History Log Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('isssiss', $contractorId, $actionType, $reason, $remarks, $userId, $now, $ip);
            $stmt->execute();

            // 5. Queue SAP Sync
            SAPSyncQueue::queue($conn, 'CONTRACTOR', $contractorId, 'BLOCK_STATUS_CHANGE', [
                'status' => 'blocked',
                'reason' => $reason,
                'remarks' => $remarks
            ]);

            // 6. Queue Attendance Sync
            AttendanceSyncQueue::queue($conn, 'CONTRACTOR', $contractorId, 'BLOCK', [
                'status' => 'blocked',
                'reason' => $reason
            ]);

            // 7. Notifications
            $contractor = db_single($conn, "SELECT contractor_name FROM contractors WHERE id = ?", 'i', [$contractorId]);
            $cName = $contractor['contractor_name'] ?? 'Contractor';
            $msg = "Contractor '{$cName}' has been BLOCKED. Reason: $reason. All workers and supervisors have been inactivated.";
            
            NotificationEngine::sendRoleNotification($conn, 'welfare_user', $msg, 'danger');
            NotificationEngine::sendRoleNotification($conn, 'admin', $msg, 'danger');

            mysqli_commit($conn);
            return ['success' => true, 'message' => 'Contractor blocked successfully and cascades triggered.'];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function unblockContractor($conn, $contractorId, $userId) {
        mysqli_begin_transaction($conn);
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $now = date('Y-m-d H:i:s');

            // 1. Restore Contractor Status
            $stmt = $conn->prepare("UPDATE contractors SET status = 'active', is_blocked = 0, activated_by = ?, activated_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Contractor Activation Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('isi', $userId, $now, $contractorId);
            $stmt->execute();

            // 2. Restore Supervisors
            $stmt = $conn->prepare("UPDATE supervisors SET status = 'active' WHERE contractor_id = ?");
            if (!$stmt) {
                throw new Exception("Supervisors Activation Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('i', $contractorId);
            $stmt->execute();

            // 3. Restore Workmen (ONLY those blocked by contractor block)
            $stmt = $conn->prepare("UPDATE workmen SET status = 'permanent_active', is_blocked = 0, blocked_source = NULL WHERE contractor_id = ? AND blocked_source = 'contractor'");
            if (!$stmt) {
                throw new Exception("Workmen Activation Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('i', $contractorId);
            $stmt->execute();

            // 4. Log History
            $actionType = 'UNBLOCK';
            $reason = 'Admin Unblock';
            $remarks = 'Restored by Welfare Admin';
            $stmt = $conn->prepare("INSERT INTO contractor_block_history (contractor_id, action_type, reason, remarks, action_by, action_at, ip_address, sync_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')");
            if (!$stmt) {
                throw new Exception("History Log Unblock Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('isssiss', $contractorId, $actionType, $reason, $remarks, $userId, $now, $ip);
            $stmt->execute();

            // 5. Queue SAP Sync
            SAPSyncQueue::queue($conn, 'CONTRACTOR', $contractorId, 'BLOCK_STATUS_CHANGE', [
                'status' => 'active'
            ]);

            // 6. Queue Attendance Sync
            AttendanceSyncQueue::queue($conn, 'CONTRACTOR', $contractorId, 'UNBLOCK', [
                'status' => 'active'
            ]);

            // 7. Notifications
            $contractor = db_single($conn, "SELECT contractor_name FROM contractors WHERE id = ?", 'i', [$contractorId]);
            $cName = $contractor['contractor_name'] ?? 'Contractor';
            $msg = "Contractor '{$cName}' has been ACTIVATED. Workers have been restored.";
            
            NotificationEngine::sendRoleNotification($conn, 'welfare_user', $msg, 'success');
            NotificationEngine::sendRoleNotification($conn, 'admin', $msg, 'success');

            mysqli_commit($conn);
            return ['success' => true, 'message' => 'Contractor activated successfully.'];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function isBlocked($conn, $contractorId) {
        $c = db_single($conn, "SELECT is_blocked FROM contractors WHERE id = ?", 'i', [$contractorId]);
        return ($c && $c['is_blocked'] == 1);
    }
}
?>
