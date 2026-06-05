<?php
require_once __DIR__ . '/RuleEngine.php';
require_once __DIR__ . '/SapDemo.php';
require_once dirname(__DIR__) . '/include/temporary_pass_validity.php';

class WorkflowEngine {
    private static $logFile = '';

    private static function getLogFile() {
        if (!self::$logFile) {
            self::$logFile = dirname(__DIR__) . '/logs/workflow.log';
        }
        return self::$logFile;
    }

    public static function performAction(
        $conn,
        $applicationId,
        $action,
        $userRole = 'admin',
        $userId = 0,
        $remarks = '',
        $additionalData = []
    ) {
        $applicationId = self::resolveAppId($conn, $applicationId);
        $row = self::fetchCurrentStatus($conn, $applicationId);
        if (!$row) {
            return self::fail($applicationId, 'UNKNOWN', 'N/A', $action, $userRole, "Application '$applicationId' not found");
        }

        $oldStatus = RuleEngine::normalizeStatus($row['workflow_status'] ?? '') ?: 'submitted';
        $newStatus = RuleEngine::getNextStatus($oldStatus, $action);
        if (!$newStatus) {
            $valid = implode(', ', RuleEngine::getValidActions($oldStatus));
            return self::fail($applicationId, $oldStatus, 'N/A', $action, $userRole, "Invalid action '$action' for status '$oldStatus'. Valid actions: [$valid]");
        }

        if (!RuleEngine::canTransition($oldStatus, $action, $userRole)) {
            return self::fail($applicationId, $oldStatus, $newStatus, $action, $userRole, "Role '$userRole' is not authorised for '$action'");
        }

        $started = false;
        if (!self::isInTransaction($conn)) {
            $conn->begin_transaction();
            $started = true;
        }

        try {
            self::syncCoreStatus($conn, $applicationId, $newStatus);
            self::processActionSpecificLogic($conn, $applicationId, $action, $additionalData);

            $remark = $remarks ?: "Status changed: $oldStatus -> $newStatus";
            self::insertRemark($conn, $applicationId, $remark, $action);
            self::insertWorkflowLog($conn, $applicationId, $oldStatus, $newStatus, $action, $userId, $userRole, $remark);

            if ($started) {
                $conn->commit();
            }

            self::fileLog($applicationId, $oldStatus, $newStatus, $action, $userRole, true, $remark);
            return [
                'success' => true,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => "Transition successful: $oldStatus -> $newStatus",
            ];
        } catch (Throwable $e) {
            if ($started) {
                $conn->rollback();
            }
            return self::fail($applicationId, $oldStatus, $newStatus, $action, $userRole, 'DB Error: ' . $e->getMessage());
        }
    }

    public static function setStatus(
        $conn,
        $applicationId,
        $newStatus,
        $userRole = 'admin',
        $userId = 0,
        $remarks = ''
    ) {
        $applicationId = self::resolveAppId($conn, $applicationId);
        $row = self::fetchCurrentStatus($conn, $applicationId);
        if (!$row) {
            return self::fail($applicationId, 'UNKNOWN', $newStatus, 'set_status', $userRole, "Application '$applicationId' not found");
        }

        $oldStatus = RuleEngine::normalizeStatus($row['workflow_status'] ?? '') ?: 'submitted';
        $remark = $remarks ?: "Status changed: $oldStatus -> $newStatus";

        $started = false;
        if (!self::isInTransaction($conn)) {
            $conn->begin_transaction();
            $started = true;
        }

        try {
            self::syncCoreStatus($conn, $applicationId, $newStatus);
            self::insertRemark($conn, $applicationId, $remark, 'set_status');
            self::insertWorkflowLog($conn, $applicationId, $oldStatus, $newStatus, 'set_status', $userId, $userRole, $remark);
            if ($started) {
                $conn->commit();
            }

            self::fileLog($applicationId, $oldStatus, $newStatus, 'set_status', $userRole, true, $remark);
            return ['success' => true, 'old_status' => $oldStatus, 'new_status' => $newStatus, 'message' => $remark];
        } catch (Throwable $e) {
            if ($started) {
                $conn->rollback();
            }
            return self::fail($applicationId, $oldStatus, $newStatus, 'set_status', $userRole, 'DB Error: ' . $e->getMessage());
        }
    }

