<?php
$type_string = "sisisssssissssssiis";
$variables = [
    '$pin_code', '$is_epf_registered', '$epf_code',
    '$is_esi_registered', '$esi_code', '$work_order_no',
    '$insurance_policy_name', '$insurance_policy_no', '$insurance_validity', '$insurance_workers_count',
    '$labour_license_no', '$labour_license_issued_by', '$labour_license_issue_date', '$labour_license_expiry_date',
    '$wage_declaration', '$salary_category', '$user_id', '$edit_id', '$vendor_code'
];

echo "Type string length: " . strlen($type_string) . "\n";
echo "Variables count: " . count($variables) . "\n\n";

for ($i = 0; $i < max(strlen($type_string), count($variables)); $i++) {
    $char = isset($type_string[$i]) ? $type_string[$i] : 'MISSING';
    $var = isset($variables[$i]) ? $variables[$i] : 'MISSING';
    printf("%2d: Type: %s -> Var: %s\n", $i + 1, $char, $var);
}
