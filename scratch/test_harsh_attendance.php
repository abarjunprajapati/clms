<?php
// scratch/test_harsh_attendance.php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/compliance_schema.php';

header('Content-Type: text/plain; charset=utf-8');

// Ensure schema is updated
ensureComplianceSchema($conn);

echo "--- Simulating Harsh Enrollment & Attendance ---\n\n";

// 1. Check if Harsh already exists
$workman = db_single($conn, "SELECT id FROM workmen WHERE acc_number = '10000248' LIMIT 1");

if ($workman) {
    $workman_id = $workman['id'];
    echo "Harsh already exists in workmen table with ID: $workman_id\n";
} else {
    // Insert Harsh as a verified worker under Contractor ID 1 (SRI RAMBALAJI GASES PVT LTD)
    $stmt = $conn->prepare("
        INSERT INTO workmen (name, father_name, gender, trade, acc_number, contractor_id, status, aadhaar, created_at)
        VALUES ('Harsh Kumar', 'Rakesh Kumar', 'M', 'Electrician', '10000248', 1, 'verified', '123456789012', NOW())
    ");
    if ($stmt && $stmt->execute()) {
        $workman_id = $conn->insert_id;
        echo "Successfully enrolled Harsh (ID: $workman_id) with ACC Code 10000248 under Contractor 1.\n";
        $stmt->close();
    } else {
        die("Error enrolling Harsh: " . $conn->error);
    }
}

// 2. Insert Test Attendance Logs for June 2026
$test_days = [
    ['day' => 1, 'in' => '06:42:13', 'out' => '15:34:44', 'ot' => 0.88], // ~8.87 hours (0.88 OT)
    ['day' => 2, 'in' => '06:46:41', 'out' => '15:32:08', 'ot' => 0.75],
    ['day' => 4, 'in' => '06:51:22', 'out' => '15:40:15', 'ot' => 0.81],
    ['day' => 5, 'in' => '06:55:30', 'out' => '15:33:14', 'ot' => 0.62],
    ['day' => 6, 'in' => '06:53:43', 'out' => '11:13:14', 'ot' => 0.00], // Short shift, no OT
    ['day' => 8, 'in' => '13:28:44', 'out' => '22:02:41', 'ot' => 0.57],
    ['day' => 9, 'in' => '13:26:47', 'out' => '22:05:19', 'ot' => 0.64],
    ['day' => 10, 'in' => '13:29:09', 'out' => '22:02:53', 'ot' => 0.56],
];

echo "\nInserting June 2026 attendance records...\n";
$inserted = 0;

foreach ($test_days as $day_data) {
    $day_str = str_pad($day_data['day'], 2, '0', STR_PAD_LEFT);
    $check_in = "2026-06-{$day_str} " . $day_data['in'];
    $check_out = "2026-06-{$day_str} " . $day_data['out'];
    $ot = $day_data['ot'];

    $stmt_att = $conn->prepare("
        INSERT INTO attendance 
            (workman_id, acc_card_number, check_in, check_out, source, device_id, status, ot_hours, created_at)
        VALUES (?, '10000248', ?, ?, 'sap', 'intranet', 'present', ?, NOW())
        ON DUPLICATE KEY UPDATE 
            check_in = VALUES(check_in),
            check_out = VALUES(check_out),
            status = 'present',
            ot_hours = VALUES(ot_hours)
    ");
    
    if ($stmt_att) {
        $stmt_att->bind_param('issd', $workman_id, $check_in, $check_out, $ot);
        $stmt_att->execute();
        $stmt_att->close();
        $inserted++;
    }
}

echo "Successfully inserted/updated $inserted attendance logs for Harsh for June 2026.\n";
echo "\nNow open the Muster Roll Print Preview to see the output!\n";
?>
