<?php
/**
 * RuleEngine for CLMS Workflow
 * Enforces strict transition rules and status-based validation.
 */

class RuleEngine {
    
    // Status Flow: draft -> submitted -> approved -> enrolment_done -> training_done -> gatepass_requested -> gatepass_verified -> temporary_pass_issued -> acc_generated -> permanent_pass_issued

    public static function canTransition($currentStatus, $nextStatus) {
        $flow = [
            'draft' => 'submitted',
            'submitted' => 'approved',
            'approved' => 'enrolment_done',
            'enrolment_done' => 'training_done',
            'training_done' => 'gatepass_requested',
            'gatepass_requested' => 'gatepass_verified',
            'gatepass_verified' => 'temporary_pass_issued',
            'temporary_pass_issued' => 'acc_generated',
            'acc_generated' => 'permanent_pass_issued'
        ];

        return isset($flow[$currentStatus]) && $flow[$currentStatus] === $nextStatus;
    }

    public static function validateAction($application_id, $action, $conn) {
        // Fetch current application data
        $stmt = clms_db_prepare($conn, "SELECT current_status FROM workflow_status WHERE application_id = ?");
        clms_db_stmt_bind_param($stmt, "s", $application_id);
        clms_db_stmt_execute($stmt);
        $result = clms_db_stmt_get_result($stmt);
        $statusRow = clms_db_fetch_assoc($result);
        $currentStatus = $statusRow['current_status'] ?? 'draft';

        switch ($action) {
            case 'apply_gate_pass':
                // RULE 1: No Training -> No Gate Pass
                $trainingStmt = clms_db_prepare($conn, "SELECT result FROM training_results WHERE application_id = ?");
                clms_db_stmt_bind_param($trainingStmt, "s", $application_id);
                clms_db_stmt_execute($trainingStmt);
                $trainingRes = clms_db_stmt_get_result($trainingStmt);
                $trainingRow = clms_db_fetch_assoc($trainingRes);
                if (!$trainingRow || $trainingRow['result'] !== 'pass') {
                    return ["success" => false, "error" => "Training required before Gate Pass application"];
                }
                break;

            case 'next_step':
                // RULE 2: No Approval -> No Next Step
                if ($currentStatus === 'submitted') {
                     // Check if approved by welfare_admin
                     // This is just a placeholder logic, actual check would involve 'approvals' table
                }
                break;

            case 'verify_documents':
                // RULE 3: No Documents -> Reject
                $docStmt = clms_db_prepare($conn, "SELECT COUNT(*) as count FROM documents WHERE application_id = ?");
                clms_db_stmt_bind_param($docStmt, "s", $application_id);
                clms_db_stmt_execute($docStmt);
                $docRes = clms_db_stmt_get_result($docStmt);
                $docRow = clms_db_fetch_assoc($docRes);
                if ($docRow['count'] == 0) {
                    return ["success" => false, "error" => "Documents missing. Rejection required."];
                }
                break;
        }

        return ["success" => true];
    }

    public static function updateStatus($application_id, $newStatus, $conn) {
        $stmt = clms_db_prepare($conn, "INSERT INTO workflow_status (application_id, current_status) 
                                      VALUES (?, ?) 
                                      ON DUPLICATE KEY UPDATE current_status = ?, updated_at = CURRENT_TIMESTAMP");
        clms_db_stmt_bind_param($stmt, "sss", $application_id, $newStatus, $newStatus);
        return clms_db_stmt_execute($stmt);
    }
}

