<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SAPSyncQueue.php';
require_once __DIR__ . '/AttendanceSyncQueue.php';
require_once __DIR__ . '/NotificationEngine.php';

class ContractorBlockingService {
    private static function columnExists($conn, $table, $column) {
        return db_count(
            $conn,
            "SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            'ss',
            [$table, $column]
        ) > 0;
    }

    private static function tryQuery($conn, $sql) {
        try {
            return (bool)$conn->query($sql);
        } catch (Throwable $e) {
            @file_put_contents(dirname(__DIR__) . '/logs/api_errors.log', '[CONTRACTOR_BLOCKING] ' . date('c') . ' - ' . $e->getMessage() . ' | ' . $sql . "\n", FILE_APPEND);
            return false;
        }
    }

    private static function ensureSchema($conn) {
        $contractorColumns = [
            'is_blocked' => "ALTER TABLE contractors ADD is_blocked TINYINT(1) DEFAULT 0",
            'block_reason' => "ALTER TABLE contractors ADD block_reason VARCHAR(255) DEFAULT NULL",
            'block_remarks' => "ALTER TABLE contractors ADD block_remarks TEXT DEFAULT NULL",
            'blocked_by' => "ALTER TABLE contractors ADD blocked_by INT DEFAULT NULL",
            'blocked_at' => "ALTER TABLE contractors ADD blocked_at DATETIME DEFAULT NULL",
            'activated_by' => "ALTER TABLE contractors ADD activated_by INT DEFAULT NULL",
            'activated_at' => "ALTER TABLE contractors ADD activated_at DATETIME DEFAULT NULL",
        ];
        foreach ($contractorColumns as $column => $sql) {
            if (!self::columnExists($conn, 'contractors', $column)) {
                self::tryQuery($conn, $sql);
            }
        }

        if (!self::columnExists($conn, 'workmen', 'is_blocked')) {
            self::tryQuery($conn, "ALTER TABLE workmen ADD is_blocked TINYINT(1) DEFAULT 0");
        }
        if (!self::columnExists($conn, 'workmen', 'blocked_source')) {
            self::tryQuery($conn, "ALTER TABLE workmen ADD blocked_source VARCHAR(30) DEFAULT NULL");
        }

        self::tryQuery($conn, "CREATE TABLE IF NOT EXISTS contractor_block_history (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            action_type VARCHAR(20) DEFAULT NULL,
            reason TEXT DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            action_by INT DEFAULT NULL,
            action_at DATETIME DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            sync_status VARCHAR(20) DEFAULT 'PENDING',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $historyColumns = [
            'action_type' => "ALTER TABLE contractor_block_history ADD action_type VARCHAR(20) DEFAULT NULL",
            'reason' => "ALTER TABLE contractor_block_history ADD reason TEXT DEFAULT NULL",
            'remarks' => "ALTER TABLE contractor_block_history ADD remarks TEXT DEFAULT NULL",
            'action_by' => "ALTER TABLE contractor_block_history ADD action_by INT DEFAULT NULL",
            'action_at' => "ALTER TABLE contractor_block_history ADD action_at DATETIME DEFAULT NULL",
            'ip_address' => "ALTER TABLE contractor_block_history ADD ip_address VARCHAR(45) DEFAULT NULL",
            'sync_status' => "ALTER TABLE contractor_block_history ADD sync_status VARCHAR(20) DEFAULT 'PENDING'",
        ];
        foreach ($historyColumns as $column => $sql) {
            if (!self::columnExists($conn, 'contractor_block_history', $column)) {
                self::tryQuery($conn, $sql);
            }
        }
        if (self::columnExists($conn, 'contractor_block_history', 'action')) {
            self::tryQuery($conn, "ALTER TABLE contractor_block_history MODIFY action VARCHAR(20) DEFAULT NULL");
        }
    }
    
    public static function blockContractor($conn, $contractorId, $reason, $remarks, $userId) {
        self::ensureSchema($conn);
        clms_db_begin_transaction($conn);
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

            clms_db_commit($conn);
            return ['success' => true, 'message' => 'Contractor blocked successfully and cascades triggered.'];
        } catch (Exception $e) {
            clms_db_rollback($conn);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function unblockContractor($conn, $contractorId, $userId) {
        self::ensureSchema($conn);
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $now = date('Y-m-d H:i:s');

            // 1. Restore Contractor Status
            $stmt = $conn->prepare("UPDATE contractors SET status = 'approved', is_blocked = 0, block_reason = NULL, block_remarks = NULL, activated_by = ?, activated_at = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Contractor Activation Prepare Failed: " . $conn->error);
            }
            $stmt->bind_param('isi', $userId, $now, $contractorId);
            $stmt->execute();
            if ($stmt->affected_rows < 1) {
                $exists = db_count($conn, "SELECT COUNT(*) FROM contractors WHERE id = ?", 'i', [$contractorId]);
                if (!$exists) {
                    throw new Exception("Contractor not found.");
                }
            }

            // 2. Restore Supervisors
            $stmt = $conn->prepare("UPDATE supervisors SET status = 'active' WHERE contractor_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $contractorId);
                $stmt->execute();
            } else {
                self::tryQuery($conn, "CREATE TABLE IF NOT EXISTS supervisors (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, contractor_id INT DEFAULT NULL, status VARCHAR(30) DEFAULT 'active')");
            }

            // 3. Restore Workmen (ONLY those blocked by contractor block)
            $stmt = $conn->prepare("UPDATE workmen SET status = 'permanent_active', is_blocked = 0, blocked_source = NULL WHERE contractor_id = ? AND (blocked_source = 'contractor' OR status = 'blocked')");
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
            if (!$stmt->execute()) {
                self::tryQuery($conn, "INSERT INTO contractor_block_history (contractor_id, reason, action_by, created_at) VALUES (" . (int)$contractorId . ", 'Admin Unblock', " . (int)$userId . ", NOW())");
            }

            // 5. Queue SAP Sync
            try {
                SAPSyncQueue::queue($conn, 'CONTRACTOR', $contractorId, 'BLOCK_STATUS_CHANGE', [
                    'status' => 'approved'
                ]);
            } catch (Throwable $e) {
                self::tryQuery($conn, "SELECT 1");
            }

            // 6. Queue Attendance Sync
            try {
                AttendanceSyncQueue::queue($conn, 'CONTRACTOR', $contractorId, 'UNBLOCK', [
                    'status' => 'approved'
                ]);
            } catch (Throwable $e) {
                self::tryQuery($conn, "SELECT 1");
            }

            // 7. Notifications
            $contractor = db_single($conn, "SELECT contractor_name FROM contractors WHERE id = ?", 'i', [$contractorId]);
            $cName = $contractor['contractor_name'] ?? 'Contractor';
            $msg = "Contractor '{$cName}' has been ACTIVATED. Workers have been restored.";
            
            try {
                NotificationEngine::sendRoleNotification($conn, 'welfare_user', $msg, 'success');
                NotificationEngine::sendRoleNotification($conn, 'admin', $msg, 'success');
            } catch (Throwable $e) {
                self::tryQuery($conn, "SELECT 1");
            }

            return ['success' => true, 'message' => 'Contractor activated successfully.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function isBlocked($conn, $contractorId) {
        $c = db_single($conn, "SELECT is_blocked FROM contractors WHERE id = ?", 'i', [$contractorId]);
        return ($c && $c['is_blocked'] == 1);
    }
}
?>
