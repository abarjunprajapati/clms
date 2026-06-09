<?php
require_once '../include/config.php';

header('Content-Type: application/json');

$code = $_REQUEST['vendor_code'] ?? '';

if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'SAP Code is required']);
    exit;
}

// 1. Try fetching as Vendor from sap_vendor_master
$data = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ? AND active_ind = 'A'", 's', [$code]);

if ($data) {
    db_execute($conn, "INSERT INTO sap_logs (activity, status) VALUES (?, ?)", 'ss', ["Vendor $code fetched via AJAX", "SUCCESS"]);
    echo json_encode([
        'status' => 'success',
        'data' => [
            'vendor_code'   => $data['vendor_code'],
            'vendor_name'   => $data['vendor_name'],
            'vendor_mob1'   => $data['vendor_mob1'],
            'mobile_no'     => $data['vendor_mob1'],
            'vendor_mob2'   => $data['vendor_mob2'] ?? '',
            'email_address' => $data['email_address'],
            'msme_type'     => $data['msme_type'] ?? '',
            'address'       => $data['address'],
            'pin'           => $data['pin'] ?? '',
            'active_ind'    => $data['active_ind'] ?? 'A'
        ]
    ]);
    exit;
}

// 2. Try fetching as Customer
$data = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);

if ($data) {
    db_execute($conn, "INSERT INTO sap_logs (activity, status) VALUES (?, ?)", 'ss', ["Customer $code fetched via AJAX", "SUCCESS"]);
    echo json_encode([
        'status' => 'success',
        'data' => [
            'vendor_code'   => $data['customer_code'],
            'vendor_name'   => $data['customer_name'],
            'vendor_mob1'   => $data['mobile'],
            'mobile_no'     => $data['mobile'],
            'email_address' => $data['email'],
            'address'       => $data['address'] ?? '',
            'active_ind'    => 'A',
            'is_customer'   => true
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Record not found in SAP Vendor or Customer master']);
}
?>
