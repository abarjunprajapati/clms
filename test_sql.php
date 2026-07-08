<?php
require_once __DIR__ . '/include/config.php';
$sql = "SELECT w.id FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id WHERE w.status IN ('verified', 'pending', 'approved') AND w.expected_joining_date <= CURDATE()";
$res = mysqli_query($conn, $sql);
if(!$res) {
    echo "ERROR: " . mysqli_error($conn);
} else {
    echo "SUCCESS: " . mysqli_num_rows($res);
}

$sql2 = "SELECT w.id, w.aadhaar, w.skill_category, w.expected_joining_date, 
                   c.name as contractor_name,
                   (SELECT COUNT(*) FROM documents d 
                    JOIN gate_pass_document_masters gm ON d.document_type = gm.document_name
                    WHERE d.workman_id = w.id AND gm.category = 'pcc' AND d.status = 'approved') as pcc_approved_count
            FROM workmen w
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE w.status IN ('verified', 'pending', 'approved') AND w.expected_joining_date <= CURDATE()";
$res2 = mysqli_query($conn, $sql2);
if(!$res2) {
    echo "\nERROR2: " . mysqli_error($conn);
} else {
    echo "\nSUCCESS2: " . mysqli_num_rows($res2);
}
?>
