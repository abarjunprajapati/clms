<?php
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';

require_role(['contractor']);

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

if (!$input) {
    json_response(false, null, 'Invalid data');
}

// Check if already exists
$existing = db_single($conn, "SELECT id FROM annexure_3a WHERE user_id = ?", "i", [$user_id]);

if ($existing) {
    // Update
    $sql = "UPDATE annexure_3a SET 
            contractor_name = ?, nature_of_work = ?, category_of_work = ?,
            establishment_code = ?, pf_establishment_code = ?, esi_establishment_code = ?,
            address_line1 = ?, address_line2 = ?, state = ?, district = ?, pincode = ?,
            contact_person_name = ?, mobile_number = ?, email = ?,
            license_number = ?, license_issue_date = ?, license_valid_upto = ?,
            max_workmen_allowed = ?, supervisor_count = ?,
            remarks = ?, status = 'pending'
            WHERE user_id = ?";
    
    $params = [
        $input['contractor_name'], $input['nature_of_work'], $input['category_of_work'],
        $input['establishment_code'], $input['pf_establishment_code'], $input['esi_establishment_code'],
        $input['address_line1'], $input['address_line2'], $input['state'], $input['district'], $input['pincode'],
        $input['contact_person_name'], $input['mobile_number'], $input['email'],
        $input['license_number'], $input['license_issue_date'], $input['license_valid_upto'],
        $input['max_workmen_allowed'], $input['supervisor_count'],
        $input['remarks'], $user_id
    ];
    
    if (db_execute($conn, $sql, "sssssssssssssssssiisi", $params)) {
        json_response(true, null, 'Annexure 3A updated successfully');
    } else {
        json_response(false, null, 'Failed to update details');
    }
} else {
    // Insert
    $sql = "INSERT INTO annexure_3a (
            contractor_name, nature_of_work, category_of_work,
            establishment_code, pf_establishment_code, esi_establishment_code,
            address_line1, address_line2, state, district, pincode,
            contact_person_name, mobile_number, email,
            license_number, license_issue_date, license_valid_upto,
            max_workmen_allowed, supervisor_count,
            remarks, user_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $params = [
        $input['contractor_name'], $input['nature_of_work'], $input['category_of_work'],
        $input['establishment_code'], $input['pf_establishment_code'], $input['esi_establishment_code'],
        $input['address_line1'], $input['address_line2'], $input['state'], $input['district'], $input['pincode'],
        $input['contact_person_name'], $input['mobile_number'], $input['email'],
        $input['license_number'], $input['license_issue_date'], $input['license_valid_upto'],
        $input['max_workmen_allowed'], $input['supervisor_count'],
        $input['remarks'], $user_id
    ];
    
    if (db_execute($conn, $sql, "sssssssssssssssssiisi", $params)) {
        json_response(true, null, 'Annexure 3A submitted successfully');
    } else {
        json_response(false, null, 'Failed to submit details');
    }
}

