<?php
error_reporting(0); // STRICT: Do not report to stdout
ini_set('display_errors', 0); // STRICT: Do not display errors as HTML
ob_start(); // Start output buffering to prevent random HTML/warnings breaking JSON
session_start();
require_once 'json_error_handler.php'; // Ensures warnings are caught
require_once '../include/config.php';
require_once '../include/helpers.php';

// Clear any accidental whitespace/BOM output up to this point
ob_clean();

header('Content-Type: application/json; charset=utf-8');

try {
    error_log("API HIT: save_annexure3a - " . json_encode($_REQUEST) . " | Session: " . json_encode($_SESSION));

    $input = json_decode(file_get_contents("php://input"), true);

    $application_id = 
        $input['application_id'] ?? 
        $_REQUEST['application_id'] ?? 
        $_SESSION['current_application_id'] ?? 
        null;

    error_log("APPLICATION_ID: " . $application_id);

    if (!$application_id) {
        throw new Exception("No application_id found. Submit Contractor Registration first.");
    }

    // ✅ STRICT VALIDATION: application_id must be a non-empty string
    if (!is_string($application_id) || trim($application_id) === '' || strlen(trim($application_id)) < 3) {
        throw new Exception("Invalid application_id. Submit Contractor Registration first.");
    }
    $application_id = trim($application_id);

    $contractor_id = $_SESSION['contractor_id'] ?? 0;
    if (!$contractor_id) {
        throw new Exception("Contractor session missing. Please log in.");
    }

    $sups = $input['sup_data'] ?? [];
    if (empty($sups)) {
        throw new Exception("At least one supervisor is required");
    }

    // ========== ANNEXURE 5/A: SUPERVISOR LIMIT VALIDATION ==========
    require_once '../include/pass_limit_validator.php';
    try {
        validatePassLimit($conn, (int)$contractor_id, 'Supervisor', count($sups), false);
    } catch (Exception $limitEx) {
        throw new Exception("Supervisor limit check: " . $limitEx->getMessage());
    }
    // ========== END ANNEXURE 5/A VALIDATION ==========

    $ref_id = 'ANN3A-' . date('Ymd') . '-' . rand(1000,9999);
    $amenities = isset($input['amenities']) && is_array($input['amenities']) ? implode(',', $input['amenities']) : ($input['amenities'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO annexure3a (
            contractor_id,
            application_id,
            supervisor_name,
            qualification,
            experience,
            mobile,
            aadhaar,
            amenities,
            ref_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $inserted_count = 0;
    foreach ($sups as $sup) {
        $supervisor_name = $sup['name'] ?? '';
        $qualification   = $sup['qualification'] ?? '';
        $experience      = (int)($sup['experience'] ?? 0);
        $mobile          = $sup['mobile'] ?? '';
        $aadhaar         = $sup['aadhaar'] ?? '';
        
        $stmt->bind_param(
            "isssissss",
            $contractor_id,
            $application_id,
            $supervisor_name,
            $qualification,
            $experience,
            $mobile,
            $aadhaar,
            $amenities,
            $ref_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting supervisor: " . $stmt->error);
        }
        $inserted_count++;
    }

    if (function_exists('jsonErrorFlush')) jsonErrorFlush();
    
    jsonResponse(true, [
        "application_id" => $application_id,
        "ref_id" => $ref_id,
        "sup_count" => $inserted_count
    ]);

} catch (Throwable $e) {
    error_log("Annexure3A FATAL ERROR: " . $e->getMessage());
    jsonResponse(false, [], $e->getMessage());
}
?>


