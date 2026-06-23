<?php
$output = shell_exec("git show c026372:pages/welfare/enrolled_workers.php");
echo "Length: " . strlen($output) . "\n";
echo "First 500 chars:\n" . substr($output, 0, 500) . "\n";
