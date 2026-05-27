<?php
// PHP Script to move files and update paths

$moves = [
    // Welfare
    'welfare-verification.php' => 'welfare/verification.php',
    'welfare-approval.php' => 'welfare/approval.php',
    'welfare-pass-approval.php' => 'welfare/gatepass.php',
    
    // Contractor
    'contractor-dashboard.php' => 'contractor/dashboard.php',
    'annexure-2a.php' => 'contractor/annexure.php',
    'enrolment-4a.php' => 'contractor/add-worker.php',
    'gatepass-6a.php' => 'contractor/gatepass.php',
    'safety-training-request.php' => 'contractor/training.php'
];

$baseDir = __DIR__ . '/pages/';

foreach ($moves as $src => $dst) {
    $srcPath = $baseDir . $src;
    $dstPath = $baseDir . $dst;
    
    if (file_exists($srcPath)) {
        // Read content
        $content = file_get_contents($srcPath);
        
        // Update paths
        $content = str_replace(
            ["include '../include/", "include_once '../include/", "require '../include/", "require_once '../include/"],
            ["include '../../include/", "include_once '../../include/", "require '../../include/", "require_once '../../include/"],
            $content
        );
        
        $content = str_replace(
            ["href=\"../css/", "src=\"../js/"],
            ["href=\"../../css/", "src=\"../../js/"],
            $content
        );
        
        // Add auth check at the top
        // Find <?php and insert after it
        $authLogic = "";
        if (strpos($dst, 'welfare/') === 0) {
            $authLogic = "\nrequire_once '../../include/auth.php';\ncheckAuth(['welfare', 'admin', 'acc']);\n";
        } else if (strpos($dst, 'contractor/') === 0) {
            $authLogic = "\nrequire_once '../../include/auth.php';\ncheckAuth(['contractor', 'admin']);\n";
        }

        // Replace old session start / config includes
        $content = preg_replace('/session_start\(\);/', '', $content);
        $content = preg_replace('/if\s*\(!isset\(\$_SESSION\[.*?header\([^)]+\);\s*exit.*?\}/s', '', $content); // remove old redirect block
        
        // Insert auth logic just before include config
        $content = str_replace("include '../../include/config.php';", $authLogic . "include '../../include/config.php';", $content);

        // Save
        file_put_contents($dstPath, $content);
        echo "Moved & Updated: $src -> $dst\n";
        
        // Optional: delete original if needed (we'll keep it for backup temporarily or just delete)
        unlink($srcPath);
    } else {
        echo "File not found: $src\n";
    }
}

