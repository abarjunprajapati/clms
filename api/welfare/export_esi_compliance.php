<?php
// api/welfare/export_esi_compliance.php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

// Check permissions
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);

ensureComplianceSchema($conn);

$reportType = isset($_GET['report_type']) ? (int)$_GET['report_type'] : 1;
$complianceIdsStr = isset($_GET['compliance_ids']) ? trim($_GET['compliance_ids']) : '';

$complianceIds = array_filter(array_map('intval', explode(',', $complianceIdsStr)));
if (empty($complianceIds)) {
    die("Error: No compliance records selected.");
}

$idsImploded = implode(',', $complianceIds);
$sql = "
    SELECT c.*, con.vendor_code, con.contractor_name 
    FROM compliance c
    JOIN contractors con ON c.contractor_id = con.id
    WHERE c.id IN ($idsImploded) AND c.type = 'esi'
    ORDER BY con.vendor_code ASC
";

$res = mysqli_query($conn, $sql);
if (!$res) {
    die("Database Error: " . mysqli_error($conn));
}

$submissions = [];
while ($row = mysqli_fetch_assoc($res)) {
    $submissions[] = $row;
}

if (empty($submissions)) {
    die("Error: No ESI compliance submissions found for the selected IDs.");
}

// PDF text extraction and parsing helpers
function welfare_extract_text_from_pdf($filePath) {
    if (!$filePath || !is_file($filePath)) return '';
    $content = file_get_contents($filePath);
    if (!$content) return '';

    $text = '';
    // Find all stream blocks
    preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/is', $content, $matches);
    foreach ($matches[1] as $stream) {
        $decompressed = @gzuncompress($stream);
        if ($decompressed === false) {
            for ($i = 1; $i < 10; $i++) {
                $decompressed = @gzuncompress(substr($stream, $i));
                if ($decompressed !== false) break;
            }
        }

        $data = ($decompressed !== false) ? $decompressed : $stream;

        // Match PDF text strings: (string) Tj or TJ
        preg_match_all('/\((.*?)\)\s*T[jJ]/s', $data, $txtMatches);
        foreach ($txtMatches[1] as $t) {
            $text .= $t . " ";
        }

        preg_match_all('/\[(.*?)\]\s*TJ/s', $data, $txtMatches2);
        foreach ($txtMatches2[1] as $t) {
            preg_match_all('/\((.*?)\)/s', $t, $innerMatches);
            foreach ($innerMatches[1] as $im) {
                $text .= $im . " ";
            }
        }
    }

    // Fallback search in raw content
    preg_match_all('/\((.*?)\)/s', $content, $rawMatches);
    foreach ($rawMatches[1] as $r) {
        if (strlen($r) > 1 && !preg_match('/^[\/a-zA-Z]/', $r)) {
            $text .= $r . " ";
        }
    }

    return $text;
}

function welfare_parse_esi_pdf_workers($filePath) {
    $text = welfare_extract_text_from_pdf($filePath);
    $text = preg_replace('/\s+/', ' ', $text);

    // Find ESI Numbers (10 or 17 digits)
    preg_match_all('/\b\d{10}\b|\b\d{17}\b/', $text, $matches, PREG_OFFSET_CAPTURE);

    $records = [];
    $numMatches = count($matches[0]);
    for ($i = 0; $i < $numMatches; $i++) {
        $esiNum = $matches[0][$i][0];
        $offset = $matches[0][$i][1];

        $startPos = $offset + strlen($esiNum);
        if ($i < $numMatches - 1) {
            $length = $matches[0][$i+1][1] - $startPos;
            $segment = substr($text, $startPos, $length);
        } else {
            $segment = substr($text, $startPos, 250);
        }

        $segment = trim($segment);
        preg_match_all('/\b\d+\.\d+\b|\b\d+\b/', $segment, $numMatches2);

        $wages = 0.0;
        $days = 0;
        $foundWages = false;
        $foundDays = false;

        foreach ($numMatches2[0] as $numStr) {
            if (strpos($numStr, '.') !== false) {
                $wages = (float)$numStr;
                $foundWages = true;
            }
        }

        $integers = [];
        foreach ($numMatches2[0] as $numStr) {
            if (strpos($numStr, '.') === false) {
                $integers[] = (int)$numStr;
            }
        }

        foreach ($integers as $val) {
            if ($val >= 0 && $val <= 31 && !$foundDays) {
                $days = $val;
                $foundDays = true;
            } elseif ($val > 31 && !$foundWages) {
                $wages = (float)$val;
                $foundWages = true;
            }
        }

        $cleanName = preg_replace('/\b\d+\.\d+\b|\b\d+\b/', '', $segment);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        $cleanName = str_ireplace(['Tj', 'TJ', 'Reason', 'Code', 'Reason code', 'Last working day'], '', $cleanName);
        $cleanName = trim($cleanName);

        $records[$esiNum] = [
            'esi_number' => $esiNum,
            'name' => $cleanName,
            'days' => $days,
            'wages' => $wages
        ];
    }
    return $records;
}

