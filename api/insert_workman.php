<?php
session_start();
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/WorkflowEngine.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = getApiInput();

    // application_no is stored on the worker record
    $application_no = trim($input['application_id'] ?? $_SESSION['current_application_id'] ?? '');
    if (!$application_no) {
        throw new Exception("application_id is required. Complete Contractor Registration first.");
    }

    $name            = trim($input['name'] ?? '');
    $father_name     = trim($input['father_name'] ?? '');
    $dob             = trim((string)($input['dob'] ?? '')) !== '' ? $input['dob'] : null;
    $gender          = trim($input['gender'] ?? 'Male');
    $marital_status  = trim($input['marital_status'] ?? '');
    $aadhaar         = trim($input['aadhaar'] ?? '');
    $esic_number     = trim($input['esic'] ?? '');
    $uan_number      = trim($input['uan'] ?? '');
    $mobile          = trim($input['contact'] ?? '');
    $acc_number      = trim($input['bank_account'] ?? '');
    $ifsc            = trim($input['bank_ifsc'] ?? '');
    $trade           = trim($input['trade'] ?? '');
    $skill           = trim($input['skill'] ?? '');
    $department      = trim($input['department'] ?? '');
    $nature_of_work  = trim($input['nature_of_work'] ?? '');
    $work_location   = trim($input['work_location'] ?? '');
    $wage_rate       = is_numeric($input['basic_wage'] ?? null) ? floatval($input['basic_wage']) : null;
    $wage_type       = strtolower(trim($input['wage_type'] ?? 'daily'));
    $wage_type       = in_array($wage_type, ['daily', 'weekly', 'monthly'], true) ? $wage_type : 'daily';
    $allowance       = is_numeric($input['allowance'] ?? null) ? floatval($input['allowance']) : 0.00;
    $present_address = trim($input['present_address'] ?? '');
    $permanent_address = trim($input['permanent_address'] ?? '');
    $state           = trim($input['state'] ?? '');
    $district        = trim($input['district'] ?? '');
    $photo           = ''; // File uploads not implemented yet

    if (empty($name) || empty($aadhaar)) {
        throw new Exception("Name and Aadhaar are required to enrol a workman.");
    }

    $contractor_id = $_SESSION['contractor_id'] ?? null;
    if (!$contractor_id) {
        throw new Exception("contractor_id missing in session. Please login as a contractor.");
    }

    $sql = "INSERT INTO workmen (
        application_no,
        contractor_id,
        name,
        father_name,
        dob,
        gender,
        marital_status,
        aadhaar,
        esic_number,
        uan_number,
        mobile,
        acc_number,
        ifsc,
        department,
        nature_of_work,
        work_location,
        wage_rate,
        wage_type,
        allowance,
        present_address,
        permanent_address,
        state,
        district,
        trade,
        skill,
        photo,
        status,
        training_status,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $bindTypes = 'sisssssssssssssdssssssssss';
    $stmt->bind_param(
        $bindTypes,
        $application_no,
        $contractor_id,
        $name,
        $father_name,
        $dob,
        $gender,
        $marital_status,
        $aadhaar,
        $esic_number,
        $uan_number,
        $mobile,
        $acc_number,
        $ifsc,
        $department,
        $nature_of_work,
        $work_location,
        $wage_rate,
        $wage_type,
        $allowance,
        $present_address,
        $permanent_address,
        $state,
        $district,
        $trade,
        $skill,
        $photo
    );

    if (!$stmt->execute()) {
        throw new Exception("Insert failed: " . $stmt->error);
    }

    $insert_id = $stmt->insert_id;
    $stmt->close();

    $temp_id = 'TMP-' . str_pad($insert_id, 5, '0', STR_PAD_LEFT);
    $updateStmt = $conn->prepare("UPDATE workmen SET temp_id = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Temp ID update failed: " . $conn->error);
    }
    $updateStmt->bind_param('si', $temp_id, $insert_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Temp ID update failed: " . $updateStmt->error);
    }
    $updateStmt->close();

    WorkflowEngine::performAction($conn, $application_no, 'enrol_workmen', 'contractor', $_SESSION['user_id'] ?? 0, 'Added workman');

    apiSuccess([
        'id' => $insert_id,
        'name' => $name,
        'temp_id' => $temp_id,
        'application_no' => $application_no
    ], "Workman enrolled successfully");

} catch (Throwable $e) {
    apiError($e->getMessage(), 400);
}
?>

