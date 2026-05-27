<?php
/**
 * RuleEngine for CLMS
 * =====================================================================
 * CANONICAL WORKFLOW (single source of truth):
 *   draft → submitted → under_review → approved → rejected
 *   approved → training_pending → training_completed
 *   training_completed → gatepass_pending → gatepass_approved
 *   gatepass_approved → final_approval_pending → completed
 *
 * Each transition lists: action_name => [allowed_roles]
 * =====================================================================
 */

class RuleEngine {

    /**
     * Map: current_status => [ action => [ new_status, [allowed_roles] ] ]
     */
    private static $transitions = [
        'draft' => [
            'submit' => ['submitted', ['contractor', 'admin']],
        ],
        'submitted' => [
            'verify' => ['under_review', ['welfare_user', 'welfare_admin', 'admin']],
            'reject' => ['rejected', ['welfare_user', 'welfare_admin', 'admin']],
        ],
        'under_review' => [
            'verify_documents' => ['verified', ['welfare_user', 'welfare_admin', 'admin']],
            'request_reupload' => ['reupload_pending', ['welfare_user', 'welfare_admin', 'admin']],
            'reject' => ['rejected', ['welfare_user', 'welfare_admin', 'admin']],
        ],
        'reupload_pending' => [
            'resubmit' => ['reverification_pending', ['contractor', 'admin']],
        ],
        'reverification_pending' => [
            'verify_reupload' => ['verified', ['welfare_user', 'welfare_admin', 'admin']],
            'reject' => ['rejected', ['welfare_user', 'welfare_admin', 'admin']],
        ],
        'verified' => [
            'approve' => ['approved', ['welfare_admin', 'admin']],
            'reject' => ['rejected', ['welfare_admin', 'admin']],
        ],
        'approved' => [
            'enrol_workmen' => ['enrolment_done', ['contractor', 'admin']],
            'complete_training' => ['training_done', ['safety_user', 'admin']],
            'fail_training' => ['training_failed', ['safety_user', 'admin']],
        ],
        'training_failed' => [
            'reschedule_training' => ['enrolment_done', ['safety_user', 'admin']],
        ],
        'training_done' => [
            'request_gatepass' => ['gatepass_requested', ['contractor', 'admin']],
        ],
        'gatepass_requested' => [
            'verify_gatepass' => ['gatepass_verified', ['welfare_user', 'welfare_admin', 'admin']],
            'reject_gatepass' => ['rejected', ['welfare_user', 'welfare_admin', 'admin']],
        ],
        'gatepass_verified' => [
            'issue_temporary_pass' => ['temporary_pass_issued', ['pass_issuer', 'welfare_user', 'welfare_admin', 'admin']],
        ],
        'temporary_pass_issued' => [
            'request_extension' => ['extension_requested', ['contractor', 'admin']],
            'generate_acc' => ['acc_generated', ['welfare_user', 'welfare_admin', 'admin']],
            'relieve_worker' => ['acc_return_pending', ['welfare_user', 'welfare_admin', 'admin']],
            'block_worker' => ['temp_blocked', ['welfare_admin', 'admin']],
        ],
        'extension_requested' => [
            'approve_extension' => ['temporary_pass_issued', ['welfare_admin', 'admin']],
            'reject_extension' => ['temporary_pass_issued', ['welfare_admin', 'admin']],
        ],
        'acc_generated' => [
            'enroll_biometric' => ['biometric_completed', ['welfare_user', 'welfare_admin', 'admin']],
            'issue_permanent_pass' => ['permanent_pass_issued', ['pass_issuer', 'welfare_user', 'welfare_admin', 'admin']],
        ],
        'biometric_completed' => [
            'issue_permanent_pass' => ['permanent_pass_issued', ['pass_issuer', 'welfare_user', 'admin']],
        ],
        'permanent_pass_issued' => [
            'relieve_worker' => ['acc_return_pending', ['welfare_user', 'welfare_admin', 'admin']],
            'request_noc' => ['noc_requested', ['contractor', 'admin']],
            'block_worker' => ['perm_blocked', ['welfare_admin', 'admin']],
        ],
        'noc_requested' => [
            'approve_noc' => ['relieved', ['welfare_admin', 'admin']],
            'reject_noc' => ['permanent_pass_issued', ['welfare_admin', 'admin']],
        ],
        'acc_return_pending' => [
            'return_acc' => ['acc_returned', ['frontline', 'admin']],
        ],
        'acc_returned' => [
            'relieve_complete' => ['relieved', ['welfare_user', 'welfare_admin', 'admin']],
        ],
        'temp_blocked' => [
            'unblock_worker' => ['temporary_pass_issued', ['welfare_admin', 'admin']],
        ],
        'perm_blocked' => [
            'unblock_worker' => ['permanent_pass_issued', ['welfare_admin', 'admin']],
        ],
        'relieved' => [],
        'rejected' => [
            'resubmit' => ['submitted', ['contractor', 'admin']],
        ],
    ];