// Generate workers details and mismatch data for each submission
$reportData = [];
$mismatchReport = [
    'vendor_errors' => [],
    'worker_errors' => []
];

foreach ($submissions as $sub) {
    $contractorId = (int)$sub['contractor_id'];
    $monthYear = $sub['month_year'];
    
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));
    
    // Fetch active workmen
    $workers = db_fetch_all($conn, "
        SELECT id, name, esic_number, certified_wage_rate 
        FROM workmen 
        WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
        ORDER BY name ASC
    ", 'i', [$contractorId]);

    // Fetch attendance days counts
    $attendance = [];
    if (!empty($workers)) {
        $attQuery = db_fetch_all($conn, "
            SELECT workman_id, COUNT(DISTINCT DATE(check_in)) AS present_days 
            FROM attendance 
            WHERE workman_id IN (
                SELECT id FROM workmen WHERE contractor_id = ?
            ) AND DATE(check_in) BETWEEN ? AND ?
              AND LOWER(status) IN ('present', 'p')
            GROUP BY workman_id
        ", 'iss', [$contractorId, $start, $end]);
        
        foreach ($attQuery as $row) {
            $attendance[(int)$row['workman_id']] = (int)$row['present_days'];
        }
    }

    // Parse ESI PDF if uploaded
    $challanWorkers = [];
    $pdfFile = __DIR__ . '/../../uploads/compliance/' . $sub['file_path'];
    if (!empty($sub['file_path']) && is_file($pdfFile)) {
        $challanWorkers = welfare_parse_esi_pdf_workers($pdfFile);
    }

    // Track which challan workers were matched
    $matchedChallanWorkers = [];

    // Map expected workers
    foreach ($workers as $w) {
        $esiNum = trim((string)$w['esic_number']);
        $days = $attendance[(int)$w['id']] ?? 0;
        $rate = (float)($w['certified_wage_rate'] ?? 0);
        $wages = $days * $rate;
        $reason = $days > 0 ? 11 : 0;

        $chDays = 0;
        $chWages = 0.0;
        $status = 'Match';
        $details = '';

        if ($esiNum !== '' && isset($challanWorkers[$esiNum])) {
            $ch = $challanWorkers[$esiNum];
            $chDays = (int)$ch['days'];
            $chWages = (float)$ch['wages'];
            $matchedChallanWorkers[$esiNum] = true;

            $dayDiff = $days !== $chDays;
            $wageDiff = abs($wages - $chWages) > 1.0;

            if ($dayDiff && $wageDiff) {
                $status = 'Mismatch';
                $details = "Days Mismatch (MR: $days, Challan: $chDays) & Wages Mismatch (MR: " . number_format($wages, 2) . ", Challan: " . number_format($chWages, 2) . ")";
            } elseif ($dayDiff) {
                $status = 'Days Mismatch';
                $details = "Days Mismatch (MR: $days, Challan: $chDays)";
            } elseif ($wageDiff) {
                $status = 'Wages Mismatch';
                $details = "Wages Mismatch (MR: " . number_format($wages, 2) . ", Challan: " . number_format($chWages, 2) . ")";
            }
        } else {
            $status = 'Missing in Challan';
            $details = "Worker enrolled in Muster Roll but not present in ESI Challan PDF.";
        }

        $rowItem = [
            'vendor_code' => $sub['vendor_code'],
            'contractor_name' => $sub['contractor_name'],
            'month_year' => $sub['month_year'],
            'esi_number' => $esiNum,
            'name' => $w['name'],
            'mr_days' => $days,
            'mr_wages' => $wages,
            'ch_days' => $chDays,
            'ch_wages' => $chWages,
            'reason' => $reason,
            'status' => $status,
            'details' => $details
        ];

        $reportData[] = $rowItem;

        // If mismatch, record in Report 2
        if ($status !== 'Match') {
            $mismatchReport['worker_errors'][] = [
                'vendor_code' => $sub['vendor_code'],
                'contractor_name' => $sub['contractor_name'],
                'month_year' => $sub['month_year'],
                'esi_number' => $esiNum,
                'name' => $w['name'],
                'category' => $status,
                'mr_val' => ($status === 'Missing in Challan' || $status === 'Wages Mismatch') ? "₹" . number_format($wages, 2) : "$days Days",
                'ch_val' => ($status === 'Missing in Challan' || $status === 'Wages Mismatch') ? "₹" . number_format($chWages, 2) : "$chDays Days",
                'difference' => $details
            ];
        }
    }

    // Check for excess workers (in challan but not in our workmen db list)
    foreach ($challanWorkers as $esiNum => $ch) {
        if (!isset($matchedChallanWorkers[$esiNum])) {
            $rowItem = [
                'vendor_code' => $sub['vendor_code'],
                'contractor_name' => $sub['contractor_name'],
                'month_year' => $sub['month_year'],
                'esi_number' => $esiNum,
                'name' => $ch['name'],
                'mr_days' => 0,
                'mr_wages' => 0.0,
                'ch_days' => (int)$ch['days'],
                'ch_wages' => (float)$ch['wages'],
                'reason' => 0,
                'status' => 'Excess in Challan',
                'details' => "Worker in ESI Challan PDF but not found/active in CLMS Workmen Database."
            ];

            $reportData[] = $rowItem;

            $mismatchReport['worker_errors'][] = [
                'vendor_code' => $sub['vendor_code'],
                'contractor_name' => $sub['contractor_name'],
                'month_year' => $sub['month_year'],
                'esi_number' => $esiNum,
                'name' => $ch['name'],
                'category' => 'Excess in Challan',
                'mr_val' => 'N/A',
                'ch_val' => (int)$ch['days'] . " Days, ₹" . number_format($ch['wages'], 2),
                'difference' => $rowItem['details']
            ];
        }
    }

    // Record vendor-level summary errors in Report 2
    if (!empty($sub['validation_errors'])) {
        $mismatchReport['vendor_errors'][] = [
            'vendor_code' => $sub['vendor_code'],
            'contractor_name' => $sub['contractor_name'],
            'month_year' => $sub['month_year'],
            'errors' => $sub['validation_errors']
        ];
    }
}

// Format month helper
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June',
    '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
function formatMonthYearStr($my, $months) {
    $parts = explode('-', $my);
    if (count($parts) === 2) {
        return ($months[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
    }
    return $my;
}

// Generate Excel filename
$dateStr = date('Ymd_His');
$filename = ($reportType === 1) 
    ? "ESI_Vendor_Workers_Details_$dateStr.xls" 
    : "ESI_Compliance_Mismatch_Details_$dateStr.xls";

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Pragma: no-cache');
header('Expires: 0');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name><?= $reportType === 1 ? 'Worker Details' : 'Mismatch Summary' ?></x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            padding: 10px 0;
        }
        .header-subtitle {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 10px;
        }
        .th-blue {
            background-color: #3b82f6;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }
        .th-red {
            background-color: #ef4444;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }
        .th-gray {
            background-color: #f3f4f6;
            color: #1f2937;
            font-weight: bold;
            text-align: center;
        }
        .td-center {
            text-align: center;
        }
        .td-right {
            text-align: right;
        }
        .badge-err {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
        }
    </style>
</head>
<body>

<?php if ($reportType === 1): ?>
    <!-- REPORT 1: Vendor Workers Details -->
    <table>
        <tr>
            <td colspan="12" class="header-title">ESI Vendor Workers Details Report</td>
        </tr>
        <tr>
            <td colspan="12" class="header-subtitle">Generated on: <?= date('Y-m-d H:i:s') ?> | Exporting details for selected ESI compliance submissions.</td>
        </tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th class="th-blue" style="width: 50px;">S.No</th>
                <th class="th-blue" style="width: 120px;">Vendor Code</th>
                <th class="th-blue" style="width: 200px;">Vendor Name</th>
                <th class="th-blue" style="width: 120px;">Wage Month</th>
                <th class="th-blue" style="width: 150px;">ESI Number</th>
                <th class="th-blue" style="width: 200px;">Worker Name</th>
                <th class="th-blue" style="width: 120px;">MR Days (Expected)</th>
                <th class="th-blue" style="width: 140px;">MR Wages (Expected)</th>
                <th class="th-blue" style="width: 120px;">Challan Days (Actual)</th>
                <th class="th-blue" style="width: 140px;">Challan Wages (Actual)</th>
                <th class="th-blue" style="width: 100px;">Reason Code</th>
                <th class="th-blue" style="width: 150px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sno = 1;
            foreach ($reportData as $row): 
                $statusColor = $row['status'] === 'Match' ? 'badge-success' : 'badge-err';
            ?>
                <tr>
                    <td class="td-center"><?= $sno++ ?></td>
                    <td style="vnd.ms-excel.numberformat:@"><?= htmlspecialchars($row['vendor_code']) ?></td>
                    <td><?= htmlspecialchars($row['contractor_name']) ?></td>
                    <td class="td-center"><?= htmlspecialchars(formatMonthYearStr($row['month_year'], $months)) ?></td>
                    <td style="vnd.ms-excel.numberformat:@"><?= htmlspecialchars($row['esi_number']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td class="td-center"><?= $row['mr_days'] ?></td>
                    <td class="td-right"><?= number_format($row['mr_wages'], 2, '.', '') ?></td>
                    <td class="td-center"><?= $row['ch_days'] ?></td>
                    <td class="td-right"><?= number_format($row['ch_wages'], 2, '.', '') ?></td>
                    <td class="td-center"><?= $row['reason'] ?></td>
                    <td class="td-center <?= $statusColor ?>"><?= htmlspecialchars($row['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>
    <!-- REPORT 2: Vendor ESI Compliance Mismatches -->
    <table>
        <tr>
            <td colspan="8" class="header-title" style="color: #b91c1c;">ESI Compliance Mismatches Report</td>
        </tr>
        <tr>
            <td colspan="8" class="header-subtitle">Generated on: <?= date('Y-m-d H:i:s') ?> | Displays summary and detailed list of discrepancies found between Muster Roll and ESI Challans.</td>
        </tr>
    </table>

    <h3>1. Vendor Level Summary Mismatches</h3>
    <table border="1">
        <thead>
            <tr>
                <th class="th-red" style="width: 50px;">S.No</th>
                <th class="th-red" style="width: 120px;">Vendor Code</th>
                <th class="th-red" style="width: 200px;">Vendor Name</th>
                <th class="th-red" style="width: 120px;">Wage Month</th>
                <th class="th-red" style="width: 450px;">Validation Errors / Discrepancies</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mismatchReport['vendor_errors'])): ?>
                <tr>
                    <td colspan="5" class="td-center" style="padding: 10px; color: #15803d; font-weight: bold; background-color: #dcfce7;">
                        No vendor-level summary mismatches found.
                    </td>
                </tr>
            <?php else: 
                $sno = 1;
                foreach ($mismatchReport['vendor_errors'] as $verr):
            ?>
                <tr>
                    <td class="td-center"><?= $sno++ ?></td>
                    <td style="vnd.ms-excel.numberformat:@"><?= htmlspecialchars($verr['vendor_code']) ?></td>
                    <td><?= htmlspecialchars($verr['contractor_name']) ?></td>
                    <td class="td-center"><?= htmlspecialchars(formatMonthYearStr($verr['month_year'], $months)) ?></td>
                    <td style="white-space: pre-wrap; color: #b91c1c; font-weight: 500; font-family: monospace;">
                        <?= htmlspecialchars($verr['errors']) ?>
                    </td>
                </tr>
            <?php 
                endforeach;
            endif; 
            ?>
        </tbody>
    </table>

    <br/>
    <h3>2. Worker Level Mismatch Details</h3>
    <table border="1">
        <thead>
            <tr>
                <th class="th-red" style="width: 50px;">S.No</th>
                <th class="th-red" style="width: 120px;">Vendor Code</th>
                <th class="th-red" style="width: 180px;">Vendor Name</th>
                <th class="th-red" style="width: 120px;">Wage Month</th>
                <th class="th-red" style="width: 150px;">ESI Number</th>
                <th class="th-red" style="width: 180px;">Worker Name</th>
                <th class="th-red" style="width: 150px;">Mismatch Category</th>
                <th class="th-gray" style="width: 120px;">Muster Roll Value</th>
                <th class="th-gray" style="width: 120px;">ESI Challan Value</th>
                <th class="th-red" style="width: 300px;">Difference Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mismatchReport['worker_errors'])): ?>
                <tr>
                    <td colspan="10" class="td-center" style="padding: 10px; color: #15803d; font-weight: bold; background-color: #dcfce7;">
                        No worker-level ESI mismatches found.
                    </td>
                </tr>
            <?php else: 
                $sno = 1;
                foreach ($mismatchReport['worker_errors'] as $werr):
            ?>
                <tr>
                    <td class="td-center"><?= $sno++ ?></td>
                    <td style="vnd.ms-excel.numberformat:@"><?= htmlspecialchars($werr['vendor_code']) ?></td>
                    <td><?= htmlspecialchars($werr['contractor_name']) ?></td>
                    <td class="td-center"><?= htmlspecialchars(formatMonthYearStr($werr['month_year'], $months)) ?></td>
                    <td style="vnd.ms-excel.numberformat:@"><?= htmlspecialchars($werr['esi_number']) ?></td>
                    <td><?= htmlspecialchars($werr['name']) ?></td>
                    <td class="td-center" style="background-color: #fef2f2; color: #991b1b; font-weight: bold;"><?= htmlspecialchars($werr['category']) ?></td>
                    <td class="td-center"><?= htmlspecialchars($werr['mr_val']) ?></td>
                    <td class="td-center"><?= htmlspecialchars($werr['ch_val']) ?></td>
                    <td style="color: #991b1b;"><?= htmlspecialchars($werr['difference']) ?></td>
                </tr>
            <?php 
                endforeach;
            endif; 
            ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
