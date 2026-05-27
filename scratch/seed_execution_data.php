<?php
include __DIR__ . '/../include/config.php';

echo "<pre>";

// 1. Get EO ID
$eoRes = mysqli_query($conn, "SELECT id FROM execution_officers LIMIT 1");
$eo = mysqli_fetch_assoc($eoRes);
if (!$eo) {
    echo "No Execution Officer found. Creating one demo officer.\n";
    mysqli_query($conn, "INSERT INTO execution_officers (name, employee_code, department) VALUES ('Demo Officer', 'EO123', 'Project Management')");
    $officerId = mysqli_insert_id($conn);
} else {
    $officerId = $eo['id'];
}
echo "Using Officer ID: $officerId\n";

// 2. Map some contractors
$contRes = mysqli_query($conn, "SELECT id, contractor_name FROM contractors LIMIT 3");
$contractorIds = [];
while ($c = mysqli_fetch_assoc($contRes)) {
    $contractorIds[] = $c['id'];
    mysqli_query($conn, "INSERT IGNORE INTO execution_officer_contractors (execution_officer_id, contractor_id, work_order_id) VALUES ($officerId, " . $c['id'] . ", 1)");
    echo "Mapped Contractor: " . $c['contractor_name'] . "\n";
}

// 3. Create some deployments
$deptRes = mysqli_query($conn, "SELECT id FROM master_departments LIMIT 3");
$depts = [];
while ($d = mysqli_fetch_assoc($deptRes)) $depts[] = $d['id'];

if (!empty($contractorIds)) {
    foreach ($contractorIds as $cid) {
        $workerRes = mysqli_query($conn, "SELECT id FROM workmen WHERE contractor_id = $cid LIMIT 5");
        $i = 0;
        while ($w = mysqli_fetch_assoc($workerRes)) {
            $wid = $w['id'];
            $deptId = $depts[array_rand($depts)] ?? 1;
            $status = ($i < 3) ? 'active' : 'inactive';
            mysqli_query($conn, "INSERT IGNORE INTO execution_worker_deployments (execution_officer_id, workman_id, contractor_id, department_id, work_order_id, status, shift) 
                                VALUES ($officerId, $wid, $cid, $deptId, 1, '$status', 'A')");
            
            // 4. Create some attendance for today
            mysqli_query($conn, "INSERT IGNORE INTO attendance (workman_id, check_in) VALUES ($wid, NOW())");
            
            // 5. Create some observations for active ones
            if ($status == 'active' && $i == 0) {
                mysqli_query($conn, "INSERT IGNORE INTO execution_observations (execution_officer_id, contractor_id, workman_id, observation_type, severity, remarks) 
                                    VALUES ($officerId, $cid, $wid, 'Safety Violation', 'high', 'Worker found without PPE at site.')");
            }
            $i++;
        }
    }
}

// 6. Create some exceptions
$excWorkerRes = mysqli_query($conn, "SELECT id FROM workmen WHERE contractor_id NOT IN (" . implode(',', $contractorIds) . ") LIMIT 2");
if ($excWorkerRes) {
    while ($w = mysqli_fetch_assoc($excWorkerRes)) {
        $wid = $w['id'];
        mysqli_query($conn, "INSERT IGNORE INTO attendance (workman_id, check_in) VALUES ($wid, NOW())");
        mysqli_query($conn, "INSERT IGNORE INTO attendance_exceptions (workman_id, exception_type, remarks) VALUES ($wid, 'Unauthorized Access', 'Worker from unassigned contractor punched.')");
    }
}

echo "Demo data seeding complete.\n";
echo "</pre>";
?>
