<?php
include __DIR__ . "/../include/config.php";

$edit_id = 1;
$vendor_code = '1100908';
$pin_code = '';
$is_epf_registered = 0;
$epf_code = '';
$is_esi_registered = 1;
$esi_code = '56464';
$work_order_no = 'WO-2026-27';
$insurance_policy_name = 'test';
$insurance_policy_no = '345678324';
$insurance_validity = '2026-05-27';
$insurance_workers_count = 10;
$labour_license_no = '98765433444';
$labour_license_issued_by = 'kuldeep';
$labour_license_issue_date = '2026-05-24';
$labour_license_expiry_date = '2026-07-11';
$wage_declaration = 'test';
$salary_category = 'Unskilled';
$user_id = 1; // dummy user id

$sql = "UPDATE contractor_annexure3a SET 
    pin_code = ?, is_epf_registered = ?, epf_code = ?, 
    is_esi_registered = ?, esi_code = ?, work_order_no = ?, 
    insurance_policy_name = ?, insurance_policy_no = ?, insurance_validity = ?, insurance_workers_count = ?,
    labour_license_no = ?, labour_license_issued_by = ?, labour_license_issue_date = ?, labour_license_expiry_date = ?,
    wage_declaration = ?, salary_category = ?, updated_by = ?, updated_at = NOW(), status = 'pending'
    WHERE id = ? AND vendor_code = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error . "\n");
}

$type_string = "sisisssssissssssiis";
echo "Binding parameters...\n";
$stmt->bind_param($type_string, 
    $pin_code, $is_epf_registered, $epf_code,
    $is_esi_registered, $esi_code, $work_order_no,
    $insurance_policy_name, $insurance_policy_no, $insurance_validity, $insurance_workers_count,
    $labour_license_no, $labour_license_issued_by, $labour_license_issue_date, $labour_license_expiry_date,
    $wage_declaration, $salary_category, $user_id, $edit_id, $vendor_code
);

echo "Executing statement...\n";
if ($stmt->execute()) {
    echo "✅ Success!\n";
} else {
    echo "❌ Execution failed: " . $stmt->error . "\n";
}
