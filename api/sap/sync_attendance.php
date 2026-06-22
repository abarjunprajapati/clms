<?php
// api/sap/sync_attendance.php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_compat.php'; // DB abstraction layer
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure schema runs
ensureComplianceSchema($conn);

// 1. Fetch SQL Server credentials override
if (!defined('SAP_DB_SERVER') && file_exists(__DIR__ . '/../../include/config_credentials.php')) {
    require_once __DIR__ . '/../../include/config_credentials.php';
}

if (!defined('SAP_DB_SERVER')) {
    define('SAP_DB_DRIVER', 'sqlsrv');
    define('SAP_DB_SERVER', '127.0.0.1'); 
    define('SAP_DB_USER', 'sa');               
    define('SAP_DB_PASS', ''); 
    define('SAP_DB_NAME', 'commondb');         
}

$sap_conn = clms_db_connect(SAP_DB_DRIVER, SAP_DB_SERVER, SAP_DB_USER, SAP_DB_PASS, SAP_DB_NAME);

if ($sap_conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'SAP Database Connection Failed: ' . $sap_conn->connect_error]);
    exit;
}

$log = [];
$sap_synced = 0;
$local_synced = 0;

try {
    // 2. Fetch raw attendance records from SAP DB
    // We assume the SQL Server has a table named 'sap_attendance' or similar
    $sql = "SELECT acc_no, attendance_date, in_time, out_time, worker_name, contractor_name, device_id FROM sap_attendance WHERE attendance_date >= DATEADD(day, -45, GETDATE())";
    $stmt_sap = $sap_conn->prepare($sql);
    $stmt_sap->execute();
    $res_sap = $stmt_sap->get_result();

    while ($row = $res_sap->fetch_assoc()) {
        $acc_no = $row['acc_no'];
        $att_date = $row['attendance_date']; // Format: YYYY-MM-DD
        $in_time = $row['in_time'];          // Format: HH:MM:SS
        $out_time = $row['out_time'];        // Format: HH:MM:SS or NULL
        $worker_name = $row['worker_name'] ?? '';
        $contractor_name = $row['contractor_name'] ?? '';
        $device_id = $row['device_id'] ?? 'SAP_DEV';

        // Calculate working hours and overtime hours
        $working_hours = NULL;
        $ot_hours = 0.00;
        if (!empty($in_time) && !empty($out_time)) {
            $in_ts = strtotime($att_date . ' ' . $in_time);
            $out_ts = strtotime($att_date . ' ' . $out_time);
            if ($out_ts >= $in_ts) {
                $diff_sec = $out_ts - $in_ts;
                $hrs = floor($diff_sec / 3600);
                $mins = floor(($diff_sec % 3600) / 60);
                $secs = $diff_sec % 60;
                $working_hours = sprintf('%02d:%02d:%02d', $hrs, $mins, $secs);

                // Overtime calculation: standard shift is 8 hours
                $total_hrs = $diff_sec / 3600.0;
                if ($total_hrs > 8.0) {
                    $ot_hours = round($total_hrs - 8.0, 2);
                }
            }
        }

        // Insert or update MySQL `sap_attendance` table
        $stmt_my_sap = $conn->prepare("
            INSERT INTO sap_attendance 
                (acc_no, attendance_date, in_time, out_time, working_hours, worker_name, contractor_name, sap_sync_status, device_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'SYNCED', ?)
            ON DUPLICATE KEY UPDATE 
                in_time = VALUES(in_time),
                out_time = VALUES(out_time),
                working_hours = VALUES(working_hours),
                sap_sync_status = 'SYNCED'
        ");
        $stmt_my_sap->bind_param('ssssssss', 
            $acc_no, 
            $att_date, 
            $in_time, 
            $out_time, 
            $working_hours, 
            $worker_name, 
            $contractor_name,
            $device_id
        );
        $stmt_my_sap->execute();
        $stmt_my_sap->close();
        $sap_synced++;

        // 3. Match workman and Sync to MySQL `attendance` table
        $workman = db_single($conn, "SELECT id FROM workmen WHERE acc_number = ? LIMIT 1", 's', [$acc_no]);
        if ($workman) {
            $workman_id = $workman['id'];
            $check_in_dt = $att_date . ' ' . ($in_time ?? '00:00:00');
            $check_out_dt = !empty($out_time) ? ($att_date . ' ' . $out_time) : NULL;
            $status = !empty($in_time) ? 'present' : 'absent';

            $stmt_att = $conn->prepare("
                INSERT INTO attendance 
                    (workman_id, acc_card_number, check_in, check_out, source, device_id, status, ot_hours, created_at)
                VALUES (?, ?, ?, ?, 'sap', ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    check_in = VALUES(check_in),
                    check_out = VALUES(check_out),
                    status = VALUES(status),
                    ot_hours = VALUES(ot_hours)
            ");
            
            // Adjust unique constraints if needed, but standard unique key is usually on workman_id + check_in date
            // Let's execute safety checks
            if ($stmt_att) {
                $stmt_att->bind_param('isssssd', 
                    $workman_id, 
                    $acc_no, 
                    $check_in_dt, 
                    $check_out_dt, 
                    $device_id, 
                    $status, 
                    $ot_hours
                );
                $stmt_att->execute();
                $stmt_att->close();
                $local_synced++;
            }
        }
    }

    $log[] = "Raw SAP Attendance Synced: $sap_synced";
    $log[] = "Local Attendance Matches Synced: $local_synced";

    echo json_encode([
        'success' => true,
        'message' => 'Attendance synchronization completed successfully.',
        'log' => $log
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Sync failed: ' . $e->getMessage(),
        'log' => $log
    ]);
}
?>
