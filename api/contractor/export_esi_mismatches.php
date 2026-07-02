<?php
// api/contractor/export_esi_mismatches.php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';

checkAuth(['contractor', 'welfare_user', 'welfare', 'super_admin', 'admin']);

$complianceId = isset($_GET['compliance_id']) ? intval($_GET['compliance_id']) : 0;
if (!$complianceId) {
    die("Error: Invalid compliance ID.");
}

// Fetch errors
$row = db_single($conn, "SELECT month_year, validation_errors, contractor_id FROM compliance WHERE id = ? LIMIT 1", 'i', [$complianceId]);
if (!$row) {
    die("Error: Compliance record not found.");
}

// Verify context (either admin/welfare or the contractor itself)
if ($_SESSION['role'] === 'contractor') {
    $c = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? LIMIT 1", 'i', [$_SESSION['user_id']]);
    if (!$c || (int)$c['id'] !== (int)$row['contractor_id']) {
        die("Access denied.");
    }
}

$errors = array_filter(explode("\n", (string)$row['validation_errors']));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ESI_Mismatches_' . $row['month_year'] . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Wage Month', $row['month_year']]);
fputcsv($output, []);
fputcsv($output, ['S.No', 'Mismatch / Validation Exception Details']);

$idx = 1;
foreach ($errors as $err) {
    fputcsv($output, [$idx++, trim($err)]);
}

fclose($output);
exit;
?>
