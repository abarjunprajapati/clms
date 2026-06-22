<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/customer_portal_context.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

function respondCompliance($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

function uploadComplianceFile($key, $contractorId, $type, $monthYear, $allowed) {
    if (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new Exception("Invalid file type for $key");
    }

    $dir = __DIR__ . '/../../uploads/compliance/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $filename = $contractorId . '_' . $type . '_' . $monthYear . '_' . $key . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$key]['tmp_name'], $dir . $filename)) {
        throw new Exception("Failed to upload $key");
    }
    return $filename;
}

function clms_extract_text_from_pdf($filePath) {
    if (!$filePath || !is_file($filePath)) return '';
    $content = file_get_contents($filePath);
    if (!$content) return '';

    $text = '';
    // Find all stream blocks
    preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/is', $content, $matches);
    foreach ($matches[1] as $stream) {
        // Try to decompress
        $decompressed = @gzuncompress($stream);
        if ($decompressed === false) {
            // Try with small offsets (1 to 10 bytes) due to different newline characters
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

    // Fallback search in raw content if not compressed or text commands are direct
    preg_match_all('/\((.*?)\)/s', $content, $rawMatches);
    foreach ($rawMatches[1] as $r) {
        if (strlen($r) > 1 && !preg_match('/^[\/a-zA-Z]/', $r)) {
            $text .= $r . " ";
        }
    }

    return $text;
}

function clms_parse_esi_pdf_workers($filePath) {
    $text = clms_extract_text_from_pdf($filePath);
    // Normalize spaces
    $text = preg_replace('/\s+/', ' ', $text);

    // Find ESI Numbers (10 or 17 digits)
    preg_match_all('/\b\d{10}\b|\b\d{17}\b/', $text, $matches, PREG_OFFSET_CAPTURE);

    $records = [];
    $numMatches = count($matches[0]);
    for ($i = 0; $i < $numMatches; $i++) {
        $esiNum = $matches[0][$i][0];
        $offset = $matches[0][$i][1];

        // Segment between this ESI number and the next
        $startPos = $offset + strlen($esiNum);
        if ($i < $numMatches - 1) {
            $length = $matches[0][$i+1][1] - $startPos;
            $segment = substr($text, $startPos, $length);
        } else {
            $segment = substr($text, $startPos, 250);
        }

        $segment = trim($segment);

        // Find numbers (days and wages)
        preg_match_all('/\b\d+\.\d+\b|\b\d+\b/', $segment, $numMatches2);

        $wages = 0.0;
        $days = 0;
        $foundWages = false;
        $foundDays = false;

        // Try to identify wages (decimal) and days (integer between 0 and 31)
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

        // Parse integers
        foreach ($integers as $val) {
            if ($val >= 0 && $val <= 31 && !$foundDays) {
                $days = $val;
                $foundDays = true;
            } elseif ($val > 31 && !$foundWages) {
                $wages = (float)$val;
                $foundWages = true;
            }
        }

        // Extract name
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

function computeComplianceValidation($conn, $contractorId, $monthYear, $type, $payload, $tmpFilePath = null) {
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));

    // Fetch active workmen
    $workers = db_fetch_all($conn, "
        SELECT id, name, esic_number, certified_wage_rate 
        FROM workmen 
        WHERE contractor_id = ? AND status NOT IN ('draft', 'pending', 'inactive', 'blocked')
        ORDER BY name ASC
    ", 'i', [$contractorId]);

    $workerCount = count($workers);

    // Fetch attendance days counts from approved/submitted Muster Roll records
    $attendance = [];
    $totalAttDays = 0;
    if ($workerCount > 0) {
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

    $wageTotal = 0.0;
    $expectedWorkmenMap = [];
    foreach ($workers as $w) {
        $wId = (int)$w['id'];
        $days = $attendance[$wId] ?? 0;
        $rate = (float)($w['certified_wage_rate'] ?? 0);
        $workerWages = $days * $rate;
        $wageTotal += $workerWages;

        $esiClean = trim((string)$w['esic_number']);
        if ($esiClean !== '') {
            $expectedWorkmenMap[$esiClean] = [
                'name' => $w['name'],
                'days' => $days,
                'wages' => $workerWages
            ];
        }
    }

    $errors = [];
    if ($workerCount <= 0) {
        $errors[] = 'No enrolled active workers found for this contractor.';
    }

    if ($type === 'esi') {
        $covered = (int)($payload['employees_count'] ?? 0);
        $gross = (float)($payload['gross_wages'] ?? 0);
        $expected = round($gross * 0.04, 2);
        $actual = (float)($payload['total_contribution'] ?? 0);
        
        if ($covered !== $workerCount) {
            $errors[] = "ESI employee count ($covered) does not match enrolled active workers ($workerCount).";
        }
        if (abs($gross - $wageTotal) > 1.0) {
            $errors[] = "ESI Gross Wages (₹" . number_format($gross, 2) . ") does not match calculated wages (₹" . number_format($wageTotal, 2) . ") based on workmen enrollment & attendance.";
        }
        if (abs($expected - $actual) > 1) {
            $errors[] = "ESI contribution mismatch. Expected about ₹" . number_format($expected, 2) . ".";
        }
        if ($totalAttDays === 0) {
            $errors[] = 'No approved Muster Roll / attendance records found for this month.';
        }

        // --- ROW-BY-ROW ESI CHALLAN PDF PARSING & VALIDATION ---
        if ($tmpFilePath && is_file($tmpFilePath)) {
            $challanWorkers = clms_parse_esi_pdf_workers($tmpFilePath);
            if (empty($challanWorkers)) {
                $errors[] = "Warning: Could not extract worker details from the uploaded ESI Challan PDF. Please ensure it is a digital contribution statement from the ESI portal.";
            } else {
                // Check expected workers against challan
                foreach ($expectedWorkmenMap as $esiNum => $exp) {
                    if (!isset($challanWorkers[$esiNum])) {
                        $errors[] = "Worker not in ESI challan: " . $exp['name'] . " (ESI: $esiNum)";
                    } else {
                        $ch = $challanWorkers[$esiNum];
                        if ((int)$exp['days'] !== (int)$ch['days']) {
                            $errors[] = "Days mismatch for " . $exp['name'] . " (ESI: $esiNum): Muster Roll has " . $exp['days'] . " days, Challan has " . $ch['days'] . " days.";
                        }
                        if (abs((float)$exp['wages'] - (float)$ch['wages']) > 1.0) {
                            $errors[] = "Wages mismatch for " . $exp['name'] . " (ESI: $esiNum): Muster Roll has ₹" . number_format($exp['wages'], 2) . ", Challan has ₹" . number_format($ch['wages'], 2) . ".";
                        }
                    }
                }

                // Check for excess workers in challan
                foreach ($challanWorkers as $esiNum => $ch) {
                    if (!isset($expectedWorkmenMap[$esiNum])) {
                        $errors[] = "Worker excess in ESI challan (not in muster roll): " . $ch['name'] . " (ESI: $esiNum)";
                    }
                }
            }
        }
    }

    if ($type === 'pf') {
        $members = (int)($payload['members_count'] ?? 0);
        $wages = (float)($payload['total_wages'] ?? 0);
        $expected = round($wages * 0.24, 2);
        $actual = (float)($payload['total_pf'] ?? 0);
        if ($members !== $workerCount) $errors[] = "PF member count ($members) does not match enrolled workers ($workerCount).";
        if (abs($wages - $wageTotal) > 1.0) $errors[] = "PF Total Wages (₹" . number_format($wages, 2) . ") does not match calculated wages (₹" . number_format($wageTotal, 2) . ") based on workmen enrollment & attendance.";
        if (abs($expected - $actual) > 1) $errors[] = "PF contribution mismatch. Expected about ₹" . number_format($expected, 2) . ".";
    }

    if ($type === 'klwf') {
        $count = (int)($payload['worker_count'] ?? 0);
        $expected = $count * 200;
        $actual = (float)($payload['total_amount'] ?? 0);
        if ($count !== $workerCount) $errors[] = "KLWF worker count ($count) does not match enrolled workers ($workerCount).";
        if (abs($expected - $actual) > 1) $errors[] = "KLWF amount mismatch. Expected ₹" . number_format($expected, 2) . ".";
    }

    return [
        'worker_count' => $workerCount,
        'attendance_days' => $totalAttDays,
        'wage_total' => $wageTotal,
        'status' => empty($errors) ? 'passed' : 'mismatch',
        'errors' => $errors,
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondCompliance(false, 'Only POST requests are allowed');
    }

    ensureComplianceSchema($conn);
    clms_get_portal_contractor($conn);

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $contractor = $userId ? db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]) : null;
    if (!$contractor) {
        respondCompliance(false, 'Contractor registration not found');
    }

    $contractorId = (int)$contractor['id'];
    $type = strtolower(trim($_POST['type'] ?? ''));
    if (!in_array($type, ['esi', 'pf', 'klwf'], true)) {
        respondCompliance(false, 'Invalid compliance type');
    }

    $monthYear = $_POST['contribution_month'] ?? date('Y-m');
    if ($type === 'klwf') {
        $monthYear = date('Y-m');
    }
    list($month, $year, $monthYear) = complianceMonthParts($monthYear);

    if (empty($_FILES['challan_file']) || $_FILES['challan_file']['error'] !== UPLOAD_ERR_OK) {
        respondCompliance(false, 'Challan file is required');
    }

    $payload = $_POST;
    $tmpFile = $_FILES['challan_file']['tmp_name'] ?? null;
    $validation = computeComplianceValidation($conn, $contractorId, $monthYear, $type, $payload, $tmpFile);
    $challanFile = uploadComplianceFile('challan_file', $contractorId, $type, $monthYear, ['pdf', 'jpg', 'jpeg', 'png']);
    $ecrFile = uploadComplianceFile('ecr_file', $contractorId, $type, $monthYear, ['xlsx', 'xls', 'txt', 'csv', 'pdf']);

    $conn->begin_transaction();

    $existing = db_single($conn, "SELECT * FROM compliance WHERE contractor_id = ? AND month_year = ? AND type = ? LIMIT 1", 'iss', [$contractorId, $monthYear, $type]);
    if ($existing) {
        $complianceId = (int)$existing['id'];
    } else {
        db_execute(
            $conn,
            "INSERT INTO compliance (contractor_id, month, year, month_year, type, status, uploaded_at)
             VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
            'isiss',
            [$contractorId, $month, $year, $monthYear, $type]
        );
        $complianceId = (int)$conn->insert_id;
    }

    $challanNo = trim($_POST['challan_no'] ?? '');
    $amount = 0.0;
    $workerCount = 0;
    if ($type === 'esi') {
        $amount = (float)($_POST['total_contribution'] ?? 0);
        $workerCount = (int)($_POST['employees_count'] ?? 0);
    } elseif ($type === 'pf') {
        $amount = (float)($_POST['total_pf'] ?? 0);
        $workerCount = (int)($_POST['members_count'] ?? 0);
    } else {
        $amount = (float)($_POST['total_amount'] ?? 0);
        $workerCount = (int)($_POST['worker_count'] ?? 0);
    }

    $esiAmount = $type === 'esi' ? $amount : 0.0;
    $pfAmount = $type === 'pf' ? $amount : 0.0;
    $klwfAmount = $type === 'klwf' ? $amount : 0.0;
    $esiFile = $type === 'esi' ? $challanFile : null;
    $pfFile = $type === 'pf' ? $challanFile : null;
    $klwfFile = $type === 'klwf' ? $challanFile : null;
    $errorsText = implode("\n", $validation['errors']);

    db_execute(
        $conn,
        "UPDATE compliance SET
            type=?, challan_number=?, amount=?, file_path=?,
            challan_worker_count=?, attendance_count=?,
            worker_count=?, attendance_days=?, wage_total=?,
            esi_amount=?, pf_amount=?, klwf_amount=?,
            esi_file=?, pf_file=?, klwf_file=?,
            validation_status=?, validation_errors=?,
            status='pending', remarks=NULL, updated_at=NOW()
         WHERE id=?",
        'ssdsiiiiidddsssssi',
        [
            $type, $challanNo, $amount, $challanFile,
            $workerCount, (int)$validation['attendance_days'],
            $validation['worker_count'], $validation['attendance_days'], $validation['wage_total'],
            $esiAmount, $pfAmount, $klwfAmount,
            $esiFile, $pfFile, $klwfFile,
            $validation['status'], $errorsText,
            $complianceId
        ]
    );

    if ($type === 'esi') {
        db_execute($conn, "DELETE FROM compliance_esi WHERE compliance_id=?", 'i', [$complianceId]);
        db_execute(
            $conn,
            "INSERT INTO compliance_esi (compliance_id, challan_no, challan_date, employees_count, gross_wages, employer_contribution, employee_contribution, total_contribution, file_path)
             VALUES (?,?,?,?,?,?,?,?,?)",
            'issidddds',
            [
                $complianceId, trim($_POST['challan_no'] ?? ''), $_POST['challan_date'] ?? null,
                (int)($_POST['employees_count'] ?? 0), (float)($_POST['gross_wages'] ?? 0),
                (float)($_POST['employer_contribution'] ?? 0), (float)($_POST['employee_contribution'] ?? 0),
                (float)($_POST['total_contribution'] ?? 0), $challanFile
            ]
        );
    } elseif ($type === 'pf') {
        db_execute($conn, "DELETE FROM compliance_epf WHERE compliance_id=?", 'i', [$complianceId]);
        db_execute(
            $conn,
            "INSERT INTO compliance_epf (compliance_id, ecr_no, challan_date, members_count, total_wages, epf_contribution, eps_contribution, total_pf, file_path, ecr_file_path)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            'issiddddss',
            [
                $complianceId, trim($_POST['challan_no'] ?? ''), $_POST['challan_date'] ?? null,
                (int)($_POST['members_count'] ?? 0), (float)($_POST['total_wages'] ?? 0),
                (float)($_POST['epf_contribution'] ?? 0), (float)($_POST['eps_contribution'] ?? 0),
                (float)($_POST['total_pf'] ?? 0), $challanFile, $ecrFile
            ]
        );
    } else {
        db_execute($conn, "DELETE FROM compliance_klwf WHERE compliance_id=?", 'i', [$complianceId]);
        db_execute(
            $conn,
            "INSERT INTO compliance_klwf (compliance_id, challan_no, payment_date, worker_count, employee_contribution, employer_contribution, amount, file_path)
             VALUES (?,?,?,?,?,?,?,?)",
            'issiddds',
            [
                $complianceId, trim($_POST['challan_no'] ?? ''), $_POST['payment_date'] ?? null,
                (int)($_POST['worker_count'] ?? 0), (float)($_POST['employee_contribution'] ?? 0),
                (float)($_POST['employer_contribution'] ?? 0), (float)($_POST['total_amount'] ?? 0), $challanFile
            ]
        );
    }

    db_execute($conn, "INSERT INTO compliance_logs (compliance_id, action, user_id, remarks) VALUES (?, 'submitted', ?, ?)", 'iis', [$complianceId, $userId, strtoupper($type) . ' compliance submitted']);
    db_execute($conn, "UPDATE contractors SET compliance_status='pending' WHERE id=?", 'i', [$contractorId]);

    $conn->commit();

    respondCompliance(true, 'Compliance submitted for welfare verification', [
        'compliance_id' => $complianceId,
        'validation_status' => $validation['status'],
        'validation_errors' => $validation['errors'],
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    respondCompliance(false, $e->getMessage());
}
?>

