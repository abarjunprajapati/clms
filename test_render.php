<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';
$_SESSION['name'] = 'Test';
$_SESSION['contractor_id'] = '1100914';
$_SESSION['logged_in'] = true;

// Mock auth.php
if (!file_exists(__DIR__ . '/include/auth.php')) {
    mkdir(__DIR__ . '/include', 0777, true);
    file_put_contents(__DIR__ . '/include/auth.php', "<?php function checkAuth(\$roles) {} ?>");
}
if (!file_exists(__DIR__ . '/include/layout.php')) {
    file_put_contents(__DIR__ . '/include/layout.php', "<?php function renderLayout(\$title, \$contentFunc, \$role, \$name) { call_user_func(\$contentFunc); } ?>");
}
if (!file_exists(__DIR__ . '/include/labour_license_threshold.php')) {
    file_put_contents(__DIR__ . '/include/labour_license_threshold.php', "<?php function clms_get_labour_license_threshold(\$conn) { return 5; } ?>");
}

chdir(__DIR__ . '/pages/contractor');
require_once 'annexure-2a.php';

ob_start();
renderContent();
$out = ob_get_clean();

echo "\n\n--- RENDER SUCCESS --- Length: " . strlen($out) . " bytes\n";
?>
