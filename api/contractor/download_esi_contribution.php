<?php
// api/contractor/download_esi_contribution.php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'contractor') {
    http_response_code(403);
    die("Access denied. Contractor authentication required.");
}

ensureComplianceSchema($conn);

$userId = (int)($_SESSION['user_id'] ?? 0);
$contractor = db_single($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE user_id = ? LIMIT 1", 'i', [$userId]);
if (!$contractor) {
    http_response_code(400);
    die("Contractor record not found.");
}
$contractorId = (int)$contractor['id'];

$monthYear = trim($_GET['month_year'] ?? date('Y-m'));
list($monthName, $year, $monthYear) = complianceMonthParts($monthYear);

$start = $monthYear . '-01';
$end = date('Y-m-t', strtotime($start));

// Fetch active workmen
$workers = db_fetch_all($conn, "
    SELECT w.id, w.name, w.esic_number, w.certified_wage_rate 
    FROM workmen w
    WHERE w.contractor_id = ? AND w.status NOT IN ('draft', 'pending', 'inactive', 'blocked')
    ORDER BY w.name ASC
", 'i', [$contractorId]);

// Fetch attendance counts for each workman
$attendance = [];
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

// Format filename
$filename = "ESI_Monthly_Contribution_" . str_replace('-', '_', $monthYear) . ".xls";

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
                    <x:Name>ESI Monthly Contribution</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
</head>
<body>
    <table border="1">
        <thead>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <th style="width: 150px;">ESI Number</th>
                <th style="width: 250px;">Full Name</th>
                <th style="width: 100px; text-align: center;">No of days</th>
                <th style="width: 150px; text-align: right;">Total monthly wages</th>
                <th style="width: 150px; text-align: center;">Reason code for zero wage days</th>
                <th style="width: 150px;">Last working Day</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workers as $w): 
                $days = $attendance[(int)$w['id']] ?? 0;
                $rate = (float)($w['certified_wage_rate'] ?? 0);
                $wages = $days * $rate;
                $reason = $days > 0 ? 11 : 0;
            ?>
                <tr>
                    <td style="vnd.ms-excel.numberformat:@"><?= htmlspecialchars($w['esic_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars($w['name'] ?? '') ?></td>
                    <td style="text-align: center;"><?= $days ?></td>
                    <td style="text-align: right;"><?= number_format($wages, 2, '.', '') ?></td>
                    <td style="text-align: center;"><?= $reason ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
