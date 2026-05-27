<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

enforceRole(['execution_officer', 'super_admin']);

$type = $_GET['type'] ?? 'attendance';
$format = $_GET['format'] ?? 'csv';

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="EO_Report_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

if ($type === 'attendance') {
    fputcsv($output, ['Worker Name', 'Contractor', 'Punch Time', 'Status']);
    // Fetch data...
} elseif ($type === 'productivity') {
    fputcsv($output, ['Contractor', 'Deployed', 'Present', 'Efficiency %']);
    // Fetch data...
} else {
    fputcsv($output, ['Report Type', $type, 'Generated on', date('Y-m-d H:i:s')]);
}

fclose($output);
exit;
?>
