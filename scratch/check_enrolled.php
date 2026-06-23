<?php
$content = file_get_contents('pages/welfare/enrolled_workers.php');
$nonNullCount = 0;
for ($i = 0; $i < strlen($content); $i++) {
    if (ord($content[$i]) !== 0) {
        $nonNullCount++;
    }
}
echo "Total bytes: " . strlen($content) . "\n";
echo "Non-null bytes count: " . $nonNullCount . "\n";