    private static function processActionSpecificLogic($conn, $applicationId, $action, $additionalData = []) {
        $app = $conn->real_escape_string($applicationId);

        if ($action === 'verify_documents' || $action === 'verify_reupload') {
            $conn->query("UPDATE gate_passes SET documents_verified = 1 WHERE application_no = '$app' AND status = 'pending'");
            $conn->query("UPDATE workmen SET pass_issuer_verified = 1, status = 'verified' 
                          WHERE application_no = '$app' 
                          AND status IN ('pending', 'enrolled', 'biometric_done', 'training_passed')
                          AND training_status IN ('pass','passed','training_passed','qualified','completed')");
            return;
        }

        if ($action === 'issue_temporary_pass') {
            $defaultDays = function_exists('clms_get_temporary_pass_validity_days') ? clms_get_temporary_pass_validity_days($conn) : 7;
            $days = max(1, min($defaultDays, (int)($additionalData['temp_validity'] ?? $defaultDays)));
            $validTo = date('Y-m-d', strtotime("+$days days"));
            $validToSql = $conn->real_escape_string($validTo);
            
            $res = $conn->query("SELECT id FROM workmen WHERE application_no = '$app' AND status IN ('verified','pending') AND training_status IN ('pass','passed','training_passed','qualified','completed')");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $wid = (int)$row['id'];
                    $tempNo = 'TEMP-' . date('Y') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
                    $tempNoSql = $conn->real_escape_string($tempNo);
                    $conn->query("UPDATE workmen SET temp_pass_status = 1, temp_pass_no = '$tempNoSql', temp_valid_from = CURDATE(), temp_valid_to = '$validToSql', status = 'temporary_issued' WHERE id = $wid");
                }
            }
            
            $conn->query("UPDATE gate_passes SET approved_date = CURDATE(), valid_from = CURDATE(), valid_to = '$validToSql', status = 'approved' WHERE application_no = '$app' AND status = 'pending'");
            return;
        }

        if ($action === 'request_extension') {
            $reqDate = $conn->real_escape_string($additionalData['requested_date'] ?? '');
            $reason = $conn->real_escape_string($additionalData['reason'] ?? '');
            $conn->query("INSERT INTO pass_extensions (application_id, requested_validity, reason, status) VALUES ('$app', '$reqDate', '$reason', 'pending')");
            return;
        }

        if ($action === 'approve_extension') {
            $extId = (int)($additionalData['extension_id'] ?? 0);
            $res = $conn->query("SELECT * FROM pass_extensions WHERE id = $extId LIMIT 1");
            if ($row = $res->fetch_assoc()) {
                $newValidTo = $row['requested_validity'];
                $newValidToSql = $conn->real_escape_string($newValidTo);
                $conn->query("UPDATE workmen SET valid_to = '$newValidToSql' WHERE application_no = '$app'");
                $conn->query("UPDATE gate_passes SET valid_to = '$newValidToSql' WHERE application_no = '$app'");
                $conn->query("UPDATE pass_extensions SET status = 'approved', approved_by = " . (int)$_SESSION['user_id'] . " WHERE id = $extId");
                self::queueSapSync($conn, 'WORKMAN', $app, 'PASS_EXTENDED', ['valid_to' => $newValidTo]);
            }
            return;
        }

