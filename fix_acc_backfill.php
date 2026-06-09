<?php
/**
 * Advanced Backfill & Diagnostic: Fix workmen with ACC numbers assigned
 * - Diagnoses their current workman status & application status
 * - Resolves missing permanent gate pass records
 * Run from browser: https://cslweb.teleconsystems.com/fix_acc_backfill.php
 */
require_once __DIR__ . '/include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$validTo = date('Y-m-d', strtotime('+1 year'));
$validToSql = $conn->real_escape_string($validTo);

echo "=== ADVANCED ACC BACKFILL & DIAGNOSTIC ===\n\n";

// 1. Fetch all workmen who have an ACC number assigned
$workmen = $conn->query(
    "SELECT w.id, w.acc_number, w.application_no, w.contractor_id, w.name, w.status as w_status,
            a.workflow_status as app_status, a.contractor_name
     FROM workmen w
     LEFT JOIN annexure2a a ON w.application_no = a.application_id
     WHERE w.acc_number IS NOT NULL AND w.acc_number != ''"
);

if (!$workmen) {
    die("Query failed: " . $conn->error . "\n");
}

$count = $workmen->num_rows;
echo "Found $count workmen in database with an ACC number.\n";
echo "--------------------------------------------------------\n\n";

$fixed = 0;
$already_ok = 0;
$errors = [];

while ($w = $workmen->fetch_assoc()) {
    $id = (int)$w['id'];
    $acc = $conn->real_escape_string($w['acc_number']);
    $app = $conn->real_escape_string($w['application_no'] ?? '');
    $cid = (int)$w['contractor_id'];
    $name = $w['name'];
    $w_status = $w['w_status'];
    $app_status = $w['app_status'] ?? 'NONE';

    echo "Worker ID: $id | Name: $name | ACC: $acc\n";
    echo "  - Current Workman Status: '$w_status'\n";
    echo "  - Current Application Status: '$app_status'\n";

    // Check if permanent gate pass row exists
    $pgp_res = $conn->query("SELECT id, status, pass_no FROM permanent_gate_passes WHERE worker_id = $id");
    $pgp = $pgp_res ? $pgp_res->fetch_assoc() : null;

    if ($pgp) {
        echo "  - Permanent Gate Pass exists: Pass No = {$pgp['pass_no']}, Status = '{$pgp['status']}'\n";
        if ($pgp['status'] === 'active' && $w_status === 'permanent_active' && in_array($app_status, ['completed', 'pass_generated', 'permanent_pass_issued', 'acc_approved'])) {
            echo "  -> Status: OK (Already fully configured and visible)\n\n";
            $already_ok++;
            continue;
        }
    } else {
        echo "  - Permanent Gate Pass exists: NO (Missing in permanent_gate_passes table)\n";
    }

    echo "  -> Action: Running Auto-Fix...\n";

    // Fix 1: Promote workman to permanent_active
    $ok1 = $conn->query(
        "UPDATE workmen SET status='permanent_active', valid_from=CURDATE(), valid_to='$validToSql' WHERE id=$id"
    );
    if ($ok1) {
        echo "     [✓] Promoted workman to 'permanent_active'\n";
    } else {
        $errors[] = "Worker $id: Failed updating workman status: " . $conn->error;
        echo "     [✗] Failed updating workman status: " . $conn->error . "\n\n";
        continue;
    }

    // Fix 2: Ensure application has an acceptable workflow status for gate pass page visibility
    $valid_app_statuses = ['completed', 'pass_generated', 'permanent_pass_issued', 'acc_approved'];
    if (!in_array($app_status, $valid_app_statuses)) {
        $ok2 = $conn->query(
            "UPDATE annexure2a SET workflow_status='acc_approved' WHERE application_id='$app'"
        );
        if ($ok2) {
            echo "     [✓] Updated application workflow_status to 'acc_approved'\n";
        } else {
            echo "     [!] Warning: Failed updating application workflow_status: " . $conn->error . "\n";
        }
    }

    // Fix 3: Sync main gate_passes table to approved permanent status
    $conn->query(
        "UPDATE gate_passes SET pass_type='permanent', valid_to='$validToSql', status='approved'
         WHERE application_no='$app' AND workman_id=$id"
    );

    // Fix 4: Insert / activate permanent_gate_passes row
    if ($pgp) {
        $ok3 = $conn->query("UPDATE permanent_gate_passes SET status='active', valid_till='$validToSql' WHERE worker_id=$id");
        if ($ok3) {
            echo "     [✓] Activated existing permanent_gate_passes row\n";
            $fixed++;
        } else {
            $errors[] = "Worker $id: Failed activating permanent_gate_passes: " . $conn->error;
            echo "     [✗] Failed activating permanent_gate_passes: " . $conn->error . "\n";
        }
    } else {
        $ok3 = $conn->query(
            "INSERT INTO permanent_gate_passes
             (pass_no, worker_id, application_id, contractor_id, valid_from, valid_till, status)
             VALUES ('$acc', $id, '$app', $cid, CURDATE(), '$validToSql', 'active')"
        );
        if ($ok3) {
            echo "     [✓] Created new active permanent_gate_passes row\n";
            $fixed++;
        } else {
            $errors[] = "Worker $id: Failed inserting permanent_gate_passes: " . $conn->error;
            echo "     [✗] Failed inserting permanent_gate_passes: " . $conn->error . "\n";
        }
    }
    echo "  -> Auto-Fix completed successfully!\n\n";
}

echo "=== SUMMARY ===\n";
echo "Total workmen with ACC: $count\n";
echo "Already configured correctly: $already_ok\n";
echo "Successfully fixed: $fixed\n";
echo "Errors encountered: " . count($errors) . "\n";
if (!empty($errors)) {
    echo "\nError Details:\n" . implode("\n", $errors) . "\n";
}
?>
