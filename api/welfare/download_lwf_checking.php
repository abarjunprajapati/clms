<?php
// api/welfare/download_lwf_checking.php
// Welfare User: LWF Dues Checking Excel Download
// Format (Page 54): Contractor Vendor Code | Vendor Name | Worker | Employee Contribution | Employer Contribution | Total | Upload Payment Proof | Mismatch Amount
// Mismatch logic: 540 - 440 = 100. If less → Not Complied.

session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/compliance_schema.php';
checkAuth(['welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);

try {
    ensureComplianceSchema($conn);
} catch (Throwable $e) {
    error_log('LWF checking download schema init failed: ' . $e->getMessage());
}

$half = $_GET['half'] ?? 'first';
$year = intval($_GET['year'] ?? date('Y'));
$selectedContractors = isset($_GET['contractors']) ? array_map('intval', (array)$_GET['contractors']) : [];

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

// Get contractors
$allContractors = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");
$queryIds = !empty($selectedContractors) ? $selectedContractors : array_column($allContractors, 'id');

// Build data
$rows = [];
foreach ($queryIds as $cid) {
    $con = null;
    foreach ($allContractors as $ac) { if ($ac['id'] == $cid) { $con = $ac; break; } }
    if (!$con) continue;

    $workers = db_fetch_all($conn, "SELECT w.id, w.name FROM workmen w WHERE w.contractor_id = ? AND w.status NOT IN ('draft','pending','inactive','blocked')", 'i', [$cid]);

    $eligibleWorkers = [];
    foreach ($workers as $w) {
        $days = db_single($conn, "SELECT COUNT(DISTINCT DATE(check_in)) AS pd FROM attendance WHERE workman_id = ? AND DATE(check_in) BETWEEN ? AND ? AND LOWER(status) IN ('present','p')", 'iss', [$w['id'], $startDate, $endDate]);
        if ((int)($days['pd'] ?? 0) >= 15) $eligibleWorkers[] = $w;
    }

    $workerCount  = count($eligibleWorkers);
    $empTotal     = $empC * $workerCount;
    $emplTotal    = $emplC * $workerCount;
    $dueAmount    = $empTotal + $emplTotal;

    $lwfRecord    = db_single($conn, "SELECT * FROM compliance_lwf WHERE contractor_id = ? AND period_half = ? AND period_year = ?", 'isi', [$cid, $half, $year]);
    $paidAmount   = (float)($lwfRecord['paid_amount'] ?? 0);
    $mismatch     = $dueAmount - $paidAmount;
    $proofPath    = $lwfRecord['payment_proof_path'] ?? '';

    $rows[] = [
        'vendor_code'  => $con['vendor_code'],
        'vendor_name'  => $con['contractor_name'],
        'workers'      => $eligibleWorkers,
        'worker_count' => $workerCount,
        'emp_total'    => $empTotal,
        'empl_total'   => $emplTotal,
        'due_amount'   => $dueAmount,
        'paid_amount'  => $paidAmount,
        'mismatch'     => $mismatch,
        'proof_path'   => $proofPath,
    ];
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="LWF_Dues_Checking_' . $halfLabel . '.xls"');
header('Cache-Control: max-age=0');
?>
<html><head><meta charset="UTF-8"></head><body>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px;">
    <tr>
        <td colspan="8" style="font-weight:bold; font-size:14px; background:#ddd8fe; text-align:center;">
            LWF Dues * Checking – <?= htmlspecialchars($halfLabel) ?>
        </td>
    </tr>
    <tr>
        <td colspan="8" style="font-size:11px; color:#555;">
            Employee Contribution: ₹<?= $empC ?> per worker | Employer Contribution: ₹<?= $emplC ?> per worker
        </td>
    </tr>
    <tr style="background:#ddd8fe; font-weight:bold;">
        <td>Contractor Vendor Code</td>
        <td>Vendor Name</td>
        <td>Worker (≥15 days)</td>
        <td>Employee Contribution (₹)</td>
        <td>Employer Contribution (₹)</td>
        <td>Total (₹)</td>
        <td>Upload Payment Proof</td>
        <td>Mismatch Amount (₹)</td>
    </tr>
    <?php if (empty($rows)): ?>
    <tr><td colspan="8" style="text-align:center; color:#888;">No data found.</td></tr>
    <?php else: ?>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r['vendor_code']) ?></td>
        <td><?= htmlspecialchars($r['vendor_name']) ?></td>
        <td><?= $r['worker_count'] ?></td>
        <td><?= number_format($r['emp_total'], 2) ?></td>
        <td><?= number_format($r['empl_total'], 2) ?></td>
        <td style="font-weight:bold;"><?= number_format($r['due_amount'], 2) ?></td>
        <td><?= $r['proof_path'] ? 'Uploaded' : 'Not uploaded' ?></td>
        <td style="color:<?= $r['mismatch'] > 0 ? 'red' : 'green' ?>; font-weight:bold;">
            <?php if ($r['mismatch'] > 0): ?>
            <?= number_format($r['due_amount'], 0) ?> − <?= number_format($r['paid_amount'], 0) ?> = <?= number_format($r['mismatch'], 2) ?> (Not Complied)
            <?php else: ?>
            Complied
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
</table>
</body></html>
