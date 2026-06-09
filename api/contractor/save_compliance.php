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

function computeComplianceValidation($conn, $contractorId, $monthYear, $type, $payload) {
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));

    $workerCount = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ?", 'i', [$contractorId]);
    $attendanceDays = db_count(
        $conn,
        "SELECT COUNT(*) FROM attendance a JOIN workmen w ON a.workman_id = w.id
         WHERE w.contractor_id = ? AND DATE(a.check_in) BETWEEN ? AND ?",
        'iss',
        [$contractorId, $start, $end]
    );
    $wageRow = db_single($conn, "SELECT COALESCE(SUM(salary),0) AS total FROM wages WHERE contractor_id = ? AND month_year = ?", 'is', [$contractorId, $monthYear]);
    $wageTotal = (float)($wageRow['total'] ?? 0);

    $errors = [];
    if ($workerCount <= 0) {
        $errors[] = 'No enrolled workers found for contractor.';
    }

    if ($type === 'esi') {
        $covered = (int)($payload['employees_count'] ?? 0);
        $gross = (float)($payload['gross_wages'] ?? 0);
        $expected = round($gross * 0.04, 2);
        $actual = (float)($payload['total_contribution'] ?? 0);
        if ($covered !== $workerCount) $errors[] = "ESI employee count ($covered) does not match enrolled workers ($workerCount).";
        if (abs($expected - $actual) > 1) $errors[] = "ESI contribution mismatch. Expected about $expected.";
        if ($attendanceDays === 0) $errors[] = 'No attendance found for this month.';
    }

    if ($type === 'pf') {
        $members = (int)($payload['members_count'] ?? 0);
        $wages = (float)($payload['total_wages'] ?? 0);
        $expected = round($wages * 0.24, 2);
        $actual = (float)($payload['total_pf'] ?? 0);
        if ($members !== $workerCount) $errors[] = "PF member count ($members) does not match enrolled workers ($workerCount).";
        if (abs($expected - $actual) > 1) $errors[] = "PF contribution mismatch. Expected about $expected.";
    }

    if ($type === 'klwf') {
        $count = (int)($payload['worker_count'] ?? 0);
        $expected = $count * 200;
        $actual = (float)($payload['total_amount'] ?? 0);
        if ($count !== $workerCount) $errors[] = "KLWF worker count ($count) does not match enrolled workers ($workerCount).";
        if (abs($expected - $actual) > 1) $errors[] = "KLWF amount mismatch. Expected $expected.";
    }

    return [
        'worker_count' => $workerCount,
        'attendance_days' => $attendanceDays,
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
    $validation = computeComplianceValidation($conn, $contractorId, $monthYear, $type, $payload);
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

