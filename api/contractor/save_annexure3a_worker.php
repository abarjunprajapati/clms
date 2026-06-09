<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests are allowed.");
    }

    $data = $_POST;
    $files = $_FILES;

    if (empty($data['name']) || empty($data['aadhaar'])) {
        throw new Exception("Name and Aadhaar Number are mandatory.");
    }

    $contractor_id = (int)($data['contractor_id'] ?? 0);
    $worker_type = $data['worker_type'] ?? 'workmen';

    // ========== LIMIT VALIDATION ==========
    if ($worker_type === 'representative') {
        $count = db_scalar($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND worker_type = 'representative' AND status != 'inactive'", 'i', [$contractor_id]);
        if ($count >= 2) throw new Exception("Maximum 2 Representatives allowed per contractor.");
    } elseif ($worker_type === 'supervisor') {
        $workmen_count = db_scalar($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND worker_type = 'workmen' AND status != 'inactive'", 'i', [$contractor_id]);
        $supervisor_count = db_scalar($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND worker_type = 'supervisor' AND status != 'inactive'", 'i', [$contractor_id]);
        $allowed = ceil($workmen_count / 50);
        if ($allowed == 0) $allowed = 1; // At least 1 supervisor if there are any workers
        if ($supervisor_count >= $allowed && $workmen_count > 0) {
             throw new Exception("Supervisor limit reached (1 per 50 workmen). Current: $supervisor_count, Allowed: $allowed based on $workmen_count workmen.");
        }
    }

    // ========== FILE UPLOADS ==========
    $upload_dir = '../../uploads/workers/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $uploaded_paths = [];
    $doc_fields = [
        'photo', 'aadhaar_doc', 'medical_fitness_file', 'police_clearance_file', 
        'insurance_policy_file', 'qualification_file', 'experience_file'
    ];

    foreach ($doc_fields as $field) {
        $uploaded_paths[$field] = '';
        if (isset($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($files[$field]['name'], PATHINFO_EXTENSION);
            $filename = $field . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($files[$field]['tmp_name'], $upload_dir . $filename)) {
                $uploaded_paths[$field] = $filename;
            }
        }
    }

    // ========== DB INSERT ==========
    $sql = "INSERT INTO workmen (
        contractor_id, worker_type, application_no, name, gender, mobile, email, 
        aadhaar, present_address, permanent_address, state, district, pincode, 
        region, nationality, blood_group, qualification, experience, 
        nature_of_work, daily_wage_rate, esic_number, uan_number, 
        bank_account_number, ifsc_code, safety_training_attended, 
        safety_training_date, photo, aadhaar_doc, medical_doc, 
        police_doc, insurance_doc, qualification_file, experience_file,
        status, training_status, source
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending', 'pending', ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param(
        "issssssssssssssssssdssssisssssss",
        $contractor_id, $worker_type, $data['work_order_no'], $data['name'], $data['gender'], $data['mobile'], $data['email'],
        $data['aadhaar'], $data['present_address'], $data['permanent_address'], $data['state'], $data['district'], $data['pincode'],
        $data['region'], $data['nationality'], $data['blood_group'], $data['qualification'], $data['experience'],
        $data['nature_of_work'], $data['daily_wage_rate'], $data['esic_number'], $data['uan_number'],
        $data['bank_account_number'], $data['ifsc_code'], $data['safety_training_attended'],
        $data['safety_training_date'], $uploaded_paths['photo'], $uploaded_paths['aadhaar_doc'], 
        $uploaded_paths['medical_fitness_file'], $uploaded_paths['police_clearance_file'], 
        $uploaded_paths['insurance_policy_file'], $uploaded_paths['qualification_file'], $uploaded_paths['experience_file'],
        $data['source']
    );

    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $worker_id = $stmt->insert_id;
    $temp_id = "TEMP-" . str_pad($worker_id, 6, "0", STR_PAD_LEFT);
    
    db_execute($conn, "UPDATE workmen SET temp_id = ? WHERE id = ?", 'si', [$temp_id, $worker_id]);

    echo json_encode([
        "success" => true,
        "message" => "Registration successful.",
        "worker_id" => $worker_id,
        "temp_id" => $temp_id
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
