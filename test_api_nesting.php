<?php
// Test the API directly to see if it double-nests
$_GET['application_id'] = 'CMS-2026-0254';
$_GET['type'] = 'workman';

echo "--- api/get_workmen.php ---\n";
ob_start();
include 'api/get_workmen.php';
$out1 = ob_get_clean();
echo $out1 . "\n\n";

echo "--- api/get_representatives.php ---\n";
ob_start();
include 'api/get_representatives.php';
$out2 = ob_get_clean();
echo $out2 . "\n\n";

echo "--- api/get_supervisors.php ---\n";
ob_start();
include 'api/get_supervisors.php';
$out3 = ob_get_clean();
echo $out3 . "\n\n";

