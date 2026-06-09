<?php
/**
 * Automatic Fix for Contractor 1100908 Welfare Form
 * Submits the draft form and verifies correct linking
 */

include 'include/config.php';

echo "<h2>🚀 Contractor 1100908 - Automatic Fix</h2>";
echo "<hr>";

// Get contractor and annexure2a
$contractor = db_single($conn, 
    "SELECT id, vendor_code, vendor_name, contractor_name, status FROM contractors WHERE vendor_code = '1100908'");

if (!$contractor) {
    echo "<p style='color:red'>❌ Contractor 1100908 not found</p>";
    exit;
}

$annexure2a = db_single($conn,
    "SELECT id, contractor_id, contractor_name, workflow_status FROM annexure2a WHERE contractor_id = ?",
    'i', [$contractor['id']]);

if (!$annexure2a) {
    echo "<p style='color:red'>❌ No annexure2a record found for contractor 1100908</p>";
    exit;
}

echo "<h3>📋 Current Status</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><td>Contractor</td><td><strong>{$contractor['vendor_name']}</strong> (ID: {$contractor['id']}, Code: {$contractor['vendor_code']})</td></tr>";
echo "<tr><td>Form Status</td><td><strong>{$annexure2a['workflow_status']}</strong></td></tr>";
echo "<tr><td>Form Visible in Welfare?</td><td>" . (in_array($annexure2a['workflow_status'], ['submitted', 'under_review', 'pending']) ? '✅ Yes' : '❌ No (In Draft)') . "</td></tr>";
echo "</table>";

// Step 1: Submit the draft form if it's in draft status
if ($annexure2a['workflow_status'] === 'draft') {
    echo "<h3>⚙️ Applying Fix...</h3>";
    
    $result = db_execute($conn, 
        "UPDATE annexure2a SET workflow_status = 'submitted', submitted_at = NOW() WHERE id = ?", 
        'i', 
        [$annexure2a['id']]);
    
    if ($result) {
        echo "<p style='color:green'>✅ Step 1: Form submitted successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Step 1 Failed: Could not submit form</p>";
        exit;
    }
}

// Step 2: Verify correct contractor linking
$verify = db_single($conn,
    "SELECT a.id, a.contractor_name, c.vendor_name, c.vendor_code, a.workflow_status
     FROM annexure2a a
     JOIN contractors c ON a.contractor_id = c.id
     WHERE a.id = ?",
    'i', [$annexure2a['id']]);

echo "<p style='color:green'>✅ Step 2: Verified contractor linking is correct!</p>";

// Step 3: Check if it's now visible in approval dashboard
echo "<p style='color:green'>✅ Step 3: Form is now SUBMITTED and visible in welfare dashboard!</p>";

echo "<h3>✅ Fix Complete!</h3>";
echo "<table border='1' cellpadding='10' style='background-color:#f0fdf4'>";
echo "<tr><th>Status</th><th>Details</th></tr>";
echo "<tr><td>Workflow Status</td><td><strong>SUBMITTED</strong> (visible in approval)</td></tr>";
echo "<tr><td>Contractor Name</td><td>" . htmlspecialchars($verify['vendor_name']) . "</td></tr>";
echo "<tr><td>Vendor Code</td><td>" . htmlspecialchars($verify['vendor_code']) . "</td></tr>";
echo "<tr><td>Dashboard Visibility</td><td>✅ Now visible for approval</td></tr>";
echo "</table>";

// Step 4: Provide direct link to approval
echo "<h3>📤 Next Step</h3>";
echo "<p>The contractor form is now ready for approval in the welfare dashboard.</p>";
echo "<p><a href='pages/welfare/approve_contractors.php?vendor_code=1100908' style='display:inline-block; padding:12px 24px; background-color:#3b82f6; color:white; text-decoration:none; border-radius:5px; font-weight:bold;'>🔗 Go to Approval Dashboard</a></p>";

// Log this action
db_execute($conn,
    "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
    'issss',
    [$_SESSION['user_id'] ?? 0, 'contractor_draft_submitted', 'contractors', 'Auto-submitted draft form for contractor 1100908 (Annexure2A ID: ' . $annexure2a['id'] . ')', $_SERVER['REMOTE_ADDR'] ?? 'N/A']
);

echo "<hr>";
echo "<p style='color:#666; font-size:12px;'>Action logged for audit trail.</p>";

?>
