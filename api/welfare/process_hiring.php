<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
// Allow welfare_admin and welfare_user, plus pass_user
if (isset($_SESSION['role'])) {
    $_SESSION['role'] = strtolower(trim($_SESSION['role']));
}
checkAuth(['welfare_admin', 'welfare_user', 'pass_user']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['workmen']) || !is_array($data['workmen'])) {
    echo json_encode(['success' => false, 'message' => 'No workmen data provided.']);
    exit;
}

$successCount = 0;
$errors = [];

mysqli_begin_transaction($conn);

/**
 * MOCK SAP GET API: Check if workman is already in SAP database
 */
function sap_get_acc_number($aadhaar) {
    // Placeholder URL for SAP API GET endpoint
    $sap_url = "https://sap.teleconsystems.com/api/get_workman_acc?aadhaar=" . urlencode($aadhaar);
    
    /* 
    // Real implementation using cURL:
    $ch = curl_init($sap_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Add headers, auth tokens as required by SAP
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if(isset($data['acc_number'])) return $data['acc_number'];
    */
    
    // For now, return null to simulate workman not existing in SAP yet
    return null;
}

/**
 * MOCK SAP POST API: Send details to SAP to create and return ACC number
 */
function sap_create_acc_number($workman_data) {
    // Placeholder URL for SAP API POST endpoint
    $sap_url = "https://sap.teleconsystems.com/api/create_workman";
    
    /*
    // Real implementation using cURL:
    $ch = curl_init($sap_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($workman_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if(isset($data['acc_number'])) return $data['acc_number'];
    */
    
    // Mock generating a new ACC number from SAP
    return 'ACC' . date('Y') . str_pad($workman_data['id'], 5, '0', STR_PAD_LEFT);
}

try {
    foreach ($data['workmen'] as $w) {
        $id = intval($w['id']);
        $pass_from = mysqli_real_escape_string($conn, $w['pass_from']);
        $pass_to = mysqli_real_escape_string($conn, $w['pass_to']);
        
        // Fetch Workman Aadhar and details for SAP
        $res = mysqli_query($conn, "SELECT id, aadhaar, name FROM workmen WHERE id = $id");
        $workman_db = mysqli_fetch_assoc($res);
        if (!$workman_db) {
            $errors[] = "Workman ID $id not found.";
            continue;
        }
        
        $aadhaar = $workman_db['aadhaar'];
        
        // Step 1: GET API - Check if already in SAP database
        $acc_number = sap_get_acc_number($aadhaar);
        
        // Step 2: POST API - If not in SAP, create it
        if (!$acc_number) {
            $acc_number = sap_create_acc_number($workman_db);
        }
        
        if (!$acc_number) {
            $errors[] = "Failed to get/generate ACC from SAP for Workman ID $id.";
            continue;
        }
        
        // Update query
        $sql = "UPDATE workmen SET 
                    acc_number = '$acc_number',
                    valid_from = '$pass_from',
                    valid_to = '$pass_to',
                    status = 'acc_generated',
                    updated_at = NOW()
                WHERE id = $id";
                
        if (mysqli_query($conn, $sql)) {
            $successCount++;
        } else {
            $errors[] = "Failed to update workman ID $id: " . mysqli_error($conn);
        }
    }

    if (count($errors) > 0) {
        throw new Exception("Errors occurred during update.");
    }

    mysqli_commit($conn);
    echo json_encode([
        'success' => true, 
        'message' => "Successfully processed hiring and generated ACC numbers for $successCount workmen."
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'errors' => $errors
    ]);
}