    /**
     * Attempt a transition: given current status + action, return the new status.
     * Returns null if action is not valid for the current status.
     */
    public static function getNextStatus($currentStatus, $action) {
        $map = isset(self::$transitions[$currentStatus]) ? self::$transitions[$currentStatus] : [];
        return isset($map[$action][0]) ? $map[$action][0] : null;
    }

    /**
     * Check whether a role is allowed to execute this transition.
     */
    public static function canTransition($currentStatus, $action, $userRole) {
        $map = isset(self::$transitions[$currentStatus]) ? self::$transitions[$currentStatus] : [];
        if (!isset($map[$action])) {
            return false;
        }
        $allowedRoles = $map[$action][1];
        return in_array('admin', $allowedRoles, true) // admin bypasses always
            || in_array($userRole, $allowedRoles, true);
    }

    /**
     * Get all valid actions from a given status.
     */
    public static function getValidActions($currentStatus) {
        $transitions = isset(self::$transitions[$currentStatus]) ? self::$transitions[$currentStatus] : [];
        return array_keys($transitions);
    }

    /**
     * Find the action that produces a desired next status from the current status.
     */
    public static function getActionForStatus($currentStatus, $desiredStatus) {
        $transitions = isset(self::$transitions[$currentStatus]) ? self::$transitions[$currentStatus] : [];
        foreach ($transitions as $action => $config) {
            $status = $config[0];
            if ($status === $desiredStatus) {
                return $action;
            }
        }
        return null;
    }

    /**
     * Validate that a status value is a known state.
     */
    public static function isValidStatus($status) {
        return array_key_exists($status, self::$transitions);
    }

    /**
     * Normalize legacy/variant status names to the canonical ones.
     */
    public static function normalizeStatus($status) {
        $legacyMap = [
            'pending'           => 'submitted',
            'forwarded'         => 'submitted',
            'welfare_pending'   => 'submitted',
            'welfare_approved'  => 'approved',
            'acc_approved'      => 'approved',
            'workmen_added'     => 'enrolment_done',
            'training_pending'  => 'enrolment_done',
            'safety_pending'    => 'enrolment_done',
            'safety_completed'  => 'training_done',
            'training_completed'=> 'training_done',
            'pass_requested'    => 'gatepass_requested',
            'gatepass_pending'  => 'gatepass_requested',
            'pass_issued'       => 'temporary_pass_issued',
            'pass_approved'     => 'temporary_pass_issued',
            'temp_pass_generated' => 'temporary_pass_issued',
            'permanent_pass_generated' => 'permanent_pass_issued',
            'compliance_pending' => 'compliance_pending',
            'compliance_verified' => 'compliance_verified',
            'compliance_rejected' => 'rejected',
            'muster_verified' => 'muster_verified',
            'resubmitted'       => 'submitted',
            'training_failed'   => 'training_failed',
            'reupload_pending'  => 'reupload_pending',
            'reverification_pending' => 'reverification_pending',
            'acc_return_pending' => 'acc_return_pending',
            'acc_returned'      => 'acc_returned',
            'temp_blocked'      => 'temp_blocked',
            'perm_blocked'      => 'perm_blocked',
            'extension_requested' => 'extension_requested',
            'noc_requested'     => 'noc_requested',
            'relieved'          => 'relieved',
        ];

        if (!$status) return 'submitted';
        return isset($legacyMap[$status]) ? $legacyMap[$status] : $status;
    }

    /**
     * Worker eligibility helpers (unchanged API).
     */
    public static function isEligibleForTraining($workerTrainingStatus, $appOverallStatus) {
        return in_array($appOverallStatus, ['approved', 'training_pending'], true)
            && in_array($workerTrainingStatus, ['pending', 'failed'], true);
    }

    public static function isEligibleForGatepass($workerTrainingStatus, $appOverallStatus) {
        return in_array($appOverallStatus, ['training_done', 'gatepass_requested'], true)
            && in_array(strtolower($workerTrainingStatus), ['training_passed', 'qualified', 'pass', 'passed', 'completed'], true);
    }

    public static function validateWorkerDetails($aadhaar) {
        return (bool)preg_match('/^\d{12}$/', $aadhaar);
    }
}
?>

