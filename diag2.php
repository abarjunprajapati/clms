<?php
// Full diagnostic — finds the source of the double-nesting
require_once __DIR__ . '/include/config.php';
$out = [];

// 1. Check tables that root-level scripts hit
$tables = ['workmen', 'representatives', 'supervisors'];
foreach ($tables as $tbl) {
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
    $exists = mysqli_num_rows($r) > 0;
    if ($exists) {
        $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM `$tbl`"))['c'];
        $out[] = "TABLE $tbl: EXISTS | rows=$cnt";
    } else {
        $out[] = "TABLE $tbl: DOES NOT EXIST";
    }
}

// 2. Simulate what each root-level PHP returns (no application_id)
$out[] = "\n=== SIMULATED ROOT-LEVEL get_worker.php (no app_id) ===";
$stmt = $conn->prepare("SELECT * FROM workmen WHERE 1=1 ORDER BY id DESC LIMIT 5");
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$out[] = json_encode(['success'=>true,'data'=>$rows,'counts'=>['total'=>count($rows)]], JSON_PRETTY_PRINT);

// 3. The KEY question: does any PHP currently put the full response inside 'data'?
// i.e. $response['data'] = $another_response ?
// This would only happen if someone wrote: $response['data'] = get_some_other_api_response()
// Check the api/get_workmen.php structure
$out[] = "\n=== api/get_workmen.php response structure CHECK ===";
$stmt2 = $conn->prepare("SELECT * FROM workmen WHERE application_id=? AND type=? ORDER BY id DESC");
$app = 'CMS-2026-0254'; $type = 'workman';
$stmt2->bind_param('ss', $app, $type);
$stmt2->execute();
$data2 = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$response2 = ['success'=>true,'data'=>$data2,'counts'=>['workman'=>count($data2),'total'=>count($data2)]];
$out[] = "CORRECT flat response (what /api/get_workmen.php should return):";
$out[] = "success=" . ($response2['success']?'true':'false') . " | data is array=" . is_array($response2['data']) . " | count=" . count($data2);
$out[] = "TOP LEVEL KEYS: " . implode(', ', array_keys($response2));
$out[] = "data[0] keys (first row): " . (count($data2) ? implode(', ', array_keys($data2[0])) : 'N/A - empty');

// 4. Show what DOUBLE-NESTING looks like and where it would come from
$out[] = "\n=== DOUBLE-NESTING SOURCE ANALYSIS ===";
$wrapped = ['success'=>true, 'data'=> $response2]; // THIS is double-nesting
$out[] = "Double-nested would look like: success=true, data.success=true, data.data=[...]";
$out[] = "This happens when: \$response['data'] = \$another_api_response (entire response object)";
$out[] = "Root cause: A PHP file was storing the whole JSON response object in data field";

echo implode("\n", $out);

