<?php
require_once __DIR__ . '/include/config.php';
$out = [];

// Schema of representatives table
$out[] = "=== representatives TABLE STRUCTURE ===";
$r = mysqli_query($conn, "DESCRIBE representatives");
if ($r) { while($row=mysqli_fetch_assoc($r)) $out[] = "{$row['Field']} | {$row['Type']} | NULL:{$row['Null']}"; }
else $out[] = "ERROR: ".mysqli_error($conn);

// Data in representatives table  
$out[] = "\n=== representatives TABLE DATA ===";
$r2 = mysqli_query($conn, "SELECT * FROM representatives LIMIT 5");
if ($r2 && mysqli_num_rows($r2)>0) { while($row=mysqli_fetch_assoc($r2)) $out[] = json_encode($row); }
else $out[] = "No rows";

// Schema of supervisors table
$out[] = "\n=== supervisors TABLE STRUCTURE ===";
$r3 = mysqli_query($conn, "DESCRIBE supervisors");
if ($r3) { while($row=mysqli_fetch_assoc($r3)) $out[] = "{$row['Field']} | {$row['Type']} | NULL:{$row['Null']}"; }
else $out[] = "ERROR: ".mysqli_error($conn);

// Data in supervisors table
$out[] = "\n=== supervisors TABLE DATA ===";
$r4 = mysqli_query($conn, "SELECT * FROM supervisors LIMIT 5");
if ($r4 && mysqli_num_rows($r4)>0) { while($row=mysqli_fetch_assoc($r4)) $out[] = json_encode($row); }
else $out[] = "No rows";

// Distinct types in workmen table
$out[] = "\n=== DISTINCT type VALUES IN workmen ===";
$r5 = mysqli_query($conn, "SELECT DISTINCT type FROM workmen");
while($row=mysqli_fetch_assoc($r5)) $out[] = "type: ".$row['type'];

echo implode("\n", $out);

