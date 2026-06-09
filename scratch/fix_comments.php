<?php
$file = 'pages/welfare/approve_contractors.php';
$content = file_get_contents($file);

// Replace decorative comment lines with clean simple comments
$content = preg_replace('/\/\/ ─+ SECTION 1: General ─+/u', '// SECTION 1: General', $content);
$content = preg_replace('/\/\/ ─+ SECTION 2: EPF \/ ESI ─+/u', '// SECTION 2: EPF / ESI', $content);
$content = preg_replace('/\/\/ ─+ SECTION 3: ECP ─+/u', '// SECTION 3: ECP', $content);
$content = preg_replace('/\/\/ ─+ SECTION 4: Work Order & SAP ─+/u', '// SECTION 4: Work Order & SAP', $content);
$content = preg_replace('/\/\/ ─+ SECTION 5: Labour Licence ─+/u', '// SECTION 5: Labour Licence', $content);
$content = preg_replace('/\/\/ ─+ SECTION 6: Banking ─+/u', '// SECTION 6: Banking', $content);
$content = preg_replace('/\/\/ ─+ SECTION 7: Remarks ─+/u', '// SECTION 7: Remarks', $content);
$content = preg_replace('/\/\/ ─+ SECTION 8: Documents ─+/u', '// SECTION 8: Documents', $content);

file_put_contents($file, $content);
echo "Cleaned up comment lines in $file.\n";
?>
