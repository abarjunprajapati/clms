<?php
$f = 'c:/xampp/htdocs/CLMS1/api/safety/review_retraining.php';
$c = file_get_contents($f);
$c = preg_replace('/if \(strtolower\(\(string\)\$worker\[\'safety_enrollment_status\'\]\) === \'approved\'\) \{.*?\}/s', '// skip safety check', $c);
$c = preg_replace('/db_execute\(\s*\$conn,\s*\"UPDATE workmen.*?WHERE id = \?\",.*?\$workmanId\]\s*\);/s', '// skip workmen update', $c);
file_put_contents($f, $c);
echo "Done";
?>
