<?php
require_once '../../../include/config.php';

$ids_raw = isset($_GET['ids']) ? $_GET['ids'] : '';
if (empty($ids_raw)) {
    http_response_code(400);
    echo "No worker IDs provided.";
    exit;
}

// Clean and validate IDs
$ids = array_filter(array_map('intval', explode(',', $ids_raw)));
if (empty($ids)) {
    http_response_code(400);
    echo "Invalid worker IDs.";
    exit;
}

$id_list = implode(',', $ids);

$query = "
    SELECT w.worker_id, wm.name as worker_name, w.aadhaar_no, w.acc_no,
           c.contractor_name, d.name as department_name, w.trade, w.skill_category,
           w.qualification, w.worker_status, w.created_at
    FROM worker_master w
    LEFT JOIN workmen wm ON w.worker_id = wm.id
    LEFT JOIN contractors c ON w.contractor_id = c.id
    LEFT JOIN master_departments d ON w.department_id = d.id
    WHERE w.worker_id IN ($id_list)
    ORDER BY w.worker_id ASC
";

$res = mysqli_query($conn, $query);

if (!$res) {
    http_response_code(500);
    echo "Database error: " . mysqli_error($conn);
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=enrolled_workers_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, [
    'Worker ID',
    'Worker Name',
    'Aadhaar Number',
    'ACC Number',
    'Contractor Name',
    'Department Name',
    'Trade',
    'Skill Category',
    'Qualification',
    'Status',
    'Enrolled Date'
]);

// Data rows
while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($output, [
        $row['worker_id'],
        $row['worker_name'],
        $row['aadhaar_no'],
        $row['acc_no'],
        $row['contractor_name'],
        $row['department_name'],
        $row['trade'],
        $row['skill_category'],
        $row['qualification'],
        $row['worker_status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
?>