        if ($action === 'generate_acc') {
            self::ensureSapLogTable($conn);
            $validTo = date('Y-m-d', strtotime('+1 year'));
            $workmen = $conn->query("SELECT w.*, c.contractor_name, c.work_awarding_department 
                                     FROM workmen w 
                                     LEFT JOIN contractors c ON w.contractor_id = c.id
                                     WHERE w.application_no = '$app' AND w.status = 'temporary_issued'");
            while ($workmen && ($row = $workmen->fetch_assoc())) {
                $acc = 'ACC-' . date('Y') . '-' . str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT);
                $accSql = $conn->real_escape_string($acc);
                $validToSql = $conn->real_escape_string($validTo);
                // Update workman to permanent_active directly (ACC generation = permanent pass issued)
                $conn->query("UPDATE workmen SET acc_number = '$accSql', acc_card_number = '$accSql', status = 'permanent_active', valid_from = CURDATE(), valid_to = '$validToSql' WHERE id = " . (int)$row['id']);
                $conn->query("UPDATE gate_passes SET acc_card_number = '$accSql', pass_type = 'permanent', valid_to = '$validToSql', status = 'approved' WHERE application_no = '$app' AND workman_id = " . (int)$row['id']);
                $conn->query("INSERT IGNORE INTO acc_attendance_map (acc_number, worker_id) VALUES ('$accSql', " . (int)$row['id'] . ")");
                // Auto-create permanent_gate_passes row so contractor can see it immediately
                $conn->query("INSERT IGNORE INTO permanent_gate_passes (pass_no, worker_id, application_id, contractor_id, valid_from, valid_till, status)
                    VALUES ('$accSql', " . (int)$row['id'] . ", '$app', " . (int)$row['contractor_id'] . ", CURDATE(), '$validToSql', 'active')");
                // SAP SYNC
                SapDemo::syncWorker($conn, $acc, $row['name'], $row['aadhaar'], $row['contractor_name'], $row['work_awarding_department']);
            }
            self::queueSapSync($conn, 'WORKMAN', $app, 'PERMANENT_PASS_ISSUED', ['valid_to' => $validTo]);
            return;
        }

        if ($action === 'issue_permanent_pass') {
            $validity = $additionalData['perm_validity'] ?? 'contract';
            $validTo = date('Y-m-d', strtotime('+1 year'));
            if ($validity === 'contract') {
                $res = $conn->query("SELECT contract_end FROM annexure2a WHERE application_id = '$app' LIMIT 1");
                $contract = $res ? $res->fetch_assoc() : null;
                if (!empty($contract['contract_end'])) {
                    $validTo = $contract['contract_end'];
                }
            }
            $validToSql = $conn->real_escape_string($validTo);
            $conn->query("UPDATE workmen SET valid_from = CURDATE(), valid_to = '$validToSql', status = 'permanent_active' WHERE application_no = '$app' AND status IN ('acc_generated', 'biometric_completed')");
            $conn->query("UPDATE gate_passes SET pass_type = 'permanent', valid_to = '$validToSql', status = 'approved' WHERE application_no = '$app'");
            self::createPermanentPassRows($conn, $applicationId, $validTo);
            self::queueSapSync($conn, 'WORKMAN', $app, 'PERMANENT_PASS_ISSUED', ['valid_to' => $validTo]);
            return;
        }

        if ($action === 'block_worker') {
            $reason = $conn->real_escape_string($additionalData['reason'] ?? 'Violation');
            $type = $additionalData['block_type'] ?? 'temporary_block';
            $conn->query("UPDATE workmen SET status = '$type' WHERE application_no = '$app'");
            $conn->query("INSERT INTO worker_block_history (workman_id, action, reason, action_by) SELECT id, '$type', '$reason', " . (int)$_SESSION['user_id'] . " FROM workmen WHERE application_no = '$app'");
            
            // SAP SYNC - Update all workers in this application as blocked in SAP simulation
            $res = $conn->query("SELECT acc_number FROM workmen WHERE application_no = '$app'");
            while($w = $res->fetch_assoc()) {
                if($w['acc_number']) SapDemo::updateWorkerStatus($conn, $w['acc_number'], 'BLOCKED');
            }

            self::queueSapSync($conn, 'WORKMAN', $app, 'BLOCKED', ['reason' => $reason, 'type' => $type]);
            return;
        }

        if ($action === 'relieve_worker') {
            $conn->query("UPDATE workmen SET status = 'acc_return_pending' WHERE application_no = '$app'");
            return;
        }

        if ($action === 'return_acc') {
            $accNo = $conn->real_escape_string($additionalData['acc_no'] ?? '');
            $conn->query("UPDATE workmen SET status = 'acc_returned' WHERE acc_number = '$accNo'");
            $conn->query("INSERT INTO acc_return_logs (workman_id, acc_no, received_by) SELECT id, acc_number, " . (int)$_SESSION['user_id'] . " FROM workmen WHERE acc_number = '$accNo'");
            
            // SAP SYNC
            SapDemo::updateWorkerStatus($conn, $accNo, 'RELIEVED');
            
            self::queueSapSync($conn, 'WORKMAN', $app, 'ACC_RETURNED', ['acc_no' => $accNo]);
            return;
        }

        if ($action === 'approve_noc') {
            // SAP SYNC
            SapDemo::log($conn, "NOC Transfer Approved for Application $app", "SUCCESS");
            return;
        }

        if ($action === 'reject_gatepass' || $action === 'request_reupload') {
            $conn->query("UPDATE gate_passes SET status = 'rejected' WHERE application_no = '$app' AND status = 'pending'");
            $conn->query("UPDATE workmen SET status = 'reupload_pending' WHERE application_no = '$app'");
        }
    }

