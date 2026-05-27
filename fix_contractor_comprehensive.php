<?php
/**
 * Contractor 1100908 - Comprehensive Status & Data Fix
 */

include 'include/config.php';

echo "<h2>🔧 Contractor 1100908 - Status & Approval Fix</h2>";
echo "<hr>";

// Get contractor 1100908
$contractor = db_single($conn, 
    "SELECT id, vendor_code, vendor_name, contractor_name, status FROM contractors WHERE vendor_code = '1100908'");

if (!$contractor) {
    echo "<p style='color:red'>❌ Contractor 1100908 not found</p>";
    exit;
}

echo "<h3>Current Contractor Record (ID: {$contractor['id']})</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>ID</td><td>{$contractor['id']}</td></tr>";
echo "<tr><td>Vendor Code</td><td>{$contractor['vendor_code']}</td></tr>";
echo "<tr><td>Vendor Name</td><td>{$contractor['vendor_name']}</td></tr>";
echo "<tr><td>Contractor Name</td><td>{$contractor['contractor_name']}</td></tr>";
echo "<tr><td>Status</td><td>{$contractor['status']}</td></tr>";
echo "</table>";

// Get all annexure2a records for this contractor (ANY status)
$annexure2a_all = db_fetch_all($conn,
    "SELECT id, contractor_id, contractor_name, workflow_status, submitted_at 
     FROM annexure2a 
     WHERE contractor_id = ?",
    'i', [$contractor['id']]);

echo "<h3>All Annexure 2A Records for This Contractor:</h3>";
if (empty($annexure2a_all)) {
    echo "<p style='color:orange'>⚠️ No annexure2a records found</p>";
} else {
    echo "<table border='1' cellpadding='10' style='width:100%'>";
    echo "<tr><th>ID</th><th>Contractor Name in A2A</th><th>Workflow Status</th><th>Submitted At</th><th>Visible in Approval?</th></tr>";
    foreach ($annexure2a_all as $a) {
        $visible = in_array($a['workflow_status'], ['submitted', 'under_review', 'pending']) ? '✅ Yes' : '❌ No (' . $a['workflow_status'] . ')';
        $match = ($a['contractor_name'] === $contractor['vendor_name']) ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$a['id']}</td>";
        echo "<td>{$a['contractor_name']} $match</td>";
        echo "<td>{$a['workflow_status']}</td>";
        echo "<td>" . ($a['submitted_at'] ? $a['submitted_at'] : 'N/A') . "</td>";
        echo "<td>{$visible}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Show all contractors to check for potential confusion
echo "<h3>🔍 All Contractors in System (Check for Duplicate/Similar):</h3>";
$all = db_fetch_all($conn, 
    "SELECT id, vendor_code, vendor_name, contractor_name, status FROM contractors ORDER BY id");

echo "<table border='1' cellpadding='10' style='width:100%'>";
echo "<tr><th>ID</th><th>Vendor Code</th><th>Vendor Name</th><th>Contractor Name</th><th>Status</th><th>Has A2A?</th></tr>";

foreach ($all as $c) {
    $a2a_count = db_single($conn, 
        "SELECT COUNT(*) as cnt FROM annexure2a WHERE contractor_id = ?", 'i', [$c['id']]);
    $has_a2a = $a2a_count['cnt'] > 0 ? "✅ ({$a2a_count['cnt']})" : "❌";
    
    $bg = ($c['id'] === $contractor['id']) ? 'style="background-color:#ffffcc"' : '';
    echo "<tr $bg>";
    echo "<td>{$c['id']}</td>";
    echo "<td><strong>{$c['vendor_code']}</strong></td>";
    echo "<td>{$c['vendor_name']}</td>";
    echo "<td>" . ($c['contractor_name'] ?: '<span style="color:orange">[Empty]</span>') . "</td>";
    echo "<td>{$c['status']}</td>";
    echo "<td>{$has_a2a}</td>";
    echo "</tr>";
}
echo "</table>";

// Provide a form to submit the draft if needed
if (!empty($annexure2a_all)) {
    $draft_record = null;
    foreach ($annexure2a_all as $a) {
        if ($a['workflow_status'] === 'draft') {
            $draft_record = $a;
            break;
        }
    }
    
    if ($draft_record) {
        echo "<h3>⚠️ Draft Form Found</h3>";
        echo "<p>Annexure2A record ID {$draft_record['id']} is still in DRAFT status.</p>";
        echo "<p><strong>Action Required:</strong> Submit this form first before it can be approved in the welfare dashboard.</p>";
        
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='submit_draft'>";
        echo "<input type='hidden' name='annexure_id' value='{$draft_record['id']}'>";
        echo "<button type='submit' style='padding:10px 20px; background-color:#3b82f6; color:white; border:none; border-radius:5px; cursor:pointer;'>📤 Submit Form for Approval</button>";
        echo "</form>";
    }
}

// Handle form submission
if ($_POST['action'] === 'submit_draft') {
    $annexure_id = intval($_POST['annexure_id']);
    
    db_execute($conn, 
        "UPDATE annexure2a SET workflow_status = 'submitted', submitted_at = NOW() WHERE id = ?", 
        'i', 
        [$annexure_id]);
    
    echo "<h3 style='color:green'>✅ Form Submitted Successfully!</h3>";
    echo "<p>Annexure2A record #{$annexure_id} is now submitted and visible in the welfare approval dashboard.</p>";
    echo "<p><a href='pages/welfare/approve_contractors.php?vendor_code=1100908' style='padding:10px 20px; background-color:#10b981; color:white; text-decoration:none; border-radius:5px;'>Go to Approval Dashboard</a></p>";
}

// Check if contractor name needs correction
echo "<h3>🔧 Data Correction Panel</h3>";

if (!empty($annexure2a_all)) {
    foreach ($annexure2a_all as $a) {
        if ($a['contractor_name'] !== $contractor['vendor_name']) {
            echo "<p style='color:red'>❌ Found Name Mismatch in Record #{$a['id']}</p>";
            echo "<p>Annexure2A shows: <strong>{$a['contractor_name']}</strong></p>";
            echo "<p>Should be: <strong>{$contractor['vendor_name']}</strong></p>";
            
            echo "<form method='POST' style='margin-top:10px;'>";
            echo "<input type='hidden' name='action' value='fix_name'>";
            echo "<input type='hidden' name='annexure_id' value='{$a['id']}'>";
            echo "<input type='hidden' name='correct_name' value='" . htmlspecialchars($contractor['vendor_name']) . "'>";
            echo "<button type='submit' style='padding:8px 16px; background-color:#f59e0b; color:white; border:none; border-radius:5px; cursor:pointer;'>🔧 Correct Name</button>";
            echo "</form>";
        }
    }
}

// Handle name correction
if ($_POST['action'] === 'fix_name') {
    $annexure_id = intval($_POST['annexure_id']);
    $correct_name = $_POST['correct_name'];
    
    db_execute($conn, 
        "UPDATE annexure2a SET contractor_name = ? WHERE id = ?", 
        'si', 
        [$correct_name, $annexure_id]);
    
    echo "<h3 style='color:green'>✅ Name Corrected!</h3>";
    echo "<p>Annexure2A record #{$annexure_id} contractor name has been updated to: <strong>{$correct_name}</strong></p>";
}

?>
