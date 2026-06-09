
<?php
/**
 * generate_permanent_pass.php
 * Standardized for CLMS
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/workflow_action.php';

try {
    $data = getApiInput();
    $application_id = validateApplicationId($data);

    // Verify application is at ACC-generated stage
    $application = db_single($conn,
        "SELECT id, application_id, contractor_id, contractor_name, workflow_status, labour_validity
         FROM annexure2a WHERE application_id = ? LIMIT 1",
        's', [$application_id]
    );

    if (!$application) {
        apiError("Application '$application_id' not found", 404);
    }

    if (!in_array($application['workflow_status'], ['acc_approved', 'pass_generated'], true)) {
        apiError("Permanent pass requires acc_approved workflow status. Current: " . $application['workflow_status'], 400);
    }

    // Get all workmen linked to this application
    $workers = db_fetch_all($conn,
        "SELECT id, name, trade, aadhar, phone, photo, application_id
         FROM workmen WHERE application_id = ? AND status = 'active'",
        's', [$application_id]
    );

    if (empty($workers)) {
        apiError('No active workmen found for this application', 404);
    }

    // ========== ANNEXURE 5/A: PASS LIMIT VALIDATION ==========
    require_once __DIR__ . '/../include/pass_limit_validator.php';
    $cid = (int)($application['contractor_id'] ?? 0);
    if ($cid) {
        try {
            validatePassLimit($conn, $cid, 'Workman', count($workers), false);
        } catch (Exception $limitEx) {
            apiError("Annexure 5/A: " . $limitEx->getMessage(), 400);
        }
    }
    // ========== END ANNEXURE 5/A VALIDATION ==========

    $year       = date('Y');
    $issue_date = date('Y-m-d');
    $valid_till_default = date('Y-m-d', strtotime('+1 year'));
    $valid_till = (!empty($application['labour_validity']) && $application['labour_validity'] < $valid_till_default)
        ? $application['labour_validity']
        : $valid_till_default;

    $generated  = [];
    $skipped    = [];

    foreach ($workers as $worker) {
        $wid = (int)$worker['id'];

        $existing = db_single($conn,
            "SELECT id, pass_no FROM permanent_gate_passes WHERE worker_id = ? AND application_id = ? AND status = 'active' LIMIT 1",
            'is', [$wid, $application_id]
        );

        if ($existing) {
            $skipped[] = ['worker_id' => $wid, 'name' => $worker['name'], 'pass_no' => $existing['pass_no']];
            continue;
        }

        $last = db_single($conn,
            "SELECT MAX(CAST(SUBSTRING_INDEX(pass_no, '-', -1) AS UNSIGNED)) as max_num
             FROM permanent_gate_passes WHERE pass_no LIKE ?",
            's', ["GP-A-{$year}-%"]
        );
        $nextNum = ($last['max_num'] ?? 0) + 1;
        $pass_no = sprintf('GP-A-%s-%05d', $year, $nextNum);

        $qr_data = base64_encode(json_encode([
            'type'           => 'permanent_pass',
            'pass_no'        => $pass_no,
            'worker_id'      => $wid,
            'worker_name'    => $worker['name'],
            'application_id' => $application_id,
            'valid_till'     => $valid_till
        ]));

        db_execute($conn,
            "INSERT INTO permanent_gate_passes
             (pass_no, worker_id, application_id, valid_from, valid_till, qr_code, status)
             VALUES (?, ?, ?, ?, ?, ?, 'active')",
            'sissss',
            [$pass_no, $wid, $application_id, $issue_date, $valid_till, $qr_data]
        );

        // Update workman workflow status
        db_execute($conn,
            "UPDATE workmen SET workflow_status = 'pass_issued', pass_status = 'issued' WHERE id = ?",
            'i', [$wid]
        );

        $generated[] = [
            'worker_id'   => $wid,
            'name'        => $worker['name'],
            'pass_no'     => $pass_no,
            'status'      => 'generated'
        ];
    }

    if (count($generated) > 0) {
        workflowSetStatus($application_id, 'pass_generated', count($generated) . ' permanent gate passes generated');
    }

    apiSuccess([
        'application_id' => $application_id,
        'generated' => $generated,
        'skipped' => $skipped,
        'generated_count' => count($generated)
    ]);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}

