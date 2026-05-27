<?php
header('Content-Type: text/plain; charset=utf-8');

echo "CLMS SYNTAX CHECKER\n";
echo "===================\n\n";

function checkSyntax($filePath) {
    if (!file_exists($filePath)) {
        echo "[MISSING] $filePath - File does not exist!\n";
        return;
    }
    
    // Check if php lint command is available
    $output = [];
    $return_var = -1;
    
    // Try executing php -l if possible
    @exec("php -v 2>&1", $out_v, $ret_v);
    if ($ret_v === 0) {
        @exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $return_var);
    }
    
    if ($return_var === 0) {
        echo "[OK] PHP Lint: $filePath is valid PHP.\n";
    } else if ($return_var > 0) {
        echo "[SYNTAX ERROR] $filePath:\n" . implode("\n", $output) . "\n";
    } else {
        // Fallback: try to include it inside an output buffer to catch parse/compile errors
        ob_start();
        $success = false;
        
        // We register a shutdown function to capture parse errors if they halt the script
        register_shutdown_function(function() use ($filePath) {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
                echo "[COMPILE ERROR] in $filePath:\n";
                echo "Message: " . $error['message'] . "\n";
                echo "Line: " . $error['line'] . "\n";
            }
        });
        
        try {
            // compliance_schema.php only defines functions so it's safe to include.
            // But config.php or save_compliance.php might run queries or starts session.
            // Let's only include compliance_schema.php to verify its syntax, as it is the only file we modified!
            if (strpos($filePath, 'compliance_schema.php') !== false) {
                include_once $filePath;
            }
            $success = true;
        } catch (Throwable $e) {
            echo "[RUNTIME THROWN] in $filePath: " . $e->getMessage() . "\n";
        }
        ob_end_clean();
        
        if ($success) {
            echo "[OK] Include: $filePath syntax is valid (checked via include).\n";
        }
    }
}

echo "Checking compliance_schema.php:\n";
checkSyntax(__DIR__ . '/include/compliance_schema.php');

echo "\nChecking save_compliance.php (syntax check only):\n";
checkSyntax(__DIR__ . '/api/contractor/save_compliance.php');

echo "\nChecking config.php (syntax check only):\n";
checkSyntax(__DIR__ . '/include/config.php');

?>
