<?php
// Mock ESI submission POST request to live server
$url = 'https://cslweb.teleconsystems.com/api/contractor/save_compliance.php';

$data = [
    'type' => 'esi',
    'contribution_month' => '2026-04',
    'challan_no' => '87654345',
    'challan_date' => '2026-05-20',
    'employees_count' => '10',
    'gross_wages' => '100000',
    'employer_contribution' => '3250.00',
    'employee_contribution' => '750.00',
    'total_contribution' => '4000.00'
];

// We need a dummy file for challan_file
$filePath = tempnam(sys_get_temp_dir(), 'challan');
file_put_contents($filePath, 'fake pdf content');

$cfile = new CURLFile($filePath, 'application/pdf', 'challan.pdf');
$data['challan_file'] = $cfile;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

unlink($filePath);

echo "HTTP CODE: $httpCode\n";
echo "RESPONSE:\n$response\n";
?>
