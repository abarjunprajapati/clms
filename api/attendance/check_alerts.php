<?php
/**
 * api/attendance/check_alerts.php
 * Real-time attendance monitoring and alert generation as per PDF requirements.
 */
require_once '../../include/config.php';

function checkAttendanceAlerts($conn) {
    $alerts = [];
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // 1. Missing OUT Punch (Inside plant for > 12 hours)
    $cutoff = date('Y-m-d H:i:s', strtotime('-12 hours'));
    $missing_out = db_fetch_all($conn, "
        SELECT a.*, w.id as worker_id, w.name, c.contractor_name 
        FROM sap_attendance a
        JOIN workmen w ON a.acc_no = w.acc_number
        JOIN contractors c ON w.contractor_id = c.id
        WHERE a.out_time IS NULL AND CONCAT(a.attendance_date, ' ', a.in_time) < ?
    ", 's', [$cutoff]);

    foreach($missing_out as $row) {
        $alerts[] = [
            'workman_id' => $row['worker_id'],
            'type' => 'inside_plant',
            'desc' => "Worker inside plant without OUT punch for > 12 hours. In-time: {$row['in_time']}"
        ];
    }

    // 2. Expired Pass Entry Attempt (handled in entry API, but checking here for existing)
    $expired_passes = db_fetch_all($conn, "
        SELECT w.id, w.name, w.temp_valid_to, c.contractor_name 
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        WHERE w.status IN ('temporary_issued', 'permanent_active') AND w.temp_valid_to < ?
    ", 's', [$today]);

    foreach($expired_passes as $row) {
        $alerts[] = [
            'workman_id' => $row['id'],
            'type' => 'expired_pass',
            'desc' => "Pass expired on {$row['temp_valid_to']}. System should block gate entry."
        ];
    }

    // 3. Blocked Worker Alert
    $blocked = db_fetch_all($conn, "SELECT id, name FROM workmen WHERE status = 'blocked'");
    foreach($blocked as $row) {
        $alerts[] = [
            'workman_id' => $row['id'],
            'type' => 'blocked_worker',
            'desc' => "Worker is in BLOCKED list. Immediate security notification required if found at gate."
        ];
    }

    // Insert into attendance_alerts table
    foreach($alerts as $alert) {
        db_execute($conn, "
            INSERT INTO attendance_alerts (workman_id, alert_type, alert_date, description, status)
            VALUES (?, ?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE description = VALUES(description)
        ", 'isss', [$alert['workman_id'], $alert['type'], $today, $alert['desc']]);
    }

    return count($alerts);
}

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    $count = checkAttendanceAlerts($conn);
    echo json_encode(['success' => true, 'alerts_generated' => $count]);
}
