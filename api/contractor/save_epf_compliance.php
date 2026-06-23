<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/customer_portal_context.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

function respondEPF($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests are allowed");
    }

    ensureComplianceSchema($conn);
    clms_get_portal_contractor($conn);

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]);
    if (!$contractor) {
        throw new Exception("Contractor registration not found");
    }
    $contractorId = (int)$contractor['id'];

    $monthYear = $_POST['contribution_month'] ?? date('Y-m', strtotime('-1 month'));
    list($monthName, $yearVal, $monthYear) = complianceMonthParts($monthYear);

    if (empty($_FILES['ecr_file']) || $_FILES['ecr_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("ECR file upload is required");
    }

    // Upload ECR file
    $fileKey = 'ecr_file';
    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['txt', 'csv'], true)) {
        throw new Exception("Invalid file type for ECR file. Only .txt and .csv are allowed.");
    }

    $dir = __DIR__ . '/../../uploads/compliance/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $ecrFilename = $contractorId . '_epf_' . $monthYear . '_ecr_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir . $ecrFilename)) {
        throw new Exception("Failed to upload ECR file");
    }

    // Parse ECR File
    $filePath = $dir . $ecrFilename;
    $fileData = file_get_contents($filePath);
    if ($fileData === false) {
        throw new Exception("Failed to read uploaded ECR file");
    }

    $lines = explode("\n", str_replace("\r", "", $fileData));
    $parsedRecords = [];
    $totalEPFAmount = 0.0;
    $totalWagesECR = 0.0;
    $totalEPFContribution = 0.0;
    $totalEPSContribution = 0.0;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        $parts = [];
        if (strpos($line, '#~#') !== false) {
            $parts = explode('#~#', $line);
        } else {
            $parts = str_getcsv($line);
        }

        // Must have at least UAN, Name, and EPF wages
        if (count($parts) < 3) continue;

        $uan = trim($parts[0]);
        $name = trim($parts[1]);
        $gross = (float)($parts[2] ?? 0.0);
        $epfWages = (float)($parts[3] ?? 0.0);
        $epsWages = (float)($parts[4] ?? 0.0);
        $edliWages = (float)($parts[5] ?? 0.0);
        $epfContrib = (float)($parts[6] ?? 0.0);
        $epsContrib = (float)($parts[7] ?? 0.0);
        $diffContrib = (float)($parts[8] ?? 0.0);
        $ncp = (int)($parts[9] ?? 0);
        $refund = (float)($parts[10] ?? 0.0);

        $parsedRecords[] = [
            'uan' => $uan,
            'name' => $name,
            'gross' => $gross,
            'epf_wages' => $epfWages,
            'eps_wages' => $epsWages,
            'edli_wages' => $edliWages,
            'epf_contrib' => $epfContrib,
            'eps_contrib' => $epsContrib,
            'diff' => $diffContrib,
            'ncp' => $ncp,
            'refund' => $refund
        ];

        $totalWagesECR += $epfWages;
        $totalEPFContribution += $epfContrib;
        $totalEPSContribution += $epsContrib;
        $totalEPFAmount += ($epfContrib + $epsContrib + $diffContrib);
    }

    if (empty($parsedRecords)) {
        throw new Exception("No valid worker records found in the ECR file.");
    }

    // Perform compliance calculation and verification
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));

    // Fetch active workmen
    $workers = db_fetch_all($conn, "
        SELECT id, name, uan_number, pf_no, certified_wage_rate 
        FROM workmen 
        WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
        ORDER BY name ASC
    ", 'i', [$contractorId]);

    // Fetch attendance days counts
    $attendance = [];
    $totalAttDays = 0;
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
            $totalAttDays += (int)$row['present_days'];
        }
    }

    $errors = [];
    $wageTotalMuster = 0.0;
    $expectedWorkmenMap = [];

    foreach ($workers as $w) {
        $wId = (int)$w['id'];
        $days = $attendance[$wId] ?? 0;
        $rate = (float)($w['certified_wage_rate'] ?? 0);
        $mrWage = $days * $rate; // Muster Roll Wage = Total Days Worked x Wage Rate
        $wageTotalMuster += $mrWage;

        $uanClean = trim((string)$w['uan_number']);
        if ($uanClean === '') {
            $uanClean = trim((string)$w['pf_no']);
        }

        if ($uanClean !== '') {
            $expectedWorkmenMap[$uanClean] = [
                'name' => $w['name'],
                'days' => $days,
                'wages' => $mrWage
            ];
        }
    }

    // Verify row-by-row
    $matchedECRRecords = [];
    foreach ($expectedWorkmenMap as $uan => $exp) {
        // Find UAN in parsed ECR records
        $ecrRecord = null;
        foreach ($parsedRecords as $rec) {
            if ($rec['uan'] === $uan) {
                $ecrRecord = $rec;
                break;
            }
        }

        if ($ecrRecord) {
            $matchedECRRecords[$uan] = true;
            $ecrWage = (float)$ecrRecord['epf_wages'];
            if (abs($ecrWage - $exp['wages']) > 1.0 && $ecrWage < $exp['wages']) {
                $errors[] = "Wage mismatch for worker: " . $exp['name'] . " (UAN: $uan) — Muster Roll: ₹" . number_format($exp['wages'], 2) . ", ECR: ₹" . number_format($ecrWage, 2) . " (Underpaid).";
            }
        } else {
            $errors[] = "Worker excluded from ECR statement: " . $exp['name'] . " (UAN: $uan) — Present in Muster Roll with ₹" . number_format($exp['wages'], 2) . " wages.";
        }
    }

    // Check for excess ECR workers
    foreach ($parsedRecords as $rec) {
        if (!isset($expectedWorkmenMap[$rec['uan']])) {
            // Unregistered or excess worker (NA status case)
            // Just log as info or ignore in strict compliance errors since it's NA, not a violation
        }
    }

    $validationStatus = empty($errors) ? 'passed' : 'mismatch';
    $errorsText = implode("\n", $errors);

    // Save/Update Compliance
    $conn->begin_transaction();

    $existing = db_single($conn, "SELECT id FROM compliance WHERE contractor_id = ? AND month_year = ? AND type = 'epf' LIMIT 1", 'iss', [$contractorId, $monthYear]);
    if ($existing) {
        $complianceId = (int)$existing['id'];
    } else {
        db_execute(
            $conn,
            "INSERT INTO compliance (contractor_id, month, year, month_year, type, status, uploaded_at)
             VALUES (?, ?, ?, ?, 'epf', 'pending', NOW())",
            'isis',
            [$contractorId, $monthName, $yearVal, $monthYear]
        );
        $complianceId = (int)$conn->insert_id;
    }

    db_execute(
        $conn,
        "UPDATE compliance SET
            challan_number = NULL, amount = ?, file_path = NULL,
            challan_worker_count = ?, attendance_count = ?,
            worker_count = ?, attendance_days = ?, wage_total = ?,
            esi_amount = 0.00, pf_amount = ?, klwf_amount = 0.00,
            esi_file = NULL, pf_file = NULL, klwf_file = NULL,
            validation_status = ?, validation_errors = ?,
            status = 'pending', remarks = NULL, updated_at = NOW()
         WHERE id = ?",
        'diiiiiddssi',
        [
            $totalEPFAmount, count($parsedRecords), $totalAttDays,
            count($workers), $totalAttDays, $wageTotalMuster,
            $totalEPFContribution,
            $validationStatus, $errorsText,
            $complianceId
        ]
    );

    // Save/Update compliance_epf
    db_execute($conn, "DELETE FROM compliance_epf WHERE compliance_id = ?", 'i', [$complianceId]);
    db_execute(
        $conn,
        "INSERT INTO compliance_epf (compliance_id, ecr_no, challan_date, members_count, total_wages, epf_contribution, eps_contribution, total_pf, file_path, ecr_file_path)
         VALUES (?, NULL, NULL, ?, ?, ?, ?, ?, NULL, ?)",
        'iidddds',
        [
            $complianceId, count($parsedRecords), $totalWagesECR,
            $totalEPFContribution, $totalEPSContribution, $totalEPFAmount,
            $ecrFilename
        ]
    );

    // Populate parsed ECR records
    db_execute($conn, "DELETE FROM compliance_epf_records WHERE compliance_id = ?", 'i', [$complianceId]);
    foreach ($parsedRecords as $rec) {
        db_execute(
            $conn,
            "INSERT INTO compliance_epf_records (compliance_id, uan, member_name, gross_wages, epf_wages, eps_wages, edli_wages, epf_contribution, eps_contribution, diff_contribution, ncp_days, refund_advances)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'issdddddddid',
            [
                $complianceId, $rec['uan'], $rec['name'], $rec['gross'],
                $rec['epf_wages'], $rec['eps_wages'], $rec['edli_wages'],
                $rec['epf_contrib'], $rec['eps_contrib'], $rec['diff'],
                $rec['ncp'], $rec['refund']
            ]
        );
    }

    db_execute($conn, "INSERT INTO compliance_logs (compliance_id, action, user_id, remarks) VALUES (?, 'submitted', ?, 'EPF ECR compliance submitted')", 'iis', [$complianceId, $userId]);
    db_execute($conn, "UPDATE contractors SET compliance_status='pending' WHERE id=?", 'i', [$contractorId]);

    $conn->commit();

    respondEPF(true, "ECR file parsed and verified.", [
        'compliance_id' => $complianceId,
        'validation_status' => $validationStatus,
        'errors' => $errors
    ]);

} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    respondEPF(false, $e->getMessage());
}
