<?php
/**
 * WorkflowEngine for CLMS
 * Manages application instances and status transitions.
 */
class WorkflowEngine {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function createApplication($type, $contractor_id) {
        $app_no = strtoupper($type[0]) . date('YmdHis') . rand(10, 99);
        $stmt = $this->conn->prepare("INSERT INTO applications (application_no, contractor_id, type, current_status) VALUES (?, ?, ?, 'draft')");
        $stmt->bind_param("sis", $app_no, $contractor_id, $type);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function updateStatus($app_id, $new_status, $reason = null) {
        $stmt = $this->conn->prepare("UPDATE applications SET current_status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $reason, $app_id);
        return $stmt->execute();
    }

    public function getApplication($app_id) {
        $stmt = $this->conn->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->bind_param("i", $app_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function canTransition($type, $current_status, $target_status) {
        $flows = [
            'contractor' => [
                'draft' => ['pending'],
                'pending' => ['approved', 'correction_required', 'hold', 'rejected'],
                'correction_required' => ['pending', 'draft'],
                'hold' => ['approved', 'rejected', 'pending'],
                'approved' => ['blocked', 'expired'],
                'blocked' => ['approved', 'rejected'],
                'expired' => ['pending', 'draft']
            ],
            'worker' => [
                'draft' => ['pending'],
                'pending' => ['training_scheduled', 'rejected', 'correction_required'],
                'training_scheduled' => ['passed', 'failed', 'absent'],
                'passed' => ['gatepass_requested'],
                'failed' => ['pending'], // Re-submission for re-test
                'gatepass_requested' => ['temp_issued', 'rejected'],
                'temp_issued' => ['acc_generated'],
                'acc_generated' => ['permanent_active', 'relieved'],
                'permanent_active' => ['blocked', 'relieved'],
                'blocked' => ['permanent_active']
            ]
        ];

        return in_array($target_status, $flows[$type][$current_status] ?? []);
    }
}

