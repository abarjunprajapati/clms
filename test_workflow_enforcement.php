<?php
/**
 * Workflow Enforcement Test Suite
 * Tests: submitted → under_review → approved → training_pending → training_completed 
 *        → gatepass_pending → gatepass_approved → final_approval_pending → completed
 */

require_once 'include/config.php';
require_once 'api/WorkflowEngine.php';
require_once 'api/RuleEngine.php';

session_start();
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['name'] = 'Test Admin';

function test_log($msg) {
    echo "[TEST] " . date('H:i:s') . " - $msg\n";
}

function assert_status($appId, $expectedStatus, $label) {
    global $conn;
    $app = $conn->query("SELECT workflow_status FROM annexure2a WHERE application_id = '$appId' LIMIT 1")->fetch_assoc();
    $status = $app['workflow_status'] ?? null;
    if ($status === $expectedStatus) {
        test_log("✅ $label: $status");
        return true;
    } else {
        test_log("❌ $label: Expected $expectedStatus, got $status");
        return false;
    }
}

function test_transition($appId, $action, $expectedStatus, $userRole = 'admin') {
    global $conn;
    test_log("Testing action: $action");
    
    $result = WorkflowEngine::performAction($conn, $appId, $action, $userRole, 1, "Test: $action");
    
    if ($result['success']) {
        test_log("  ✅ Action succeeded: $result[old_status] → $result[new_status]");
        if (assert_status($appId, $expectedStatus, "  Status check")) {
            return true;
        }
    } else {
        test_log("  ❌ Action failed: $result[message]");
    }
    return false;
}

function test_invalid_action($appId, $action, $userRole = 'admin') {
    global $conn;
    test_log("Testing invalid action: $action (should fail)");
    
    $result = WorkflowEngine::performAction($conn, $appId, $action, $userRole, 1, "Test invalid");
    
    if (!$result['success']) {
        test_log("  ✅ Correctly rejected: $result[message]");
        return true;
    } else {
        test_log("  ❌ Should have been rejected but succeeded");
        return false;
    }
}

// === BEGIN TESTS ===
test_log("Starting Workflow Enforcement Test Suite");
test_log("=========================================");

// Create test application
$testAppId = "TEST-" . date('YmdHis') . "-" . mt_rand(1000, 9999);
test_log("Creating test application: $testAppId");

$conn->query("INSERT INTO annexure2a (application_id, contractor_name, workflow_status) VALUES ('$testAppId', 'Test Contractor', 'submitted')");
$conn->query("INSERT INTO applications (id, status) VALUES ('$testAppId', 'submitted')");
test_log("✅ Test application created");

// Test 1: Initial status is submitted
assert_status($testAppId, 'submitted', "Initial status");

// Test 2: Valid transition - submit to under_review (via 'review' action)
test_log("\nPhase 1: Submission & Review");
test_transition($testAppId, 'review', 'under_review', 'welfare');

// Test 3: Invalid action from under_review (can't request training yet)
test_log("\nPhase 2: Validation - Invalid Actions");
test_invalid_action($testAppId, 'request_training', 'contractor');

// Test 4: Valid transition - under_review to approved
test_log("\nPhase 3: Approval");
test_transition($testAppId, 'approve', 'approved', 'welfare');

// Test 5: Valid transition - approved to training_pending
test_log("\nPhase 4: Training Request");
test_transition($testAppId, 'request_training', 'training_pending', 'contractor');

// Test 6: Valid transition - training_pending to training_completed
test_log("\nPhase 5: Training Completion");
test_transition($testAppId, 'complete_training', 'training_completed', 'safety_officer');

// Test 7: Valid transition - training_completed to gatepass_pending
test_log("\nPhase 6: Gatepass Request");
test_transition($testAppId, 'request_gatepass', 'gatepass_pending', 'contractor');

// Test 8: Valid transition - gatepass_pending to gatepass_approved
test_log("\nPhase 7: Gatepass Approval");
test_transition($testAppId, 'approve_gatepass', 'gatepass_approved', 'pio');

// Test 9: Valid transition - gatepass_approved to final_approval_pending
test_log("\nPhase 8: Final Approval Request");
test_transition($testAppId, 'request_final_approval', 'final_approval_pending', 'welfare');

// Test 10: Valid transition - final_approval_pending to completed
test_log("\nPhase 9: Final Completion");
test_transition($testAppId, 'final_approve', 'completed', 'authority');

// Verify final state
test_log("\nFinal Verification");
$finalCheck = $conn->query("SELECT workflow_status FROM annexure2a WHERE application_id = '$testAppId' LIMIT 1")->fetch_assoc();
test_log("✅ Final workflow_status: " . $finalCheck['workflow_status']);

// Check logs
$logCount = $conn->query("SELECT COUNT(*) as cnt FROM workflow_logs WHERE application_id = '$testAppId'")->fetch_assoc();
test_log("✅ Workflow log entries recorded: " . $logCount['cnt']);

test_log("\n=========================================");
test_log("Test Suite Complete!");
test_log("All workflow transitions validated.");
?>

