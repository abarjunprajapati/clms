<?php
/**
 * Setup Test Data for Annexure 5/A Testing
 * Creates sample contractors with enrollment data
 * 
 * Usage: php api/annexure5a/setup_test_data.php
 */

require_once __DIR__ . '/../../include/config.php';

try {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "   ANNEXURE 5/A - TEST DATA SETUP\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    // ========== CREATE AUDIT LOG TABLE ==========
    $create_audit = "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(100),
        contractor_id INT,
        pass_type VARCHAR(50),
        requested_count INT,
        reason TEXT,
        admin_id INT,
        admin_name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contractor (contractor_id),
        INDEX idx_action (action),
        INDEX idx_created (created_at)
    )";
    
    if (clms_db_query($conn, $create_audit)) {
        echo "✅ Audit log table ready\n";
    }

    // ========== INSERT TEST CONTRACTORS ==========
    echo "\n📋 Inserting test contractors...\n";

    $contractors = [
        [
            'id' => 100,
            'firm_id' => 'FIRM-001',
            'name' => 'ABC Construction',
            'email' => 'abc@construction.com'
        ],
        [
            'id' => 101,
            'firm_id' => 'FIRM-002', 
            'name' => 'XYZ Projects',
            'email' => 'xyz@projects.com'
        ]
    ];

    foreach ($contractors as $c) {
        // Check if exists
        $check = clms_db_query($conn, "SELECT id FROM contractors WHERE id = " . $c['id']);
        if (clms_db_num_rows($check) > 0) {
            echo "  ⚠️  Contractor ID {$c['id']} already exists\n";
            continue;
        }

        $sql = "INSERT INTO contractors (id, firm_id, name, email) VALUES (?, ?, ?, ?)";
        $stmt = clms_db_prepare($conn, $sql);
        clms_db_stmt_bind_param($stmt, 'isss', $c['id'], $c['firm_id'], $c['name'], $c['email']);
        
        if (clms_db_stmt_execute($stmt)) {
            echo "  ✅ Added: {$c['name']} (ID: {$c['id']})\n";
        } else {
            echo "  ❌ Failed to add {$c['name']}: " . clms_db_stmt_error($stmt) . "\n";
        }
        clms_db_stmt_close($stmt);
    }

    // ========== INSERT TEST WORKMEN ==========
    echo "\n📋 Inserting test workmen...\n";

    $workmen_data = [];
    for ($i = 1; $i <= 35; $i++) {
        $workmen_data[] = [
            'contractor_id' => 100,
            'name' => "Worker $i",
            'aadhar' => str_pad($i, 12, '0', STR_PAD_LEFT),
            'phone' => '98' . str_pad($i, 10, '0', STR_PAD_LEFT)
        ];
    }

    $inserted = 0;
    $sql = "INSERT INTO workmen (contractor_id, name, aadhar, phone, status) VALUES (?, ?, ?, ?, 'active')";
    $stmt = clms_db_prepare($conn, $sql);
    
    foreach ($workmen_data as $w) {
        clms_db_stmt_bind_param($stmt, 'isss', $w['contractor_id'], $w['name'], $w['aadhar'], $w['phone']);
        if (clms_db_stmt_execute($stmt)) {
            $inserted++;
        }
    }
    clms_db_stmt_close($stmt);
    
    echo "  ✅ Inserted $inserted workmen\n";

    // ========== INSERT TEST SUPERVISORS ==========
    echo "\n📋 Inserting test supervisors...\n";

    $supervisors = [
        ['id' => 101, 'contractor_id' => 100, 'name' => 'Super Visor 1', 'aadhar' => '111111111111'],
        ['id' => 102, 'contractor_id' => 100, 'name' => 'Super Visor 2', 'aadhar' => '222222222222']
    ];

    foreach ($supervisors as $sup) {
        $check = clms_db_query($conn, "SELECT id FROM workmen WHERE id = {$sup['id']}");
        if (clms_db_num_rows($check) > 0) {
            echo "  ⚠️  Supervisor ID {$sup['id']} already exists\n";
            continue;
        }

        $sql = "INSERT INTO workmen (id, contractor_id, name, aadhar, status) VALUES (?, ?, ?, ?, 'active')";
        $stmt = clms_db_prepare($conn, $sql);
        clms_db_stmt_bind_param($stmt, 'iiss', $sup['id'], $sup['contractor_id'], $sup['name'], $sup['aadhar']);
        
        if (clms_db_stmt_execute($stmt)) {
            echo "  ✅ Added: {$sup['name']}\n";
        }
        clms_db_stmt_close($stmt);
    }

    // ========== INSERT TEST REPRESENTATIVE ==========
    echo "\n📋 Inserting test representative...\n";

    $rep_check = clms_db_query($conn, "SELECT id FROM workmen WHERE contractor_id = 100 AND aadhar = '333333333333'");
    if (clms_db_num_rows($rep_check) === 0) {
        $sql = "INSERT INTO workmen (contractor_id, name, aadhar, status) VALUES (?, ?, ?, 'active')";
        $stmt = clms_db_prepare($conn, $sql);
        $contractor_id = 100;
        $name = "John Representative";
        $aadhar = "333333333333";
        
        clms_db_stmt_bind_param($stmt, 'iss', $contractor_id, $name, $aadhar);
        if (clms_db_stmt_execute($stmt)) {
            echo "  ✅ Added: John Representative\n";
        }
        clms_db_stmt_close($stmt);
    }

    // ========== INSERT SAMPLE AUDIT LOG ENTRIES ==========
    echo "\n📋 Inserting sample audit logs...\n";

    $audit_entries = [
        ['action' => 'pass_limit_override', 'contractor_id' => 100, 'pass_type' => 'Supervisor', 'requested_count' => 1, 'reason' => 'Special project requirement', 'admin_id' => 1, 'admin_name' => 'Welfare Admin'],
        ['action' => 'pass_limit_validation', 'contractor_id' => 100, 'pass_type' => 'Representative', 'requested_count' => 1, 'reason' => 'Added representative', 'admin_id' => 100, 'admin_name' => 'Contractor']
    ];

    $inserted_logs = 0;
    $sql = "INSERT INTO audit_log (action, contractor_id, pass_type, requested_count, reason, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = clms_db_prepare($conn, $sql);

    foreach ($audit_entries as $log) {
        clms_db_stmt_bind_param($stmt, 'sisiis', 
            $log['action'],
            $log['contractor_id'],
            $log['pass_type'],
            $log['requested_count'],
            $log['reason'],
            $log['admin_id'],
            $log['admin_name']
        );
        if (clms_db_stmt_execute($stmt)) {
            $inserted_logs++;
        }
    }
    clms_db_stmt_close($stmt);
    
    echo "  ✅ Inserted $inserted_logs audit log entries\n";

    // ========== VERIFICATION ==========
    echo "\n\n════ VERIFICATION ════\n\n";

    $result = clms_db_query($conn, "SELECT COUNT(*) as cnt FROM contractors WHERE id IN (100, 101)");
    $row = clms_db_fetch_assoc($result);
    echo "✅ Contractors: {$row['cnt']}/2\n";

    $result = clms_db_query($conn, "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = 100 AND status = 'active'");
    $row = clms_db_fetch_assoc($result);
    echo "✅ Workmen (Contractor 100): {$row['cnt']}/35\n";

    $result = clms_db_query($conn, "SELECT COUNT(*) as cnt FROM audit_log");
    $row = clms_db_fetch_assoc($result);
    echo "✅ Audit Logs: {$row['cnt']}\n";

    echo "\n✅ Test data setup complete!\n";
    echo "   Use Contractor ID: 100 (ABC Construction)\n";
    echo "   Total Workmen: 35\n";
    echo "   Supervisors: 2 (Max 4 for 35 workmen)\n";
    echo "   Representative: 1 (Max 1)\n\n";

} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    die(1);
}

?>

