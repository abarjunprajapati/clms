<?php
/**
 * Get Representatives API
 * Merges data from annexure3a (Proposal) and workmen table (Enrolment)
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

session_start();

try {
    $input = getApiInput();
    $application_id = $input['application_id'] ?? $_SESSION['current_application_id'] ?? '';

    if ($application_id === '') {
        sendResponse(true, [], "Success");
    }

    // Query both tables to find all representatives
    $sql = "
        (SELECT 
            id, application_id, representative_name AS name, 
            designation AS role, designation, '' as email,
            aadhaar AS aadhar, mobile AS phone, ref_id, 
            created_at, 'representative' AS type, 'active' AS status,
            'Partial' as authority
        FROM annexure3a 
        WHERE application_id = ? AND representative_name IS NOT NULL AND representative_name != '')
        
        UNION ALL
        
        (SELECT 
            id, application_id, name, 
            worker_type as role, worker_type as designation, '' as email,
            aadhaar as aadhar, mobile as phone, id as ref_id, 
            created_at, 'representative' AS type, status,
            'Full' as authority
        FROM workmen 
        WHERE application_id = ? AND worker_type = 'representative')
        
        ORDER BY created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $application_id, $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    sendResponse(true, $data, "Success");

} catch (Throwable $e) {
    sendResponse(false, [], "Error: " . $e->getMessage());
}
?>

