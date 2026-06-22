<?php
// pages/contractor/download_annual_muster_roll.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'welfare_user', 'welfare', 'super_admin', 'admin']);
include '../../include/config.php';

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];

// Resolve contractor
$contractor_id = 0;
if (in_array($role, ['welfare_user', 'welfare', 'super_admin', 'admin']) && isset($_GET['contractor_id'])) {
    $contractor_id = intval($_GET['contractor_id']);
} else {
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $contractor_id = $contractor['id'] ?? 0;
}
if (!$contractor_id) die("Error: Invalid Contractor context.");

$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Contractor details
$c = db_single($conn, "SELECT * FROM contractors WHERE id = ?", 'i', [$contractor_id]);
if (!$c) die("Error: Contractor not found.");

// PO details
$po = db_single($conn, "
    SELECT po_number, header_text, purchasing_group, vendor_name
    FROM sap_po_master
    WHERE vendor_code = ?
    ORDER BY document_date DESC LIMIT 1
", 's', [$c['vendor_code']]);
$po_number      = $po['po_number']         ?? $c['work_order_no']              ?? 'N/A';
$nature_of_work = $po['header_text']        ?? 'N/A';
$dept_name      = $po['purchasing_group']   ?? $c['work_awarding_department']  ?? 'N/A';

// Active workmen
$workers = db_fetch_all($conn, "
    SELECT id, name, father_name, gender, trade, acc_number, aadhaar, certified_wage_rate
    FROM workmen
    WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
    ORDER BY name ASC
", 'i', [$contractor_id]);

// Fetch full-year attendance summary per worker per month
$attendance_summary = [];
$year_start = "$year-01-01 00:00:00";
$year_end   = "$year-12-31 23:59:59";
$raw = db_fetch_all($conn, "
    SELECT
        workman_id,
        MONTH(check_in)  AS mth,
        SUM(CASE WHEN LOWER(COALESCE(status,'')) IN ('present','p') THEN 1 ELSE 0 END) AS days_present,
        SUM(COALESCE(ot_hours, 0)) AS ot_total
    FROM attendance
    WHERE workman_id IN (SELECT id FROM workmen WHERE contractor_id = ?)
      AND check_in BETWEEN ? AND ?
    GROUP BY workman_id, MONTH(check_in)
", 'iss', [$contractor_id, $year_start, $year_end]);

foreach ($raw as $row) {
    $attendance_summary[$row['workman_id']][$row['mth']] = [
        'present' => (int)$row['days_present'],
        'ot'      => (float)$row['ot_total'],
    ];
}

$months_short = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Annual Muster Roll – <?= htmlspecialchars($c['contractor_name'] ?? $c['vendor_name'] ?? '') ?> – <?= $year ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Arial',sans-serif;font-size:10px;color:#222;background:#fff;margin:14px}

/* ── Top bar (hidden on print) ── */
.topbar{background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:10px 18px;
        margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.topbar-title{font-weight:800;font-size:14px;color:#1e293b}
.topbar-controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;
     font-weight:700;font-size:12px;cursor:pointer;border:none;text-decoration:none;transition:.15s}
.btn-primary{background:#2563eb;color:#fff}
.btn-primary:hover{background:#1d4ed8}
.btn-outline{background:#fff;color:#475569;border:1px solid #cbd5e1}
.btn-outline:hover{background:#f8fafc}
.year-select{height:34px;border:1px solid #cbd5e1;border-radius:6px;padding:0 10px;font-size:12px;font-weight:700}

/* ── Document header ── */
.doc-header{text-align:center;margin-bottom:14px}
.doc-header h2{font-size:15px;text-transform:uppercase;letter-spacing:.5px}
.doc-header h3{font-size:11px;font-weight:normal;margin-top:2px}
.doc-header .year-badge{display:inline-block;background:#2563eb;color:#fff;border-radius:6px;
                         padding:2px 12px;font-size:13px;font-weight:900;margin-top:6px}

/* ── Meta table ── */
.meta-table{width:100%;border-collapse:collapse;margin-bottom:12px;font-size:10px}
.meta-table td{padding:3px 6px;vertical-align:top;width:50%}
.meta-label{font-weight:bold;display:inline-block;min-width:200px}

/* ── Annual muster table ── */
.muster-table{width:100%;border-collapse:collapse;font-size:9px}
.muster-table th,.muster-table td{border:1px solid #000;padding:3px 2px;text-align:center}
.muster-table th{background:#f2f2f2;font-weight:bold}
.muster-table .text-left{text-align:left!important;padding-left:4px!important}
.muster-table .month-header{background:#1e3a8a;color:#fff;font-weight:900;font-size:9px}
.muster-table .sub-label{font-size:7px;color:#444;display:block;font-weight:bold}
.muster-table .total-col{background:#f8fafc;font-weight:900;font-size:10px}
.muster-table .grand-col{background:#1e293b;color:#fff;font-weight:900}
.muster-table .ot-cell{color:#666;font-size:8px}
.muster-table .present-val{color:#15803d;font-weight:900}
.muster-table .zero-val{color:#bbb}
.worker-row-attd{}
.worker-row-ot{background:#fafafa}

/* ── Totals bar ── */
.totals-section{margin-top:20px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.total-card{border:1px solid #e2e8f0;border-radius:8px;padding:10px;text-align:center}
.total-card .val{font-size:20px;font-weight:900;color:#1e3a8a}
.total-card .lbl{font-size:10px;color:#64748b;margin-top:2px}

/* ── Footer ── */
.footer-section{margin-top:35px;display:flex;justify-content:space-between;align-items:flex-end}
.signature-box{border-top:1px solid #000;width:230px;text-align:center;padding-top:5px;font-weight:bold;font-size:10px}

/* ── Print ── */
@media print{
    .topbar,.no-print{display:none!important}
    body{margin:8px}
    .muster-table{font-size:8px}
    @page{size:landscape;margin:10mm}
}
</style>
</head>
<body>

<!-- Top Control Bar -->
<div class="topbar no-print">
    <div>
        <div class="topbar-title">📋 Annual Muster Roll – <?= htmlspecialchars($c['contractor_name'] ?? $c['vendor_name'] ?? '') ?></div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">Attendance summary for all 12 months of the selected year</div>
    </div>
    <div class="topbar-controls">
        <form method="get" style="display:flex;align-items:center;gap:8px">
            <input type="hidden" name="contractor_id" value="<?= (int)$contractor_id ?>">
            <label style="font-size:12px;font-weight:700;color:#475569">Year:</label>
            <select name="year" class="year-select" onchange="this.form.submit()">
                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a href="download_muster_roll.php?month=<?= date('n') ?>&year=<?= $year ?>&contractor_id=<?= (int)$contractor_id ?>" class="btn btn-outline">
            📅 Monthly View
        </a>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Download PDF</button>
    </div>
</div>

<!-- Document Header -->
<div class="doc-header">
    <h2>FORM XVI – Annual Muster Roll (मस्टर रोल – वार्षिक)</h2>
    <h3>[See Rule 78(1)(a)(i)] – Contract Labour (Regulation &amp; Abolition) Act, 1970</h3>
    <div class="year-badge"><?= $year ?></div>
</div>

<!-- Meta Info -->
<table class="meta-table">
    <tr>
        <td>
            <span class="meta-label">Name and Address of Contractor:</span>
            <?= htmlspecialchars($c['contractor_name'] ?? $c['vendor_name'] ?? '') ?>,
            <?= htmlspecialchars($c['address'] ?? '') ?>
            <br>
            <span class="meta-label">Nature and Location of Work:</span>
            <?= htmlspecialchars($nature_of_work) ?>
        </td>
        <td>
            <span class="meta-label">Name &amp; Address of Principal Employer:</span>
            CLMS Infrastructure Division
            <br>
            <span class="meta-label">Work Order / PO Number:</span>
            <?= htmlspecialchars($po_number) ?> (Dept: <?= htmlspecialchars($dept_name) ?>)
            <br>
            <span class="meta-label">For the Year:</span>
            <strong><?= $year ?></strong>
        </td>
    </tr>
</table>

<?php if (empty($workers)): ?>
<div style="padding:30px;text-align:center;border:1px solid #e2e8f0;border-radius:8px;color:#94a3b8">
    No active workmen found for this contractor.
</div>
<?php else:
    // Compute grand totals
    $grand_total_present = 0;
    $grand_total_ot      = 0.0;
    foreach ($workers as $w) {
        for ($m = 1; $m <= 12; $m++) {
            $grand_total_present += $attendance_summary[$w['id']][$m]['present'] ?? 0;
            $grand_total_ot      += $attendance_summary[$w['id']][$m]['ot']      ?? 0.0;
        }
    }
?>

<!-- Summary Cards -->
<div class="totals-section no-print" style="margin-bottom:16px">
    <div class="total-card">
        <div class="val"><?= count($workers) ?></div>
        <div class="lbl">Total Workmen</div>
    </div>
    <div class="total-card">
        <div class="val"><?= $grand_total_present ?></div>
        <div class="lbl">Total Person-Days Present (<?= $year ?>)</div>
    </div>
    <div class="total-card">
        <div class="val"><?= number_format($grand_total_ot, 1) ?>h</div>
        <div class="lbl">Total OT Hours (<?= $year ?>)</div>
    </div>
    <div class="total-card">
        <div class="val"><?= $grand_total_present > 0 && count($workers) > 0 ? round($grand_total_present / count($workers)) : 0 ?></div>
        <div class="lbl">Avg Days / Worker</div>
    </div>
</div>

<!-- Annual Muster Table -->
<table class="muster-table">
    <thead>
        <tr>
            <th rowspan="3" style="width:3%">Sl.<br>No.</th>
            <th rowspan="3" style="width:12%;text-align:left;padding-left:4px">Name of Workman</th>
            <th rowspan="3" style="width:7%">PC / Acc No.</th>
            <th rowspan="3" style="width:5%">Trade</th>
            <!-- 12 month columns, each split into Attd + OT -->
            <?php foreach ($months_short as $i => $mn): ?>
            <th colspan="2" class="month-header"><?= $mn ?></th>
            <?php endforeach; ?>
            <th rowspan="3" class="grand-col" style="width:4%">Total<br>Days</th>
            <th rowspan="3" class="grand-col" style="width:4%">Total<br>OT(h)</th>
            <th rowspan="3" style="width:5%">Rate<br>(₹)</th>
        </tr>
        <tr>
            <?php foreach ($months_short as $mn): ?>
            <th style="font-size:8px;background:#dbeafe;color:#1e40af">Days</th>
            <th style="font-size:8px;background:#fef9c3;color:#854d0e">OT(h)</th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php
    $sl = 1;
    $month_totals = array_fill(1, 12, ['present' => 0, 'ot' => 0.0]);

    foreach ($workers as $w):
        $w_id         = $w['id'];
        $year_present = 0;
        $year_ot      = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $p = $attendance_summary[$w_id][$m]['present'] ?? 0;
            $o = $attendance_summary[$w_id][$m]['ot']      ?? 0.0;
            $year_present                += $p;
            $year_ot                     += $o;
            $month_totals[$m]['present'] += $p;
            $month_totals[$m]['ot']      += $o;
        }
    ?>
    <tr>
        <td><?= $sl++ ?></td>
        <td class="text-left"><strong><?= htmlspecialchars($w['name']) ?></strong>
            <?php if (!empty($w['father_name'])): ?>
            <span class="sub-label">S/o <?= htmlspecialchars($w['father_name']) ?></span>
            <?php endif; ?>
        </td>
        <td style="font-size:8px"><?= htmlspecialchars($w['acc_number'] ?? '') ?></td>
        <td style="font-size:8px"><?= htmlspecialchars($w['trade'] ?? '') ?></td>
        <?php for ($m = 1; $m <= 12; $m++):
            $p = $attendance_summary[$w_id][$m]['present'] ?? 0;
            $o = $attendance_summary[$w_id][$m]['ot']      ?? 0.0;
        ?>
        <td class="<?= $p > 0 ? 'present-val' : 'zero-val' ?>" style="background:#f0fdf4">
            <?= $p > 0 ? $p : '–' ?>
        </td>
        <td class="ot-cell" style="background:#fefce8">
            <?= $o > 0 ? number_format($o, 1) : '–' ?>
        </td>
        <?php endfor; ?>
        <td class="grand-col"><?= $year_present ?></td>
        <td class="grand-col"><?= $year_ot > 0 ? number_format($year_ot, 1) : '–' ?></td>
        <td><?= htmlspecialchars($w['certified_wage_rate'] ?? '0.00') ?></td>
    </tr>
    <?php endforeach; ?>

    <!-- Monthly Totals Row -->
    <tr style="background:#e0e7ff;font-weight:900">
        <td colspan="4" class="text-left" style="font-weight:900;font-size:10px;padding-left:4px">MONTH TOTAL ▶</td>
        <?php
        $col_total_present = 0;
        $col_total_ot      = 0.0;
        for ($m = 1; $m <= 12; $m++):
            $p = $month_totals[$m]['present'];
            $o = $month_totals[$m]['ot'];
            $col_total_present += $p;
            $col_total_ot      += $o;
        ?>
        <td style="background:#dbeafe;color:#1e40af;font-weight:900"><?= $p > 0 ? $p : '–' ?></td>
        <td style="background:#fef9c3;color:#854d0e;font-size:8px"><?= $o > 0 ? number_format($o,1) : '–' ?></td>
        <?php endfor; ?>
        <td class="grand-col"><?= $col_total_present ?></td>
        <td class="grand-col"><?= $col_total_ot > 0 ? number_format($col_total_ot,1) : '–' ?></td>
        <td></td>
    </tr>
    </tbody>
</table>

<!-- Footer -->
<div class="footer-section">
    <div class="signature-box" style="border-top:none;text-align:left;width:320px">
        <strong>Legend:</strong> Days = Days Present &nbsp;|&nbsp; OT(h) = Overtime Hours &nbsp;|&nbsp; – = No data
    </div>
    <div class="signature-box">
        Signature of Contractor / Representative
    </div>
    <div class="signature-box">
        Principal Employer Verification Sign
    </div>
</div>

<?php endif; ?>

</body>
</html>
