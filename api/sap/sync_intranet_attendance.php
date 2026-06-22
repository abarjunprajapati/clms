<?php
// api/sap/sync_intranet_attendance.php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure schema runs
ensureComplianceSchema($conn);

// Read JSON input payload
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request payload. Must be a JSON array of attendance records.'
    ]);
    exit;
}

$processed = 0;
$skipped = 0;
$logs = [];

foreach ($data as $record) {
    $code_no = isset($record['code_no']) ? trim((string)$record['code_no']) : '';
    $name = isset($record['name']) ? trim((string)$record['name']) : '';
    $date_in = isset($record['date_in']) ? trim((string)$record['date_in']) : '';
    $in_time = isset($record['in_time']) ? trim((string)$record['in_time']) : '';
    $date_out = isset($record['date_out']) ? trim((string)$record['date_out']) : '';
    $out_time = isset($record['out_time']) ? trim((string)$record['out_time']) : '';
    $remarks = isset($record['remarks']) ? trim((string)$record['remarks']) : '';

    if (empty($code_no) || empty($date_in)) {
        $skipped++;
        continue;
    }

    // Convert date format from DD-MM-YYYY to YYYY-MM-DD
    $date_in_formatted = '';
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date_in, $matches)) {
        $date_in_formatted = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
    } else {
        $skipped++;
        continue; // Invalid date format
    }

    // Parse Check-in
    $check_in_dt = NULL;
    $status = 'absent';
    if (!empty($in_time) && strpos(strtoupper($remarks), 'NO SWIPE DETAILS FOUND') === false) {
        $check_in_dt = $date_in_formatted . ' ' . $in_time;
        $status = 'present';
    } else {
        $check_in_dt = $date_in_formatted . ' 00:00:00';
    }

    // Parse Check-out
    $check_out_dt = NULL;
    $ot_hours = 0.00;
    if (!empty($out_time) && !empty($date_out)) {
        $date_out_formatted = '';
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date_out, $matches_out)) {
            $date_out_formatted = "{$matches_out[3]}-{$matches_out[2]}-{$matches_out[1]}";
            $check_out_dt = $date_out_formatted . ' ' . $out_time;

            // Calculate Overtime (Standard shift = 8 hours)
            if ($status === 'present') {
                $in_ts = strtotime($check_in_dt);
                $out_ts = strtotime($check_out_dt);
                if ($out_ts >= $in_ts) {
                    $total_hrs = ($out_ts - $in_ts) / 3600.0;
                    if ($total_hrs > 8.0) {
                        $ot_hours = round($total_hrs - 8.0, 2);
                    }
                }
            }
        }
    }

    // Match workman in workmen table by acc_number / code_no
    $workman = db_single($conn, "SELECT id FROM workmen WHERE acc_number = ? LIMIT 1", 's', [$code_no]);
    if ($workman) {
        $workman_id = $workman['id'];

        $stmt = $conn->prepare("
            INSERT INTO attendance 
                (workman_id, acc_card_number, check_in, check_out, source, device_id, status, ot_hours, created_at)
            VALUES (?, ?, ?, ?, 'sap', 'intranet', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                check_in = VALUES(check_in),
                check_out = VALUES(check_out),
                status = VALUES(status),
                ot_hours = VALUES(ot_hours)
        ");

        if ($stmt) {
            $stmt->bind_param('issssd', 
                $workman_id, 
                $code_no, 
                $check_in_dt, 
                $check_out_dt, 
                $status, 
                $ot_hours
            );
            $stmt->execute();
            $stmt->close();
            $processed++;
        } else {
            $skipped++;
        }
    } else {
        $skipped++;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Intranet attendance sync completed.',
    'processed_records' => $processed,
    'skipped_records' => $skipped
]);
?>
