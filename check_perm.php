<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dir = __DIR__ . '/uploads/documents';
echo "Checking directory: $dir<br>";

if (!file_exists($dir)) {
    echo "Directory does not exist. Attempting to create...<br>";
    if (mkdir($dir, 0777, true)) {
        echo "Successfully created.<br>";
    } else {
        echo "FAILED to create. Check parent permissions.<br>";
    }
}

if (is_writable($dir)) {
    echo "Directory is WRITABLE. ✅<br>";
} else {
    echo "Directory is NOT WRITABLE. ❌<br>";
    echo "Attempting chmod...<br>";
    if (chmod($dir, 0777)) {
        echo "Chmod successful.<br>";
    } else {
        echo "Chmod FAILED. ❌<br>";
    }
}

echo "Current user: " . get_current_user() . "<br>";
echo "PHP Process User: " . exec('whoami') . "<br>";
echo "Directory Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br>";

// Test a write
$testFile = $dir . '/test.txt';
if (@file_put_contents($testFile, 'test')) {
    echo "Test write successful! ✅<br>";
    unlink($testFile);
} else {
    echo "Test write FAILED! ❌<br>";
}
?>
