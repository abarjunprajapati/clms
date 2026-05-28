<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
    if (!$worker_id) {
        throw new Exception("Worker ID is required.");
    }

    $hasNationality = false;
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM workmen LIKE 'nationality'");
    if ($colRes && mysqli_num_rows($colRes) > 0) {
        $hasNationality = true;
    }
    $nationalitySelect = $hasNationality ? "wm.nationality" : "'Indian'";

    // 1. Fetch Master & Workmen Details
    $masterQuery = "
        SELECT w.*, 
               wm.name as worker_name, wm.father_name, wm.spouse_name, wm.dob, wm.gender,
               $nationalitySelect as nationality,
               wm.mobile as workmen_mobile, wm.email as workmen_email, wm.permanent_address, 
               wm.present_address, wm.state, wm.district, wm.education, wm.nature_of_work,
               wm.training_status, wm.safety_training_status, wm.status as workmen_status,
               c.contractor_name as contractor_name, c.vendor_code as contractor_vendor_code,
               d.name as department_name, wo.work_order_no
        FROM worker_master w
        LEFT JOIN workmen wm ON w.worker_id = wm.id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN master_departments d ON w.department_id = d.id
        LEFT JOIN work_orders wo ON w.work_order_id = wo.id
        WHERE w.worker_id = $worker_id
    ";
    
    $masterRes = mysqli_query($conn, $masterQuery);
    if (!$masterRes || mysqli_num_rows($masterRes) === 0) {
        throw new Exception("Worker master record not found.");
    }
    $workerData = mysqli_fetch_assoc($masterRes);

    // 2. Fetch Qualifications
    $qualQuery = "SELECT * FROM worker_qualifications WHERE worker_id = $worker_id";
    $qualRes = mysqli_query($conn, $qualQuery);
    $qualifications = [];
    if ($qualRes) {
        while ($q = mysqli_fetch_assoc($qualRes)) {
            $qualifications[] = $q;
        }
    }

    // 3. Fetch Documents
    $docQuery = "SELECT * FROM worker_documents WHERE worker_id = $worker_id ORDER BY created_at DESC";
    $docRes = mysqli_query($conn, $docQuery);
    $documents = [];
    if ($docRes) {
        while ($d = mysqli_fetch_assoc($docRes)) {
            $documents[] = $d;
        }
    }

    // 4. Fetch Audit Logs (History Rail)
    $auditQuery = "
        SELECT wal.*, u.name as user_name 
        FROM worker_audit_logs wal
        LEFT JOIN users u ON wal.created_by = u.id
        WHERE wal.worker_id = $worker_id 
        ORDER BY wal.created_at DESC
    ";
    $auditRes = mysqli_query($conn, $auditQuery);
    $auditLogs = [];
    if ($auditRes) {
        while ($a = mysqli_fetch_assoc($auditRes)) {
            $auditLogs[] = $a;
        }
    }

    // 5. Fetch Block History
    $blockQuery = "
        SELECT wbh.*, u1.name as blocked_by_name, u2.name as unblocked_by_name 
        FROM worker_block_history wbh
        LEFT JOIN users u1 ON wbh.blocked_by = u1.id
        LEFT JOIN users u2 ON wbh.unblocked_by = u2.id
        WHERE wbh.worker_id = $worker_id 
        ORDER BY wbh.blocked_at DESC
    ";
    $blockRes = mysqli_query($conn, $blockQuery);
    $blockHistory = [];
    if ($blockRes) {
        while ($b = mysqli_fetch_assoc($blockRes)) {
            $blockHistory[] = $b;
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'worker' => $workerData,
            'qualifications' => $qualifications,
            'documents' => $documents,
            'audit_logs' => $auditLogs,
            'block_history' => $blockHistory
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
