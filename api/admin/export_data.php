<?php
/** Data Export API — CSV with audit protection */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$dataset = $_GET['dataset'] ?? '';

$datasets = [
    'workmen' => ["SELECT w.id, w.temp_id, w.acc_number, w.name, w.father_name, w.dob, w.gender, w.aadhaar, w.mobile, w.trade, w.department, w.status, w.created_at, c.contractor_name FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id ORDER BY w.name", 'workmen_master'],
    'contractors' => ["SELECT id, contractor_name, vendor_code, pan, gst, mobile, email, status, created_at FROM contractors ORDER BY contractor_name", 'contractor_master'],
    'gate_passes' => ["SELECT g.id, g.pass_number, g.pass_type, g.valid_from, g.valid_to, g.status, g.created_at, w.name as workman_name FROM gate_passes g LEFT JOIN workmen w ON g.workman_id = w.id ORDER BY g.created_at DESC", 'gate_pass_history'],
    'audit_logs' => ["SELECT l.id, l.action, l.module, l.ip_address, l.created_at, u.name as user_name FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5000", 'audit_logs'],
    'training_results' => ["SELECT tr.id, w.name as workman_name, tr.result, tr.total_score, tr.created_at FROM training_results tr JOIN workmen w ON tr.workman_id = w.id ORDER BY tr.created_at DESC", 'training_results'],
    'compliance' => ["SELECT c.id, con.contractor_name, c.type, c.month_year, c.amount, c.status FROM compliance c LEFT JOIN contractors con ON c.contractor_id = con.id ORDER BY c.uploaded_at DESC", 'compliance_records'],
    'blocked_workers' => ["SELECT w.id, w.name, w.aadhaar, w.status, c.contractor_name FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id WHERE w.status='blocked'", 'blocked_workers'],
];

if (!isset($datasets[$dataset])) {
    jsonError("Invalid dataset. Available: " . implode(', ', array_keys($datasets)));
}

$requiredTables = [
    'workmen' => 'workmen',
    'contractors' => 'contractors',
    'gate_passes' => 'gate_passes',
    'audit_logs' => 'audit_logs',
    'training_results' => 'training_results',
    'compliance' => 'compliance',
    'blocked_workers' => 'workmen',
];

$requiredTable = $requiredTables[$dataset] ?? '';
if ($requiredTable !== '') {
    $safeTable = clms_db_real_escape_string($conn, $requiredTable);
    $tableCheck = clms_db_query($conn, "SHOW TABLES LIKE '$safeTable'");
    if (!$tableCheck || clms_db_num_rows($tableCheck) === 0) {
        jsonError("Cannot export '$dataset': source table '$requiredTable' does not exist.", 404);
    }
}

$ds = $datasets[$dataset];
$rows = db_fetch_all($conn, $ds[0]);
logAdminActivity($conn, 'data_exported', 'exports', null, null, ['dataset'=>$dataset,'rows'=>count($rows)], 'info');

$filename = $ds[1] . '_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
if (!empty($rows)) {
    fputcsv($output, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($output, $row);
}
fclose($output);
exit;
