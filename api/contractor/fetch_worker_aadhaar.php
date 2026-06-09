<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $aadhaar = $_GET['aadhaar'] ?? '';
    if (strlen($aadhaar) !== 12) {
        throw new Exception("Invalid Aadhaar number.");
    }

    // Search in workmen table (Common Pool)
    $worker = db_single($conn, "
        SELECT * FROM workmen 
        WHERE aadhaar = ? 
        ORDER BY updated_at DESC LIMIT 1
    ", 's', [$aadhaar]);

    if ($worker) {
        // Prepare data for auto-fill
        $data = [
            'source' => 'COMMON_POOL',
            'name' => $worker['name'] ?? '',
            'gender' => $worker['gender'] ?? '',
            'mobile' => $worker['mobile'] ?? '',
            'email' => $worker['email'] ?? '',
            'pincode' => $worker['pincode'] ?? '',
            'nationality' => $worker['nationality'] ?? 'Indian',
            'present_address' => $worker['present_address'] ?? '',
            'permanent_address' => $worker['permanent_address'] ?? '',
            'state' => $worker['state'] ?? '',
            'district' => $worker['district'] ?? '',
            'qualification' => $worker['qualification'] ?? ($worker['education'] ?? ''),
            'experience' => $worker['experience'] ?? '',
            'nature_of_work' => $worker['nature_of_work'] ?? '',
            'daily_wage_rate' => $worker['daily_wage_rate'] ?? ($worker['wage_rate'] ?? ''),
            'esic_number' => $worker['esic_number'] ?? ($worker['esi_no'] ?? ''),
            'uan_number' => $worker['uan_number'] ?? '',
            'bank_account_number' => $worker['bank_account_number'] ?? ($worker['bank_account'] ?? ''),
            'ifsc_code' => $worker['ifsc_code'] ?? ($worker['ifsc'] ?? '')
        ];

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "New Aadhaar - Manual entry required."
        ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
