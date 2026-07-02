<?php
// api/welfare/export_epf_compliance.php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

// Check permissions
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);

ensureComplianceSchema($conn);

$complianceIdsStr = isset($_GET['compliance_ids']) ? trim($_GET['compliance_ids']) : '';
$complianceIds = array_filter(array_map('intval', explode(',', $complianceIdsStr)));
if (empty($complianceIds)) {
    die("Error: No compliance records selected.");
}

$idsImploded = implode(',', $complianceIds);
$sql = "
    SELECT c.*, con.vendor_code, con.contractor_name, con.company_name
    FROM compliance c
    JOIN contractors con ON c.contractor_id = con.id
    WHERE c.id IN ($idsImploded) AND c.type = 'epf'
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
    die("Error: No EPF compliance submissions found for the selected IDs.");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="EPF_Compliance_Mismatch_Report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// CSV Headers matching Page 48 columns
fputcsv($output, [
    'ACC NO', 'Name of Worker', 'Vendor Code', 
    'Vendor name', 'Musterroll wage (Total days x Rate)', 'ECR EPF wage', 
    'Status'
]);

foreach ($submissions as $sub) {
    $cId = (int)$sub['contractor_id'];
    $monthYear = $sub['month_year'];
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));
    
    // Fetch active workmen
    $workers = db_fetch_all($conn, "
        SELECT id, name, uan_number, pf_no, acc_number, certified_wage_rate
        FROM workmen
        WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
        ORDER BY name ASC
    ", 'i', [$cId]);

    // Fetch attendance days counts
    $attendance = [];
    if (!empty($workers)) {
        $attQuery = db_fetch_all($conn, "
            SELECT workman_id, COUNT(DISTINCT DATE(check_in)) AS present_days
            FROM attendance
            WHERE workman_id IN (SELECT id FROM workmen WHERE contractor_id = ?)
              AND DATE(check_in) BETWEEN ? AND ?
              AND LOWER(status) IN ('present', 'p')
            GROUP BY workman_id
        ", 'iss', [$cId, $start, $end]);
        foreach ($attQuery as $row) {
            $attendance[(int)$row['workman_id']] = (int)$row['present_days'];
        }
    }

    // Fetch ECR records
    $ecrRecords = db_fetch_all($conn, "
        SELECT * FROM compliance_epf_records WHERE compliance_id = ?
    ", 'i', [(int)$sub['id']]);

    $matchedUans = [];

    // Map workmen
    foreach ($workers as $w) {
        $uan = trim((string)$w['uan_number']);
        if ($uan === '') {
            $uan = trim((string)$w['pf_no']);
        }

        $days = $attendance[(int)$w['id']] ?? 0;
        $rate = (float)($w['certified_wage_rate'] ?? 0);
        $mrWage = $days * $rate; // Muster Roll Wage = Total Days Worked x Wage Rate

        // Find ECR record
        $ecrWage = 0.00;
        $status = 'Complied';
        
        $found = false;
        foreach ($ecrRecords as $rec) {
            if ($rec['uan'] === $uan) {
                $ecrWage = (float)$rec['epf_wages'];
                $matchedUans[$rec['uan']] = true;
                $found = true;
                break;
            }
        }

        if ($found) {
            if (abs($ecrWage - $mrWage) > 1.0 && $ecrWage < $mrWage) {
                $status = 'Not Complied';
            }
        } else {
            $status = 'Not Complied';
        }

        fputcsv($output, [
            $w['acc_number'] ?: ('W-' . $w['id']),
            $w['name'],
            $sub['vendor_code'],
            $sub['contractor_name'], // Vendor Name / Company Name
            number_format($mrWage, 2, '.', ''),
            number_format($ecrWage, 2, '.', ''),
            $status
        ]);
    }

    // Add excess ECR records
    foreach ($ecrRecords as $rec) {
        if (!isset($matchedUans[$rec['uan']])) {
            fputcsv($output, [
                'N/A',
                $rec['member_name'],
                $sub['vendor_code'],
                $sub['contractor_name'],
                '0.00',
                number_format((float)$rec['epf_wages'], 2, '.', ''),
                'NA'
            ]);
        }
    }
}

fclose($output);
exit;
