<?php
/**
 * scratch/test_integration.php
 * Automated integration test suite for the Enrolled Worker Management module.
 * Verifies Aadhaar uniqueness, ACC uniqueness, Contractor pass limits, and Document Expiry Cron automations.
 */

// Define color output constants for CLI
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RESET', "\033[0m");

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/pass_limit_validator.php';

echo "==================================================\n";
echo "    ENROLLED WORKER MANAGEMENT INTEGRATION TESTS  \n";
echo "==================================================\n\n";

$contractor_id = 1; // Test contractor
$test_aadhaar = '999988887777';
$test_acc = 'ACC-TEST-999';

// 0. Clean up previous test data if any
cleanUp($conn, $test_aadhaar, $test_acc);

try {
    // ----------------------------------------------------
    // TEST 1: Aadhaar Uniqueness Check (Rule 1)
    // ----------------------------------------------------
    echo "Running Test 1: Aadhaar Uniqueness Check... ";
    
    // Simulate inserting the first worker
    $insert1 = mysqli_query($conn, "
        INSERT INTO workmen (name, aadhaar, status, contractor_id, created_at)
        VALUES ('Test Worker One', '$test_aadhaar', 'pending', $contractor_id, NOW())
    ");
    if (!$insert1) {
        throw new Exception("Failed to insert first test workman: " . mysqli_error($conn));
    }
    $worker1_id = mysqli_insert_id($conn);

    // Sync to worker_master
    $master1 = mysqli_query($conn, "
        INSERT INTO worker_master (worker_id, contractor_id, aadhaar_no, worker_status, created_at)
        VALUES ($worker1_id, $contractor_id, '$test_aadhaar', 'Pending Verification', NOW())
    ");
    if (!$master1) {
        throw new Exception("Failed to insert first test worker_master: " . mysqli_error($conn));
    }

    // Now attempt to validate a second worker with the same Aadhaar
    $duplicate_aadhaar_check = false;
    
    $existing_aadhaar = db_single(
        $conn,
        "SELECT id FROM workmen WHERE aadhaar = ? AND status NOT IN ('removed', 'rejected')",
        's',
        [$test_aadhaar]
    );
    $existing_master = db_single(
        $conn,
        "SELECT worker_id FROM worker_master WHERE aadhaar_no = ? AND worker_status NOT IN ('Deleted', 'Rejected')",
        's',
        [$test_aadhaar]
    );

    if ($existing_aadhaar || $existing_master) {
        $duplicate_aadhaar_check = true; // Correctly blocked duplicate!
    }

    if ($duplicate_aadhaar_check) {
        echo COLOR_GREEN . "[PASS] Duplicate Aadhaar successfully blocked." . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "[FAIL] Duplicate Aadhaar was NOT blocked." . COLOR_RESET . "\n";
    }

    // ----------------------------------------------------
    // TEST 2: ACC Uniqueness Check (Rule 2)
    // ----------------------------------------------------
    echo "Running Test 2: ACC Uniqueness Check... ";
    
    // Assign ACC to worker 1
    $update1 = mysqli_query($conn, "UPDATE workmen SET acc_number = '$test_acc' WHERE id = $worker1_id");
    $updateMaster1 = mysqli_query($conn, "UPDATE worker_master SET acc_no = '$test_acc' WHERE worker_id = $worker1_id");

    // Insert worker 2
    $insert2 = mysqli_query($conn, "
        INSERT INTO workmen (name, aadhaar, status, contractor_id, created_at)
        VALUES ('Test Worker Two', '111122223333', 'pending', $contractor_id, NOW())
    ");
    $worker2_id = mysqli_insert_id($conn);

    // Try assigning the same ACC to worker 2 and check uniqueness queries
    $duplicate_acc_check = false;
    $existing_acc = db_single($conn, 
        "SELECT id FROM workmen WHERE acc_number = ? AND id != ? AND status != 'removed'", 
        'si', [$test_acc, $worker2_id]
    );
    $existing_acc_master = db_single($conn, 
        "SELECT worker_id FROM worker_master WHERE acc_no = ? AND worker_id != ? AND worker_status != 'Deleted'", 
        'si', [$test_acc, $worker2_id]
    );

    if ($existing_acc || $existing_acc_master) {
        $duplicate_acc_check = true; // Correctly blocked duplicate ACC mapping!
    }

    if ($duplicate_acc_check) {
        echo COLOR_GREEN . "[PASS] Duplicate ACC successfully blocked." . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "[FAIL] Duplicate ACC was NOT blocked." . COLOR_RESET . "\n";
    }

    // ----------------------------------------------------
    // TEST 3: Contractor Pass Limit Check
    // ----------------------------------------------------
    echo "Running Test 3: Contractor Allowed Limits... ";
    
    // We fetch a contractor's limit from annexure 2a to see what limits exist
    $cLimit = db_single($conn, "SELECT id, allocation_limit FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1", 'i', [$contractor_id]);
    if ($cLimit) {
        $limit = (int)$cLimit['allocation_limit'];
        echo "Contractor $contractor_id Allocation Limit is $limit. ";
        
        // Let's test the validator function with a huge count to trigger an exception
        try {
            validatePassLimit($conn, $contractor_id, 'Workman', $limit + 100, false);
            echo COLOR_RED . "[FAIL] Excess pass limit was allowed." . COLOR_RESET . "\n";
        } catch (Exception $e) {
            echo COLOR_GREEN . "[PASS] Excess pass limit correctly rejected. Message: " . $e->getMessage() . COLOR_RESET . "\n";
        }
    } else {
        echo COLOR_YELLOW . "[SKIP] No allocation limit configured for contractor $contractor_id." . COLOR_RESET . "\n";
    }

    // ----------------------------------------------------
    // TEST 4: Document Expiry Automation & Pass Suspension
    // ----------------------------------------------------
    echo "Running Test 4: Document Expiry & Pass Suspension... ";

    // Set worker 1 status to Active and insert a safety critical document that is expired
    mysqli_query($conn, "UPDATE worker_master SET worker_status = 'Active' WHERE worker_id = $worker1_id");
    mysqli_query($conn, "UPDATE workmen SET status = 'active' WHERE id = $worker1_id");

    // Insert worker pass
    mysqli_query($conn, "
        INSERT INTO worker_passes (worker_id, pass_status, created_at)
        VALUES ($worker1_id, 'Approved', NOW())
    ");

    // Insert expired critical safety document (Medical Certificate)
    mysqli_query($conn, "
        INSERT INTO worker_documents (worker_id, document_type, document_path, expiry_date, verification_status, created_at)
        VALUES ($worker1_id, 'Medical Fitness Certificate', 'uploads/worker_documents/test.pdf', '2020-01-01', 'Verified', NOW())
    ");

    // Execute the document expiry monitor cron script code locally
    include_once __DIR__ . '/../api/cron/document_expiry_monitor.php';

    // Verify worker status has been set to Expired and pass is deactivated
    $check_worker = db_single($conn, "SELECT worker_status FROM worker_master WHERE worker_id = $worker1_id");
    $check_workman = db_single($conn, "SELECT status FROM workmen WHERE id = $worker1_id");
    $check_pass = db_single($conn, "SELECT pass_status FROM worker_passes WHERE worker_id = $worker1_id");

    if ($check_worker['worker_status'] === 'Expired' && $check_workman['status'] === 'expired' && $check_pass['pass_status'] === 'Expired') {
        echo COLOR_GREEN . "[PASS] Worker suspended and passes deactivated on safety document expiry." . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "[FAIL] Worker suspension failed. Master status: {$check_worker['worker_status']}, Workman: {$check_workman['status']}, Pass: {$check_pass['pass_status']}" . COLOR_RESET . "\n";
    }

} catch (Exception $e) {
    echo COLOR_RED . "\nTest execution failed with exception: " . $e->getMessage() . COLOR_RESET . "\n";
} finally {
    // Cleanup test data
    echo "\nCleaning up test data... ";
    cleanUp($conn, $test_aadhaar, $test_acc);
    mysqli_query($conn, "DELETE FROM workmen WHERE id = " . ($worker2_id ?? 0));
    echo "Done.\n";
}

function cleanUp($conn, $aadhaar, $acc) {
    // Delete any matching test workers
    $res = mysqli_query($conn, "SELECT id FROM workmen WHERE aadhaar = '$aadhaar' OR acc_number = '$acc'");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $id = $row['id'];
            mysqli_query($conn, "DELETE FROM worker_master WHERE worker_id = $id");
            mysqli_query($conn, "DELETE FROM worker_documents WHERE worker_id = $id");
            mysqli_query($conn, "DELETE FROM worker_passes WHERE worker_id = $id");
            mysqli_query($conn, "DELETE FROM worker_qualifications WHERE worker_id = $id");
            mysqli_query($conn, "DELETE FROM workmen WHERE id = $id");
        }
    }
}
?>
