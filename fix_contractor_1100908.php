<?php
/**
 * Fix Contractor 1100908 Welfare Form Approval Mismatch
 * 
 * Issue: Form was filled for contractor 1100908, but welfare dashboard shows wrong contractor
 * This script identifies and fixes the data linkage issue
 */

include 'include/config.php';

echo "<h2>🔍 Contractor 1100908 Approval Fix Diagnostic</h2>";
echo "<hr>";

// Step 1: Find all contractors with vendor_code 1100908
$contractors_1100908 = db_fetch_all($conn, 
    "SELECT id, vendor_code, vendor_name, contractor_name, status FROM contractors WHERE vendor_code = '1100908'");

echo "<h3>1. Contractors with Vendor Code 1100908:</h3>";
if (empty($contractors_1100908)) {
    echo "<p style='color:red'>❌ No contractors found with vendor_code 1100908</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Vendor Code</th><th>Vendor Name</th><th>Contractor Name</th><th>Status</th></tr>";
    foreach ($contractors_1100908 as $c) {
        echo "<tr>";
        echo "<td>{$c['id']}</td>";
        echo "<td>{$c['vendor_code']}</td>";
        echo "<td>{$c['vendor_name']}</td>";
        echo "<td>" . ($c['contractor_name'] ?: '<span style="color:orange">[Empty]</span>') . "</td>";
        echo "<td>{$c['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 2: Find annexure2a records linked to contractors with vendor_code 1100908
$annexure2a_records = db_fetch_all($conn,
    "SELECT a.id, a.contractor_id, a.contractor_name, a.workflow_status, a.submitted_at, c.vendor_code, c.vendor_name
     FROM annexure2a a
     JOIN contractors c ON a.contractor_id = c.id
     WHERE c.vendor_code = '1100908'");

echo "<h3>2. Annexure 2A Records Linked to 1100908:</h3>";
if (empty($annexure2a_records)) {
    echo "<p style='color:red'>❌ No annexure2a records found for vendor_code 1100908</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>A2A ID</th><th>Contractor ID</th><th>A2A Contractor Name</th><th>Contractor Table Vendor Name</th><th>Workflow Status</th><th>Match?</th></tr>";
    foreach ($annexure2a_records as $a) {
        $match = ($a['contractor_name'] === $a['vendor_name']) ? '✅ Yes' : '❌ No - MISMATCH';
        echo "<tr>";
        echo "<td>{$a['id']}</td>";
        echo "<td>{$a['contractor_id']}</td>";
        echo "<td>{$a['contractor_name']}</td>";
        echo "<td>{$a['vendor_name']}</td>";
        echo "<td>{$a['workflow_status']}</td>";
        echo "<td style='font-weight:bold;'>{$match}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 3: Check if contractor_name in annexure2a matches contractors table
echo "<h3>3. Data Integrity Check:</h3>";
$mismatches = 0;
foreach ($annexure2a_records as $a) {
    if ($a['contractor_name'] !== $a['vendor_name']) {
        echo "<p style='color:red'>❌ Mismatch Found: Annexure2A record #{$a['id']} shows '{$a['contractor_name']}' but contractor table shows '{$a['vendor_name']}'</p>";
        $mismatches++;
    }
}
if ($mismatches === 0) {
    echo "<p style='color:green'>✅ All records are consistent - No mismatches found</p>";
}

// Step 4: Check if there are multiple contractors with similar names that might be confused
echo "<h3>4. All Contractors in System (to check for similar entries):</h3>";
$all_contractors = db_fetch_all($conn, 
    "SELECT id, vendor_code, vendor_name, contractor_name, status FROM contractors ORDER BY id");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Vendor Code</th><th>Vendor Name</th><th>Contractor Name</th><th>Status</th></tr>";
foreach ($all_contractors as $c) {
    $highlight = ($c['vendor_code'] === '1100908') ? 'style="background-color:yellow"' : '';
    echo "<tr $highlight>";
    echo "<td>{$c['id']}</td>";
    echo "<td>{$c['vendor_code']}</td>";
    echo "<td>{$c['vendor_name']}</td>";
    echo "<td>" . ($c['contractor_name'] ?: '<span style="color:orange">[Empty]</span>') . "</td>";
    echo "<td>{$c['status']}</td>";
    echo "</tr>";
}
echo "</table>";

// Step 5: Provide fix recommendations
echo "<h3>5. Fix Recommendations:</h3>";
if (!empty($annexure2a_records)) {
    $a = $annexure2a_records[0];
    if ($a['contractor_name'] !== $a['vendor_name']) {
        echo "<p>Found mismatch - contractor_name in annexure2a doesn't match vendor_name in contractors table.</p>";
        echo "<p><strong>To fix, run this SQL:</strong></p>";
        echo "<pre>UPDATE annexure2a SET contractor_name = '" . mysqli_real_escape_string($conn, $a['vendor_name']) . "' WHERE id = {$a['id']};</pre>";
        
        // Show update button
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='fix_contractor'>";
        echo "<input type='hidden' name='annexure_id' value='{$a['id']}'>";
        echo "<input type='hidden' name='new_name' value='" . htmlspecialchars($a['vendor_name']) . "'>";
        echo "<button type='submit' style='padding:10px 20px; background-color:green; color:white; border:none; border-radius:5px; cursor:pointer;'>Apply Fix</button>";
        echo "</form>";
    }
}

// Handle fix submission
if ($_POST['action'] === 'fix_contractor') {
    $annexure_id = intval($_POST['annexure_id']);
    $new_name = $_POST['new_name'];
    
    db_execute($conn, 
        "UPDATE annexure2a SET contractor_name = ? WHERE id = ?", 
        'si', 
        [$new_name, $annexure_id]);
    
    echo "<h3 style='color:green'>✅ Fix Applied Successfully!</h3>";
    echo "<p>Annexure2A record #{$annexure_id} has been updated with the correct contractor name.</p>";
    echo "<p><a href='pages/welfare/approve_contractors.php'>Go to Contractor Approval Dashboard</a></p>";
}

?>
