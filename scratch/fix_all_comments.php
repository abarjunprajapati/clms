<?php
$file = 'pages/welfare/approve_contractors.php';
$content = file_get_contents($file);

// Replace any line starting with spaces, // and containing "SECTION X"
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 1: General[^\n]*/m', '        // SECTION 1: General', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 2: EPF[^\n]*/m', '        // SECTION 2: EPF / ESI', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 3: ECP[^\n]*/m', '        // SECTION 3: ECP', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 4: Work Order[^\n]*/m', '        // SECTION 4: Work Order & SAP', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 5: Labour Licence[^\n]*/m', '        // SECTION 5: Labour Licence', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 6: Banking[^\n]*/m', '        // SECTION 6: Banking', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 7: Remarks[^\n]*/m', '        // SECTION 7: Remarks', $content);
$content = preg_replace('/^[ \t]*\/\/[^\n]*SECTION 8: Documents[^\n]*/m', '        // SECTION 8: Documents', $content);

file_put_contents($file, $content);
echo "Successfully normalized all comments in $file.\n";
?>