    private static function syncCoreStatus($conn, $applicationId, $status) {
        $stmt = $conn->prepare("UPDATE annexure2a SET workflow_status = ?, updated_at = NOW() WHERE application_id = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $status, $applicationId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE applications SET current_status = ?, updated_at = NOW() WHERE application_no = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $status, $applicationId);
            $stmt->execute();
            $stmt->close();
        }

        self::syncApplicationWorkflow($conn, $applicationId, $status);
    }

    private static function syncApplicationWorkflow($conn, $applicationId, $status) {
        $stmt = $conn->prepare(
            "INSERT INTO application_workflow (application_id, current_stage, overall_status)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE current_stage = VALUES(current_stage), overall_status = VALUES(overall_status), updated_at = NOW()"
        );
        if ($stmt) {
            $stmt->bind_param('sss', $applicationId, $status, $status);
            $stmt->execute();
            $stmt->close();
        }
    }

    private static function insertRemark($conn, $applicationId, $remarks, $action) {
        $actionBy = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'System';
        $stmt = $conn->prepare("INSERT INTO remarks_history (application_id, remark, created_by, action_type) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssss', $applicationId, $remarks, $actionBy, $action);
            $stmt->execute();
            $stmt->close();
        }
    }

    private static function insertWorkflowLog($conn, $applicationId, $oldStatus, $newStatus, $action, $userId, $role, $remarks) {
        $conn->query("CREATE TABLE IF NOT EXISTS workflow_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id VARCHAR(50) NOT NULL,
            from_status VARCHAR(50) DEFAULT NULL,
            to_status VARCHAR(50) NOT NULL,
            action_name VARCHAR(50) DEFAULT NULL,
            action_by_id INT DEFAULT 0,
            action_by_role VARCHAR(50) DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_wl_app (application_id),
            KEY idx_wl_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $conn->prepare("INSERT INTO workflow_logs (application_id, from_status, to_status, action_name, action_by_id, action_by_role, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssssiss', $applicationId, $oldStatus, $newStatus, $action, $userId, $role, $remarks);
            $stmt->execute();
            $stmt->close();
        }
    }

    private static function fetchCurrentStatus($conn, $applicationId) {
        if (is_numeric($applicationId)) {
            $stmt = $conn->prepare("SELECT current_status as workflow_status FROM applications WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $applicationId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) return $row;
            }
        }

        $stmt = $conn->prepare("SELECT workflow_status FROM annexure2a WHERE application_id = ? OR ref_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ss', $applicationId, $applicationId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return $row;
            }
        }

        $stmt = $conn->prepare("SELECT current_status as workflow_status FROM applications WHERE application_no = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $applicationId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) return $row;
        }

        $stmt = $conn->prepare("SELECT status as workflow_status FROM gate_passes WHERE application_no = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $applicationId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) return $row;
        }

        $stmt = $conn->prepare("SELECT status as workflow_status FROM workmen WHERE application_no = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $applicationId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) return $row;
        }

        return null;
    }

