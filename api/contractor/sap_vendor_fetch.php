<?php
require_once '../../include/config.php';
require_once '../../include/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$vendor_code = $_POST['vendor_code'] ?? '';

if (empty($vendor_code)) {
    echo json_encode(['success' => false, 'message' => 'Vendor Code is required']);
    exit;
}

$vendor = db_single($conn, "SELECT * FROM sap_vendors WHERE vendor_code = ? AND active_ind = 'A'", 's', [$vendor_code]);

if ($vendor) {
    // Log the activity
    db_execute($conn, "INSERT INTO sap_logs (activity, status) VALUES (?, ?)", 'ss', ["Vendor $vendor_code validated from SAP", "SUCCESS"]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'vendor_code'    => $vendor['vendor_code'],
            'vendor_name'    => $vendor['vendor_name'],
            'contractor_name'=> $vendor['vendor_name'],  // alias for backward compat
            'vendor_mob1'    => $vendor['vendor_mob1'],
            'mobile_no'      => $vendor['vendor_mob1'],
            'vendor_mob2'    => $vendor['vendor_mob2'],
            'email_address'  => $vendor['email_address'],
            'msme_type'      => $vendor['msme_type'],
            'address'        => $vendor['address'],
            'pin'            => $vendor['pin'],
            'active_ind'     => $vendor['active_ind'],
            // Legacy fields
            'department'     => $vendor['department'] ?? '',
            'work_order'     => $vendor['work_order'] ?? '',
            'po_number'      => $vendor['po_number'] ?? '',
            'valid_from'     => $vendor['valid_from'] ?? '',
            'valid_to'       => $vendor['valid_to'] ?? '',
            'pf_number'      => $vendor['pf_number'] ?? '',
            'esi_number'     => $vendor['esi_number'] ?? '',
            'category'       => $vendor['category'] ?? '',
            'wage_code'      => $vendor['wage_code'] ?? '',
            'max_workers'    => $vendor['max_worker_limit'] ?? ''
        ],
        'message' => 'Retrieved From SAP S/4 HANA'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor Code not found in SAP'
    ]);
}
?>
