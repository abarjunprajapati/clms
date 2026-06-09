<?php

class SapDemo {
    public static function log($conn, $activity, $status = 'SUCCESS') {
        $stmt = $conn->prepare("INSERT INTO sap_logs (activity, status) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param('ss', $activity, $status);
            $stmt->execute();
            $stmt->close();
        }
    }

    public static function syncWorker($conn, $acc_no, $name, $aadhaar, $contractor, $department) {
        $stmt = $conn->prepare("INSERT INTO sap_workers (acc_no, worker_name, aadhaar_no, contractor, department, sap_status, synced_at) VALUES (?, ?, ?, ?, ?, 'SYNCED', NOW())");
        if ($stmt) {
            $stmt->bind_param('sssss', $acc_no, $name, $aadhaar, $contractor, $department);
            $stmt->execute();
            $stmt->close();
            self::log($conn, "Worker $name ($acc_no) Synced To SAP", "SUCCESS");
            return true;
        }
        return false;
    }

    public static function syncAttendance($conn, $acc_no, $date, $in_time, $out_time) {
        $stmt = $conn->prepare("INSERT INTO sap_attendance (acc_no, attendance_date, in_time, out_time, sap_sync_status) VALUES (?, ?, ?, ?, 'SYNCED')");
        if ($stmt) {
            $stmt->bind_param('ssss', $acc_no, $date, $in_time, $out_time);
            $stmt->execute();
            $stmt->close();
            self::log($conn, "Attendance for $acc_no on $date Synced", "SUCCESS");
            return true;
        }
        return false;
    }

    public static function updateWorkerStatus($conn, $acc_no, $status) {
        $stmt = $conn->prepare("UPDATE sap_workers SET sap_status = ? WHERE acc_no = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $status, $acc_no);
            $stmt->execute();
            $stmt->close();
            self::log($conn, "Worker $acc_no Status Updated to $status in SAP", "SUCCESS");
            return true;
        }
        return false;
    }
}
?>
