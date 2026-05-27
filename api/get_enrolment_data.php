<?php
/**
 * Unified Enrolment Data API
 * Fetches workmen, supervisors, and representatives
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    $application_id = getApplicationId($input);

    // Fetch from workmen
    $sql_workmen = "
        SELECT 
            w.id, w.name, w.aadhar as aadhaar, w.age, w.temp_id, 
            w.training_status, w.status, w.type as role, 
            c.name as contractor_name 
        FROM workmen w 
        LEFT JOIN contractors c ON w.contractor_id = c.id
    ";
    
    // Fetch from supervisors (separate table)
    $sql_supervisors = "
        SELECT 
            id, name, aadhar as aadhaar, 0 as age, tempId as temp_id, 
            trainingStatus as training_status, status, 'supervisor' as role,
            '' as contractor_name
        FROM supervisors
    ";
    
    // Fetch from representatives (separate table)
    $sql_representatives = "
        SELECT 
            id, name, aadhar as aadhaar, 0 as age, tempId as temp_id, 
            'qualified' as training_status, status, 'representative' as role,
            '' as contractor_name
        FROM representatives
    ";

    // Filter by application_id if provided (for workmen)
    if ($application_id) {
        $sql_workmen .= " WHERE w.application_id = '" . $conn->real_escape_string($application_id) . "'";
    }

    $sql = "($sql_workmen) UNION ALL ($sql_supervisors) UNION ALL ($sql_representatives)";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception($conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    apiSuccess($data);

} catch (Throwable $e) {
    apiError($e->getMessage());
}

