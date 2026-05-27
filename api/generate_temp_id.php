<?php
/**
 * generate_temp_id.php
 * Generates temporary IDs for workmen.
 * Stores in `temporary_ids` table and also updates workmen.temp_id column.
 *
 * POST body (JSON):
 *   worker_ids   array  required   e.g. [1, 2, 3]
 *   valid_days   int    optional   default 270 (~9 months)
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/auth_middleware.php';
require_once __DIR__ . '/workflow_action.php';

require_role(['contractor', 'welfare', 'admin', 'pass_officer']);

header('Content-Type: application/json');

$input      = json_decode(file_get_contents('php://input'), true);
$worker_ids = $input['worker_ids'] ?? [];
$valid_days = (int)($input['valid_days'] ?? 270);

if (empty($worker_ids) || !is_array($worker_ids)) {
    echo json_encode(['success' => false, 'error' => 'worker_ids array is required']);
    exit;
}

// Sanitize to integers
$worker_ids = array_filter(array_map('intval', $worker_ids));
if (empty($worker_ids)) {
    echo json_encode(['success' => false, 'error' => 'No valid worker IDs provided']);
    exit;
}

// Security: contractor can only generate for own workers
if ($currentRole === 'contractor') {
    $placeholders = implode(',', array_fill(0, count($worker_ids), '?'));
    $checkTypes   = str_repeat('i', count($worker_ids)) . 'i';
    $checkParams  = array_merge(array_values($worker_ids), [$currentUserId]);
    $allowed      = db_fetch_all($conn,
        "SELECT id FROM workmen WHERE id IN ($placeholders) AND contractor_id = ?",
        $checkTypes, $checkParams
    );
    $worker_ids = array_column($allowed, 'id');
    if (empty($worker_ids)) {
        echo json_encode(['success' => false, 'error' => 'No valid workers found for your contractor account']);
        exit;
    }
}

$results    = [];
$issue_date = date('Y-m-d');
$valid_till = date('Y-m-d', strtotime("+{$valid_days} days"));
$year       = date('Y');
$applicationIds = [];

foreach ($worker_ids as $wid) {
    $wid = (int)$wid;

    // Verify worker exists in workmen table
    $worker = db_single($conn, 'SELECT id, name, application_id FROM workmen WHERE id = ? LIMIT 1', 'i', [$wid]);
    if (!$worker) {
        $results[] = ['worker_id' => $wid, 'status' => 'not_found', 'error' => 'Worker not found in workmen table'];
        continue;
    }
    $applicationId = $worker['application_id'] ?? '';
    if ($applicationId === '') {
        $results[] = ['worker_id' => $wid, 'status' => 'failed', 'error' => 'Worker is not linked to an application'];
        continue;
    }
    $application = workflowGetApplication($applicationId);
    if (!$application) {
        $results[] = ['worker_id' => $wid, 'status' => 'failed', 'error' => 'Linked application not found'];
        continue;
    }
    $currentWorkflow = $application['workflow_status'] ?? 'submitted';
    if (!in_array($currentWorkflow, ['pass_approved', 'temp_pass_generated', 'acc_generated', 'permanent_pass_generated'], true)) {
        $results[] = [
            'worker_id' => $wid,
            'status' => 'blocked',
            'error' => "Temporary pass requires pass_approved workflow status. Current: $currentWorkflow"
        ];
        continue;
    }

    // Check if active temp ID already exists
    $existing = db_single($conn,
        "SELECT id, temp_id, valid_till FROM temporary_ids WHERE worker_id = ? AND status = 'active' LIMIT 1",
        'i', [$wid]
    );

    if ($existing) {
        $results[] = [
            'worker_id'  => $wid,
            'temp_id'    => $existing['temp_id'],
            'valid_till' => $existing['valid_till'],
            'status'     => 'already_exists'
        ];
        continue;
    }

    // Generate unique temp ID: TMP-YYYY-XXXXXX
    $last    = db_single($conn,
        "SELECT MAX(CAST(SUBSTRING_INDEX(temp_id, '-', -1) AS UNSIGNED)) as max_num
         FROM temporary_ids WHERE temp_id LIKE ?",
        's', ["TMP-{$year}-%"]
    );
    $nextNum = ($last['max_num'] ?? 0) + 1;
    $temp_id = sprintf('TMP-%s-%05d', $year, $nextNum);

    // QR payload
    $qr_data = base64_encode(json_encode([
        'type'       => 'temp_id',
        'temp_id'    => $temp_id,
        'worker_id'  => $wid,
        'valid_till' => $valid_till
    ]));

    try {
        // Insert into temporary_ids table
        db_execute($conn,
            "INSERT INTO temporary_ids (worker_id, temp_id, issue_date, valid_till, qr_code, status)
             VALUES (?, ?, ?, ?, ?, 'active')",
            'issss', [$wid, $temp_id, $issue_date, $valid_till, $qr_data]
        );

        // Also update workmen.temp_id for quick reference
        db_execute($conn,
            'UPDATE workmen SET temp_id = ? WHERE id = ?',
            'si', [$temp_id, $wid]
        );

        $results[] = [
            'worker_id'   => $wid,
            'worker_name' => $worker['name'],
            'temp_id'     => $temp_id,
            'issue_date'  => $issue_date,
            'valid_till'  => $valid_till,
            'qr_code'     => $qr_data,
            'status'      => 'generated'
        ];
        $applicationIds[$applicationId] = true;
    } catch (Exception $e) {
        $results[] = ['worker_id' => $wid, 'status' => 'failed', 'error' => $e->getMessage()];
    }
}

$generated_count = count(array_filter($results, function($r) { return $r['status'] === 'generated'; }));

foreach (array_keys($applicationIds) as $applicationId) {
    try {
        workflowSetStatus($applicationId, 'temp_pass_generated', 'Temporary IDs generated');
    } catch (Throwable $e) {
        error_log('[generate_temp_id] workflow update failed: ' . $e->getMessage());
    }
}

echo json_encode([
    'success'         => true,
    'message'         => $generated_count . ' temporary IDs generated successfully',
    'data'            => ['results' => $results],
    'results'         => $results,
    'generated_count' => $generated_count,
    'total_requested' => count($worker_ids)
]);

