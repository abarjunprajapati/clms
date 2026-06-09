<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth_middleware.php';

require_role(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);

function directoryExportStatus($type, $row) {
    if ($type === 'customer') {
        if (strtoupper((string)($row['status'] ?? '')) === 'INACTIVE') return 'rejected';
        return ((int)($row['is_password_created'] ?? 0)) ? 'approved' : 'pending';
    }
    $status = strtolower((string)($row['status'] ?? 'pending'));
    if (in_array($status, ['approved', 'active'], true)) return 'approved';
    if (in_array($status, ['rejected', 'blocked'], true)) return 'rejected';
    return 'pending';
}

function directoryExportRows($conn) {
    $rows = [];

    $contractors = db_fetch_all($conn, "
        SELECT id, vendor_code, vendor_name, contractor_name, mobile, email, email_address,
               pan, pan_no, gst, gst_no, address, work_awarding_department, work_order_no,
               status, compliance_status, created_at
        FROM contractors
        ORDER BY created_at DESC
    ");
    foreach ($contractors as $c) {
        $rows[] = [
            'type' => 'contractor',
            'code' => $c['vendor_code'] ?? '',
            'name' => $c['contractor_name'] ?: ($c['vendor_name'] ?? ''),
            'mobile' => $c['mobile'] ?? '',
            'email' => $c['email'] ?: ($c['email_address'] ?? ''),
            'address' => $c['address'] ?? '',
            'status' => directoryExportStatus('contractor', $c),
            'raw_status' => $c['status'] ?? '',
            'pan' => $c['pan'] ?: ($c['pan_no'] ?? ''),
            'gst' => $c['gst'] ?: ($c['gst_no'] ?? ''),
            'work_order' => $c['work_order_no'] ?? '',
            'department' => $c['work_awarding_department'] ?? '',
            'created_at' => $c['created_at'] ?? ''
        ];
    }

    $customers = db_fetch_all($conn, "
        SELECT id, customer_code, customer_name, Customer_MOB1, customer_MOB2,
               EMAIL_ADDRESS, email, mobile, Address, PIN, ACTIVE_IND, status,
               is_password_created, created_at
        FROM sap_customer_master
        ORDER BY created_at DESC
    ");
    foreach ($customers as $c) {
        $rows[] = [
            'type' => 'customer',
            'code' => $c['customer_code'] ?? '',
            'name' => $c['customer_name'] ?? '',
            'mobile' => $c['Customer_MOB1'] ?: ($c['mobile'] ?? ''),
            'email' => $c['EMAIL_ADDRESS'] ?: ($c['email'] ?? ''),
            'address' => $c['Address'] ?? '',
            'status' => directoryExportStatus('customer', $c),
            'raw_status' => $c['status'] ?? $c['ACTIVE_IND'] ?? '',
            'pan' => '',
            'gst' => '',
            'work_order' => '',
            'department' => '',
            'created_at' => $c['created_at'] ?? ''
        ];
    }

    usort($rows, function($a, $b) {
        return strtotime($b['created_at'] ?: '1970-01-01') <=> strtotime($a['created_at'] ?: '1970-01-01');
    });

    return $rows;
}

$type = strtolower(trim($_GET['type'] ?? ''));
$status = strtolower(trim($_GET['status'] ?? ''));
$search = strtolower(trim($_GET['search'] ?? ''));

$rows = array_filter(directoryExportRows($conn), function($row) use ($type, $status, $search) {
    if ($type !== '' && $row['type'] !== $type) return false;
    if ($status !== '' && $row['status'] !== $status) return false;
    if ($search !== '') {
        $haystack = strtolower(implode(' ', $row));
        if (strpos($haystack, $search) === false) return false;
    }
    return true;
});

$filename = 'contractor_customer_directory_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
fputcsv($out, ['Type', 'Code', 'Name', 'Mobile', 'Email', 'Address', 'Status', 'Raw Status', 'PAN', 'GST', 'Work Order', 'Department', 'Created At']);
foreach ($rows as $row) {
    fputcsv($out, [
        ucfirst($row['type']),
        $row['code'],
        $row['name'],
        $row['mobile'],
        $row['email'],
        $row['address'],
        strtoupper($row['status']),
        $row['raw_status'],
        $row['pan'],
        $row['gst'],
        $row['work_order'],
        $row['department'],
        $row['created_at']
    ]);
}
fclose($out);
exit;
?>
