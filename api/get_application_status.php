<?php
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';

try {
    $data = getApiInput();
    $application_id = validateApplicationId($data);

    $application = db_single($conn, "SELECT workflow_status, status FROM annexure2a WHERE application_id = ? LIMIT 1", 's', [$application_id]);
    if (!$application) {
        $application = db_single($conn, "SELECT status FROM applications WHERE id = ? LIMIT 1", 's', [$application_id]);
    }

    if (!$application) {
        apiError('Application not found', 404);
    }

    $workflowStatus = $application['workflow_status'] ?? $application['status'] ?? 'draft';

    sendResponse(true, [
        'application_id' => $application_id,
        'workflow_status' => $workflowStatus,
        'steps' => [
            'draft' => true,
            'submitted' => in_array($workflowStatus, ['submitted', 'verified', 'approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'verified' => in_array($workflowStatus, ['verified', 'approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'approved' => in_array($workflowStatus, ['approved', 'enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'enrolment_done' => in_array($workflowStatus, ['enrolment_done', 'training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'training_done' => in_array($workflowStatus, ['training_done', 'gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'gatepass_requested' => in_array($workflowStatus, ['gatepass_requested', 'gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'gatepass_verified' => in_array($workflowStatus, ['gatepass_verified', 'temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'temporary_pass_issued' => in_array($workflowStatus, ['temporary_pass_issued', 'acc_generated', 'permanent_pass_issued'], true),
            'acc_generated' => in_array($workflowStatus, ['acc_generated', 'permanent_pass_issued'], true),
            'permanent_pass_issued' => $workflowStatus === 'permanent_pass_issued'
        ]
    ], 'Application status fetched');
} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}

