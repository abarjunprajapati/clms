<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/customer_portal_context.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

checkAuth(['contractor']);

try {
    ensureComplianceSchema($conn);
    clms_get_portal_contractor($conn);

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]);
    if (!$contractor) {
        throw new Exception("Contractor registration not found.");
    }
    $contractorId = (int)$contractor['id'];

    $monthYear = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');
    $format = isset($_GET['format']) ? trim($_GET['format']) : 'text';

    list($monthName, $yearVal, $monthYear) = complianceMonthParts($monthYear);
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));
    $totalCalendarDays = (int)date('t', strtotime($start));

    // Fetch active workmen
    $workers = db_fetch_all($conn, "
        SELECT id, name, uan_number, pf_no, certified_wage_rate 
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

    $records = [];
    foreach ($workers as $w) {
        $wId = (int)$w['id'];
        $presentDays = $attendance[$wId] ?? 0;
        $rate = (float)($w['certified_wage_rate'] ?? 0);
        $grossWages = $presentDays * $rate;

        $epfWages = $grossWages;
        $epsWages = min(15000.00, $grossWages);
        $edliWages = min(15000.00, $grossWages);

        $epfContribution = round($epfWages * 0.12, 0);
        $epsContribution = round($epsWages * 0.0833, 0);
        $diffContribution = $epfContribution - $epsContribution;
        
        $ncpDays = max(0, $totalCalendarDays - $presentDays);
        $refund = 0.00;

        $uan = trim((string)$w['uan_number']);
        if ($uan === '') {
            $uan = trim((string)$w['pf_no']);
        }

        $records[] = [
            'uan' => $uan,
            'name' => trim($w['name']),
            'gross' => number_format($grossWages, 2, '.', ''),
            'epf_wages' => number_format($epfWages, 2, '.', ''),
            'eps_wages' => number_format($epsWages, 2, '.', ''),
            'edli_wages' => number_format($edliWages, 2, '.', ''),
            'epf_contrib' => number_format($epfContribution, 2, '.', ''),
            'eps_contrib' => number_format($epsContribution, 2, '.', ''),
            'diff' => number_format($diffContribution, 2, '.', ''),
            'ncp' => $ncpDays,
            'refund' => number_format($refund, 2, '.', '')
        ];
    }

    if ($format === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="prefilled_ecr_' . $monthYear . '.csv"');
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'UAN', 'Member Name', 'Gross Wages', 'EPF Wages', 'EPS Wages', 
            'EDLI Wages', 'EPF Contribution', 'EPS Contribution', 'Difference', 
            'NCP Days', 'Refund'
        ]);
        
        foreach ($records as $r) {
            fputcsv($output, [
                $r['uan'], $r['name'], $r['gross'], $r['epf_wages'], $r['eps_wages'],
                $r['edli_wages'], $r['epf_contrib'], $r['eps_contrib'], $r['diff'],
                $r['ncp'], $r['refund']
            ]);
        }
        fclose($output);
        exit;
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="prefilled_ecr_' . $monthYear . '.txt"');
        
        foreach ($records as $r) {
            echo implode('#~#', [
                $r['uan'], $r['name'], $r['gross'], $r['epf_wages'], $r['eps_wages'],
                $r['edli_wages'], $r['epf_contrib'], $r['eps_contrib'], $r['diff'],
                $r['ncp'], $r['refund']
            ]) . "\r\n";
        }
        exit;
    }

} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
