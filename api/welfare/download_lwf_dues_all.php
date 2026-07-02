<?php
// api/welfare/download_lwf_dues_all.php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin', 'contractor'])) {
    http_response_code(403); die("Access denied.");
}

ensureComplianceSchema($conn);

$half = trim($_GET['half'] ?? 'first');
$year = (int)($_GET['year'] ?? date('Y'));

if ($half === 'first') {
    $startDate = "$year-01-01"; $endDate = "$year-06-30";
    $halfLabel = "First Half (Jan-Jun $year)";
} else {
    $startDate = "$year-07-01"; $endDate = "$year-12-31";
    $halfLabel = "Second Half (Jul-Dec $year)";
}

$latestRate = db_single($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC LIMIT 1");
$empC  = (float)($latestRate['employee_contribution'] ?? 45);
$emplC = (float)($latestRate['employer_contribution'] ?? 45);

$contractors = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");

$filename = "LWF_Dues_All_" . $half . "_" . $year . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

echo "<html><head><meta charset='UTF-8'></head><body>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;font-family:Arial;font-size:12px;'>";
echo "<tr style='background:#7c3aed;color:#fff;font-weight:bold;'><td colspan='5' style='font-size:14px;padding:10px;'>LWF Dues – {$halfLabel} (All Contractors)</td></tr>";
echo "<tr style='background:#ede9fe;font-weight:bold;'>
    <td>S.No</td><td>Vendor Code</td><td>Vendor Name</td><td>Worker Name</td>
    <td>Employee Contribution (₹)</td><td>Employer Contribution (₹)</td>
</tr>";

$sno = 1;
foreach ($contractors as $con) {
    $workers = db_fetch_all($conn, "SELECT id, name FROM workmen WHERE contractor_id = ? AND status NOT IN ('draft','pending','inactive','blocked') ORDER BY name ASC", 'i', [$con['id']]);
    $eligible = [];
    foreach ($workers as $w) {
        $days = db_single($conn, "SELECT COUNT(DISTINCT DATE(check_in)) AS pd FROM attendance WHERE workman_id = ? AND DATE(check_in) BETWEEN ? AND ? AND LOWER(status) IN ('present','p')", 'iss', [$w['id'], $startDate, $endDate]);
        if ((int)($days['pd'] ?? 0) >= 15) $eligible[] = $w;
    }
    if (empty($eligible)) continue;

    foreach ($eligible as $w) {
        echo "<tr>
            <td>{$sno}</td>
            <td>" . htmlspecialchars($con['vendor_code']) . "</td>
            <td>" . htmlspecialchars($con['contractor_name']) . "</td>
            <td>" . htmlspecialchars($w['name']) . "</td>
            <td style='text-align:right;'>" . number_format($empC, 2) . "</td>
            <td style='text-align:right;'>" . number_format($emplC, 2) . "</td>
        </tr>";
        $sno++;
    }
}
echo "</table></body></html>";
?>
