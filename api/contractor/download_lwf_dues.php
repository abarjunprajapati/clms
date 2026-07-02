<?php
// api/contractor/download_lwf_dues.php
// Contractor LWF Dues Excel Download
// Rule: Include ONLY workers with >= 15 days in the period. Exclude workers with < 15 days.
// Format (Page 50): Sr No | Name of Employee | Employee Contribution | Employer Contribution | ACC No | Aadhar No | Mobile No
// Below: Acc No (blank) | IFSC (blank) | Bank Name (blank) | Branch Name (blank)

session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/compliance_schema.php';
checkAuth(['contractor', 'welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);

try {
    ensureComplianceSchema($conn);
} catch (Throwable $e) {
    error_log('LWF dues download schema init failed: ' . $e->getMessage());
}

$role       = $_SESSION['role'];
$user_id    = $_SESSION['user_id'];
$half       = $_GET['half'] ?? 'first';
$year       = intval($_GET['year'] ?? date('Y'));

// Contractor resolution
if ($role === 'contractor') {
    $contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id       = $contractor['id'] ?? null;
    $c_name     = $contractor['contractor_name'] ?? '';
} else {
    $c_id   = isset($_GET['contractor_id']) ? intval($_GET['contractor_id']) : null;
    $conRec = $c_id ? db_single($conn, "SELECT contractor_name, vendor_code FROM contractors WHERE id = ?", 'i', [$c_id]) : null;
    $c_name = $conRec['contractor_name'] ?? '';
}

if (!$c_id) { die("Contractor not found."); }

// Date range
if ($half === 'first') {
    $startDate = "$year-01-01";
    $endDate   = "$year-06-30";
    $halfLabel = "First Half $year";
} else {
    $startDate = "$year-07-01";
    $endDate   = "$year-12-31";
    $halfLabel = "Second Half $year";
}

// Get latest LWF rate
$latestRate = db_single($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC LIMIT 1");
$empC  = (float)($latestRate['employee_contribution'] ?? 45);
$emplC = (float)($latestRate['employer_contribution'] ?? 45);

// Fetch all workers for this contractor
$workers = db_fetch_all($conn, "
    SELECT w.id, w.name, w.aadhar_number, w.mobile_number, w.account_number, w.ifsc_code, w.bank_name, w.bank_branch
    FROM workmen w
    WHERE w.contractor_id = ? AND w.status NOT IN ('draft','pending','inactive','blocked')
    ORDER BY w.name ASC
", 'i', [$c_id]);

// Filter: ONLY workers with >= 15 days (workers with < 15 days must NOT appear in report)
$eligibleWorkers = [];
foreach ($workers as $w) {
    $days = db_single($conn, "
        SELECT COUNT(DISTINCT DATE(check_in)) AS present_days
        FROM attendance
        WHERE workman_id = ? AND DATE(check_in) BETWEEN ? AND ?
          AND LOWER(status) IN ('present','p')
    ", 'iss', [$w['id'], $startDate, $endDate]);
    $pd = (int)($days['present_days'] ?? 0);
    if ($pd >= 15) {
        $w['present_days'] = $pd;
        $eligibleWorkers[] = $w;
    }
}

// Output as Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="LWF_Dues_' . $halfLabel . '_' . $c_name . '.xls"');
header('Cache-Control: max-age=0');
?>
<html><head><meta charset="UTF-8"></head><body>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px;">
    <tr>
        <td colspan="7" style="font-weight:bold; font-size:14px; background:#f3e8ff; text-align:center;">
            Labour Welfare Fund (LWF) – <?= htmlspecialchars($halfLabel) ?>
        </td>
    </tr>
    <tr>
        <td colspan="7" style="font-size:11px; color:#555;">
            Contractor: <?= htmlspecialchars($c_name) ?> | Employee Contribution: ₹<?= $empC ?> | Employer Contribution: ₹<?= $emplC ?>
        </td>
    </tr>
    <tr style="background:#ddd8fe; font-weight:bold;">
        <td>Sr No</td>
        <td>Name of Employee</td>
        <td>Employee Contribution (₹)</td>
        <td>Employer Contribution (₹)</td>
        <td>ACC No</td>
        <td>Aadhar No</td>
        <td>Mobile No</td>
    </tr>
    <?php if (empty($eligibleWorkers)): ?>
    <tr><td colspan="7" style="text-align:center; color:#888;">No workers with ≥ 15 days found for this period.</td></tr>
    <?php else: ?>
    <?php foreach ($eligibleWorkers as $i => $w): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($w['name']) ?></td>
        <td><?= number_format($empC, 2) ?></td>
        <td><?= number_format($emplC, 2) ?></td>
        <td><?= htmlspecialchars($w['account_number'] ?? '') ?></td>
        <td><?= htmlspecialchars($w['aadhar_number'] ?? '') ?></td>
        <td><?= htmlspecialchars($w['mobile_number'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <!-- Below: Bank details row (as per Page 50 format) -->
    <tr style="background:#f9fafb;">
        <td colspan="7" style="font-weight:bold; background:#e2e8f0; padding:6px;">Below (Bank Details):</td>
    </tr>
    <?php foreach ($eligibleWorkers as $i => $w): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($w['name']) ?></td>
        <td colspan="1" style="color:#555;">Acc No: <?= htmlspecialchars($w['account_number'] ?? '') ?></td>
        <td colspan="1" style="color:#555;">IFSC: <?= htmlspecialchars($w['ifsc_code'] ?? '') ?></td>
        <td colspan="1" style="color:#555;">Bank Name: <?= htmlspecialchars($w['bank_name'] ?? '') ?></td>
        <td colspan="2" style="color:#555;">Branch Name: <?= htmlspecialchars($w['bank_branch'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="background:#ddd8fe; font-weight:bold;">
        <td colspan="2">Total</td>
        <td><?= number_format($empC * count($eligibleWorkers), 2) ?></td>
        <td><?= number_format($emplC * count($eligibleWorkers), 2) ?></td>
        <td colspan="3"></td>
    </tr>
    <?php endif; ?>
</table>
</body></html>
