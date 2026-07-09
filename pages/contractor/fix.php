<?php
$file = 'annexure-2a.php';
$lines = file($file);

// We want to remove lines 1414 to 1760 (1-indexed), so indices 1413 to 1759
if (count($lines) >= 1760 && strpos($lines[1414], 'addEcpBtn') !== false && strpos($lines[1759], '<?php endif; ?>') !== false) {
    array_splice($lines, 1413, 347); // 1759 - 1413 + 1 = 347 lines
    file_put_contents($file, implode("", $lines));
    echo "SUCCESS";
} else {
    echo "ERROR: Bounds not matching. Line 1415 is: " . htmlspecialchars($lines[1414]) . "<br>";
    echo "Line 1760 is: " . htmlspecialchars($lines[1759]);
}
?>
