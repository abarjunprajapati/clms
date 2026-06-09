<?php
/**
 * get_application_details.php
 * Returns full application details: annexure2a + annexure3a workers + workmen + workflow
 *
 * GET params:
 *   id   string  required  (application_id from annexure2a)
 */
require_once 'api_helper.php';
require_once '../include/config.php';

$data = getApiInput();
$id = validateApplicationId($data);

try {
    // 1. Fetch main application record with joined contractor data
    $application = db_single($conn,
        "SELECT 
            a.*,
            a.created_at AS submitted_at,
            COALESCE(c.name, a.contractor_name, 'N/A') AS contractor_name,
            COALESCE(c.project_name, a.project_name, a.category_work, 'N/A') AS project_name,
            a.contractor_id AS id_internal,
            COALESCE(c.sap_code, a.contractor_id, 'N/A') AS contractor_id,
            COALESCE(c.pan, 'N/A') AS pan_no,
            COALESCE(c.contract_no, 'N/A') AS contract_no,
            COALESCE(c.work_location, 'N/A') AS work_location,
            CONCAT(COALESCE(c.contract_start, '-'), ' to ', COALESCE(c.contract_end, '-')) AS contract_period,
            c.work_awarding_department,
            c.epf_code,
            c.esi_code,
            c.ecp_number,
            c.skilled_count,
            c.semi_skilled_count,
            c.unskilled_count,
            c.workers_proposed,
            c.mobile,
            c.email,
            c.address,
            c.vendor_code,
            c.vendor_mob2,
            c.epf_account_no,
            c.wage_declaration,
            c.ecp_covered,
            c.ecp_details_json,
            c.license_details_json,
            c.labour_license_appl_no
         FROM annexure2a a 
         LEFT JOIN contractors c ON a.contractor_id = c.id
         WHERE a.application_id = ? 
         LIMIT 1",
        's', [$id]
    );

    if (!$application) {
        apiError('Application not found: ' . $id, 404);
    }

    // 2. Fetch Annexure 3A records (supervisors/representatives)
    $supervisors = db_fetch_all($conn,
        'SELECT * FROM annexure3a WHERE application_id = ? ORDER BY id ASC',
        's', [$id]
    );

    // 3. Fetch enrolled workmen from workmen table
    $workmen = db_fetch_all($conn,
        "SELECT w.*, ti.temp_id AS temp_id_issued, ti.valid_till AS temp_valid_till
         FROM workmen w
         LEFT JOIN temporary_ids ti ON ti.worker_id = w.id AND ti.status = 'active'
         WHERE w.application_id = ?
         ORDER BY w.name ASC",
        's', [$id]
    );

    // 4. Fetch workflow state
    $workflow = db_single($conn,
        'SELECT * FROM application_workflow WHERE application_id = ? LIMIT 1',
        's', [$id]
    );

    // 5. Fetch workflow audit log
    $audit_log = db_fetch_all($conn,
        'SELECT * FROM workflow_logs WHERE application_id = ? ORDER BY created_at ASC LIMIT 50',
        's', [$id]
    );

    // 6. Fetch permanent passes if any
    $permanent_passes = db_fetch_all($conn,
        "SELECT pgp.*, wm.name AS worker_name, wm.trade, wm.aadhar
         FROM permanent_gate_passes pgp
         LEFT JOIN workmen wm ON wm.id = pgp.worker_id
         WHERE pgp.application_id = ? AND pgp.status = 'active'
         ORDER BY pgp.created_at DESC",
        's', [$id]
    );

    // 7. Fetch documents (Both contractor and worker level if applicable)
    $contractor_id_internal = (int)($application['id_internal'] ?? 0);
    $documents = db_fetch_all($conn,
        "SELECT 
            id, 
            doc_type AS doc_name, 
            doc_type AS doc_key,
            file_path, 
            status, 
            uploaded_at 
         FROM contractor_documents 
         WHERE contractor_id = ? 
         ORDER BY uploaded_at DESC",
        'i', [$contractor_id_internal]
    );

    apiSuccess([
        'application'      => $application,
        'workers'          => $supervisors,
        'supervisors'      => $supervisors,
        'workmen'          => $workmen,
        'workflow'         => $workflow,
        'audit_log'        => $audit_log,
        'permanent_passes' => $permanent_passes,
        'documents'        => $documents,
        'workflow_status'  => $application['workflow_status'] ?? $application['status'] ?? 'unknown',
    ]);

} catch (Exception $ex) {
    apiError('Failed to fetch application details: ' . $ex->getMessage(), 500);
}

