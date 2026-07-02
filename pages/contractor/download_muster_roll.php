<?php
// pages/contractor/download_muster_roll.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'welfare_user', 'welfare_admin', 'welfare', 'super_admin', 'admin']);
include '../../include/config.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get contractor ID from GET (for welfare/admin) or from session (for contractor)
$contractor_id = 0;
if (($role === 'welfare_user' || $role === 'welfare_admin' || $role === 'welfare' || $role === 'super_admin' || $role === 'admin') && isset($_GET['contractor_id'])) {
    $contractor_id = intval($_GET['contractor_id']);
} else {
    // Logged in as contractor
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $contractor_id = $contractor['id'] ?? 0;
}

if (!$contractor_id) {
    die("Error: Invalid Contractor context.");
}

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Fetch contractor details
$c = db_single($conn, "SELECT * FROM contractors WHERE id = ?", 'i', [$contractor_id]);
if (!$c) {
    die("Error: Contractor not found.");
}

// Fetch active workmen
$workers = db_fetch_all($conn, "
    SELECT id, name, father_name, gender, trade, acc_number, aadhaar, certified_wage_rate 
    FROM workmen 
    WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
    ORDER BY name ASC
", 'i', [$contractor_id]);

// Fetch PO Details (latest active PO for vendor)
$po = db_single($conn, "
    SELECT po_number, header_text, purchasing_group, vendor_name 
    FROM sap_po_master 
    WHERE vendor_code = ? 
    ORDER BY document_date DESC LIMIT 1
", 's', [$c['vendor_code']]);

$po_number = $po['po_number'] ?? $c['work_order_no'] ?? 'N/A';
$nature_of_work = $po['header_text'] ?? 'N/A';
$dept_name = $po['purchasing_group'] ?? $c['work_awarding_department'] ?? 'N/A';

// Fetch attendance and OT for this month
$attendance_data = [];
$start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-$days_in_month";

$attendance_query = db_fetch_all($conn, "
    SELECT workman_id as worker_id, DAY(check_in) as day, status, ot_hours 
    FROM attendance 
    WHERE workman_id IN (SELECT id FROM workmen WHERE contractor_id = ?) 
    AND check_in BETWEEN ? AND ?
", 'iss', [$contractor_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);

foreach ($attendance_query as $row) {
    $w_id = $row['worker_id'];
    $d = $row['day'];
    $attendance_data[$w_id][$d] = [
        'status' => $row['status'],
        'ot_hours' => floatval($row['ot_hours'] ?? 0)
    ];
}

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$monthName = $months[$month];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Muster Roll - <?= htmlspecialchars($c['contractor_name'] ?? $c['vendor_name'] ?? '') ?> - <?= $monthName ?> <?= $year ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
            background-color: #fff;
        }
        .header-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .header-section h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header-section h3 {
            margin: 0;
            font-size: 13px;
            font-weight: normal;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 4px 8px;
            vertical-align: top;
            border: none;
            width: 50%;
        }
        .meta-label {
            font-weight: bold;
            display: inline-block;
            width: 220px;
        }
        .muster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .muster-table th, .muster-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
        }
        .muster-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-left {
            text-align: left !important;
            padding-left: 5px !important;
        }
        .sub-row-label {
            font-size: 8px;
            color: #555;
            font-weight: bold;
        }
        .footer-section {
            margin-top: 45px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-box {
            border-top: 1px solid #000;
            width: 250px;
            text-align: center;
            padding-top: 5px;
            font-weight: bold;
        }
        @media print {
            body {
                margin: 10px;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
        }
        .no-print-bar {
            background: #f1f5f9;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #cbd5e1;
        }
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-outline {
            background: transparent;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn-outline:hover {
            background: #f8fafc;
        }
    </style>
</head>
<body>

<div class="no-print-bar no-print">
    <div>
        <strong>Print Preview Mode</strong> – Monthly Muster Roll | Use Ctrl+P or the print button to save/print.
    </div>
    <div style="display:flex; gap:10px;">
        <a class="btn btn-outline" href="download_annual_muster_roll.php?year=<?= $year ?>&contractor_id=<?= (int)$contractor_id ?>">📅 Annual Muster Roll</a>
        <button class="btn btn-outline" onclick="window.close()"><i class="fas fa-times"></i> Close Window</button>
        <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print Muster Roll</button>
    </div>
</div>

<div class="header-section">
    <h2>FORM XVI</h2>
    <h3>[See Rule 78(1)(a)(i)]</h3>
    <h2 style="margin-top: 10px; font-size: 16px;">MUSTER ROLL (मस्टर रोल)</h2>
</div>

<table class="meta-table">
    <tr>
        <td>
            <span class="meta-label">Name and Address of Contractor:</span>
            <span><?= htmlspecialchars($c['contractor_name'] ?? $c['vendor_name'] ?? '') ?>, <?= htmlspecialchars($c['address']) ?></span>
            <br>
            <span class="meta-label">Nature and Location of Work:</span>
            <span><?= htmlspecialchars($nature_of_work) ?></span>
        </td>
        <td>
            <span class="meta-label">Name & Address of Principal Employer:</span>
            <span>CLMS Infrastructure Division</span>
            <br>
            <span class="meta-label">Work Order / PO Number:</span>
            <span><?= htmlspecialchars($po_number) ?> (Dept: <?= htmlspecialchars($dept_name) ?>)</span>
            <br>
            <span class="meta-label">For the Month of:</span>
            <strong><?= $monthName ?> <?= $year ?></strong>
        </td>
    </tr>
</table>

<table class="muster-table">
    <thead>
        <tr>
            <th rowspan="2" style="width: 3%;">Sl. No.</th>
            <th rowspan="2" style="width: 15%;">Name of Workman</th>
            <th rowspan="2" style="width: 8%;">PC No.</th>
            <th rowspan="2" style="width: 4%;">Type</th>
            <th colspan="<?= $days_in_month ?>">Dates of the Month (1 to <?= $days_in_month ?>)</th>
            <th rowspan="2" style="width: 5%;">Total Days</th>
            <th rowspan="2" style="width: 5%;">Rate</th>
            <th rowspan="2" style="width: 12%;">Signature of Employee</th>
        </tr>
        <tr>
            <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                <th style="width: 1.8%;"><?= $d ?></th>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($workers)): ?>
            <tr>
                <td colspan="<?= $days_in_month + 7 ?>" style="padding: 30px; text-align: center;">
                    No active workmen found for this month.
                </td>
            </tr>
        <?php else: ?>
            <?php 
            $sl = 1;
            foreach ($workers as $w): 
                $w_id = $w['id'];
                $total_present = 0;
                $total_ot = 0.0;
            ?>
                <!-- Attendance Row -->
                <tr>
                    <td rowspan="2"><?= $sl++ ?></td>
                    <td rowspan="2" class="text-left"><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                    <td rowspan="2"><code><?= htmlspecialchars($w['acc_number']) ?></code></td>
                    <td class="sub-row-label">Attd</td>
                    <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <?php 
                        $day_data = $attendance_data[$w_id][$d] ?? null;
                        $status = $day_data['status'] ?? '-';
                        $char = '-';
                        if (strcasecmp($status, 'present') === 0 || strcasecmp($status, 'p') === 0) {
                            $char = 'P';
                            $total_present++;
                        } elseif (strcasecmp($status, 'absent') === 0 || strcasecmp($status, 'a') === 0) {
                            $char = 'A';
                        } elseif (strcasecmp($status, 'holiday') === 0 || strcasecmp($status, 'h') === 0) {
                            $char = 'H';
                        }
                        ?>
                        <td><?= $char ?></td>
                    <?php endfor; ?>
                    <td style="font-weight: bold; font-size: 11px; background-color: #fafafa;"><?= $total_present ?></td>
                    <td rowspan="2" style="font-weight: bold; font-size: 11px; background-color: #fafafa;"><?= htmlspecialchars($w['certified_wage_rate'] ?? '0.00') ?></td>
                    <td rowspan="2"></td>
                </tr>
                <!-- OT Row -->
                <tr>
                    <td class="sub-row-label">O.T.</td>
                    <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <?php 
                        $day_data = $attendance_data[$w_id][$d] ?? null;
                        $ot = $day_data['ot_hours'] ?? 0;
                        if ($ot > 0) {
                            $total_ot += $ot;
                            $disp = number_format($ot, 1);
                        } else {
                            $disp = '-';
                        }
                        ?>
                        <td style="color:#777; font-size:8px;"><?= $disp ?></td>
                    <?php endfor; ?>
                    <td style="font-weight: bold; font-size: 9px; color:#555; background-color: #fafafa;"><?= number_format($total_ot, 1) ?>h</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="footer-section">
    <div class="signature-box" style="border-top:none; text-align:left; width: 350px;">
        Note: P - Present, A - Absent, H - Holiday/Weekly Off
    </div>
    <div class="signature-box">
        Signature of Contractor / Representative
    </div>
    <div class="signature-box">
        Principal Employer Verification Sign
    </div>
</div>

</body>
</html>
