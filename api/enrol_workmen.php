<?php
/**
 * Enrolment API - Insert multiple Workmen
 * Handles Annexure 4/A form submission
 */
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'json_error_handler.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/workflow_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    // Validate database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Ensure workflow tables exist
    workflow_ensure_tables($conn);

    // Get input data
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!$input) {
        throw new Exception("Invalid JSON input");
    }

    error_log("[ENROLMENT] Input received: " . print_r($input, true));

    // STRICT: contractor_id MUST come from session
    $contractor_id = $_SESSION['contractor_id'] ?? $_SESSION['user_id'] ?? null;
    if (!$contractor_id) {
        throw new Exception("contractor_id missing in session. Please login.");
    }

    // STRICT: application_id from input (primary) or session (fallback only)
    $application_id = $input['application_id'] ?? $_SESSION['current_application_id'] ?? null;
    if (!$application_id) {
        throw new Exception("application_id missing. Submit Contractor Registration first.");
    }

    // Store back to session for consistency
    $_SESSION['current_application_id'] = $application_id;

    error_log("[ENROLMENT] Resolved application_id=$application_id | contractor_id=$contractor_id");

    // Get workmen array
    $workmen = $input['workmen'] ?? [];

    if (empty($workmen)) {
        // Single workman mode
        $workmen = [[
            'name' => $input['name'] ?? '',
            'father_name' => $input['father_name'] ?? '',
            'dob' => $input['dob'] ?? '',
            'gender' => $input['gender'] ?? '',
            'aadhar' => $input['aadhar'] ?? '',
            'phone' => $input['phone'] ?? '',
            'role' => $input['role'] ?? '',
            'address' => $input['address'] ?? '',
            'state' => $input['state'] ?? '',
            'type' => $input['type'] ?? 'workman'
        ]];
    }

    // ========== ANNEXURE 5/A VALIDATION ==========
    require_once __DIR__ . '/../include/pass_limit_validator.php';
    
    // Group by type and count
    $type_counts = array_count_values(array_column($workmen, 'type'));
    
    foreach ($type_counts as $type => $count) {
        if ($type === 'workman') continue; // Unlimited
        
        try {
            validatePassLimit($conn, $contractor_id, ucfirst($type), $count);
            error_log("[ANN5A] Validated $count $type for contractor $contractor_id");
        } catch (Exception $e) {
            throw new Exception("Annexure 5/A: " . $e->getMessage());
        }
    }
    // ============================================

    // Validate required fields for at least one workman
    if (empty($workmen[0]['name']) || empty($workmen[0]['aadhar'])) {
        throw new Exception("Name and Aadhaar are required for at least one workman");
    }

    // Insert each workman
    $inserted_ids = [];
    $errors = [];

    foreach ($workmen as $index => $w) {
        $name = trim($w['name'] ?? '');
        $father_name = trim($w['father_name'] ?? '');
        $dob = trim((string)($w['dob'] ?? '')) !== '' ? $w['dob'] : null;
        $gender = $w['gender'] ?? 'Male';
        $aadhar = trim($w['aadhar'] ?? '');
        $phone = trim($w['phone'] ?? '');
        $role = trim($w['role'] ?? 'Helper');
        $address = trim($w['address'] ?? '');
        $state = trim($w['state'] ?? '');
        $type = $w['type'] ?? 'workman';

        if (empty($name) || empty($aadhar)) {
            $errors[] = "Row " . ($index + 1) . ": Name and Aadhaar required";
            continue;
        }

        // STEP 1: INSERT without temp_id (NULL) to get insert_id
        $sql = "INSERT INTO workmen (
            application_no, contractor_id, name, father_name,
            dob, gender, aadhaar, mobile, trade, permanent_address, state,
            worker_type, status, training_status, safety_training_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 'PENDING_TRAINING', NOW())";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "Row " . ($index + 1) . ": Prepare failed - " . $conn->error;
            continue;
        }

        $stmt->bind_param(
            "sissssssssss",
            $application_id,
            $contractor_id,
            $name,
            $father_name,
            $dob,
            $gender,
            $aadhar,
            $phone,
            $role,
            $address,
            $state,
            $type
        );

        if (!$stmt->execute()) {
            $errors[] = "Row " . ($index + 1) . ": Insert failed - " . $stmt->error;
            $stmt->close();
            continue;
        }

        // STEP 2: GET LAST INSERT ID
        $insert_id = $conn->insert_id;
        if (!$insert_id) {
            $errors[] = "Row " . ($index + 1) . ": Failed to get insert_id";
            $stmt->close();
            continue;
        }

        // STEP 3: GENERATE temp_id from insert_id
        $temp_id = "TEMP-" . str_pad($insert_id, 6, "0", STR_PAD_LEFT);

        // STEP 4: UPDATE temp_id
        $update_sql = "UPDATE workmen SET temp_id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            $errors[] = "Row " . ($index + 1) . ": Update prepare failed - " . $conn->error;
            $stmt->close();
            continue;
        }
        $update_stmt->bind_param("si", $temp_id, $insert_id);
        if (!$update_stmt->execute()) {
            $errors[] = "Row " . ($index + 1) . ": Update IDs failed - " . $update_stmt->error;
            $update_stmt->close();
            $stmt->close();
            continue;
        }
        $update_stmt->close();
        $stmt->close();

        $inserted_ids[] = [
            'db_id'      => $insert_id,
            'workman_id' => $workman_id,
            'temp_id'    => $temp_id,
            'name'       => $name
        ];
        error_log("[ENROLMENT] Inserted id=$insert_id | workman_id=$workman_id | temp_id=$temp_id | name=$name");
    }

    // Update workflow if any workmen inserted
    if (!empty($inserted_ids)) {
        // Update or create workflow record
        $workflow_sql = "INSERT INTO application_workflow 
            (application_id, contractor_id, current_stage, welfare_status, overall_status, updated_at)
            VALUES (?, ?, 'enrolment', 'approved', 'pending', NOW())
            ON DUPLICATE KEY UPDATE 
                current_stage = 'enrolment',
                welfare_status = 'approved',
                updated_at = NOW()";

        $wf_stmt = $conn->prepare($workflow_sql);
        $wf_stmt->bind_param("si", $application_id, $contractor_id);
        $wf_stmt->execute();
        $wf_stmt->close();

        error_log("[ENROLMENT] Workflow updated for $application_id to enrolment stage");
    }

    // Build response
    if (!empty($inserted_ids)) {
        $response['success'] = true;
        $response['message'] = count($inserted_ids) . " Workmen enrolled successfully";
        $response['data'] = [
            'application_id' => $application_id,
            'workmen' => $inserted_ids,
            'total_inserted' => count($inserted_ids)
        ];
    } else {
        $response['message'] = "No workmen inserted. " . implode("; ", $errors);
    }

    if (!empty($errors) && empty($inserted_ids)) {
        $response['success'] = false;
    } elseif (!empty($errors)) {
        $response['message'] .= " | Warnings: " . implode("; ", $errors);
    }

    jsonErrorFlush();
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    jsonErrorFlush();
    error_log("[ENROLMENT] Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}