    private static function resolveAppId($conn, $id) {
        if (empty($id) || !is_numeric($id)) return $id;
        
        $res = $conn->query("SELECT application_no FROM applications WHERE id = " . (int)$id);
        if ($res && $row = $res->fetch_assoc()) return $row['application_no'];
        
        $res = $conn->query("SELECT application_id FROM annexure2a WHERE id = " . (int)$id);
        if ($res && $row = $res->fetch_assoc()) return $row['application_id'];

        $res = $conn->query("SELECT application_no FROM gate_passes WHERE id = " . (int)$id);
        if ($res && $row = $res->fetch_assoc()) return $row['application_no'];

        $res = $conn->query("SELECT application_no FROM workmen WHERE id = " . (int)$id);
        if ($res && $row = $res->fetch_assoc()) return $row['application_no'];
        
        return $id;
    }

    private static function ensureSapLogTable($conn) {
        $conn->query("CREATE TABLE IF NOT EXISTS sap_integration_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id VARCHAR(50),
            action VARCHAR(50),
            status VARCHAR(30),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function queueSapSync($conn, $type, $id, $action, $payload) {
        $payloadJson = json_encode($payload);
        $stmt = $conn->prepare("INSERT INTO sap_sync_queue (entity_type, entity_id, action, payload) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssss', $type, $id, $action, $payloadJson);
            $stmt->execute();
            $stmt->close();
        }
    }

    private static function createPermanentPassRows($conn, $applicationId, $validTo) {
        $app = $conn->real_escape_string($applicationId);
        $validToSql = $conn->real_escape_string($validTo);
        $rows = $conn->query("SELECT id, contractor_id, acc_number FROM workmen WHERE application_no = '$app' AND status = 'permanent_active'");
        while ($rows && ($w = $rows->fetch_assoc())) {
            $passNo = $w['acc_number'] ?: ('PGP-' . date('Y') . '-' . str_pad((string)$w['id'], 6, '0', STR_PAD_LEFT));
            $passNoSql = $conn->real_escape_string($passNo);
            $conn->query("INSERT IGNORE INTO permanent_gate_passes (pass_no, worker_id, application_id, contractor_id, valid_from, valid_till, status)
                VALUES ('$passNoSql', " . (int)$w['id'] . ", '$app', " . (int)$w['contractor_id'] . ", CURDATE(), '$validToSql', 'active')");
        }
    }

    private static function isInTransaction($conn) {
        $result = @$conn->query("SELECT @@in_transaction AS active");
        if (!$result) {
            return false;
        }
        $row = $result->fetch_assoc();
        return (int)($row['active'] ?? 0) === 1;
    }

    private static function fail(string $appId, string $from, string $to, string $action, string $role, string $message): array {
        self::fileLog($appId, $from, $to, $action, $role, false, $message);
        return ['success' => false, 'old_status' => $from === 'UNKNOWN' ? null : $from, 'new_status' => null, 'message' => $message];
    }

    private static function fileLog($appId, $from, $to, $action, $role, $success, $message) {
        $status = $success ? 'OK' : 'FAIL';
        $line = '[' . date('Y-m-d H:i:s') . "] [$status] app=$appId from=$from to=$to action=$action role=$role | $message\n";
        $logFile = self::getLogFile();
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
?>
