<?php
$path = __DIR__ . '/uploads';

echo "<pre>";
echo "Attempting to fix permissions for: $path\n";

function recursiveChmod($path) {
    if (!file_exists($path)) return;
    
    echo "Processing: $path\n";
    @chmod($path, 0777);
    
    if (is_dir($path)) {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            recursiveChmod($path . '/' . $item);
        }
    }
}

recursiveChmod($path);

echo "\nFinal Check:\n";
if (is_writable($path . '/documents')) {
    echo "SUCCESS: /uploads/documents is now writable! ✅\n";
} else {
    echo "FAILED: Still not writable. ❌\n";
    echo "You MUST run this command via SSH:\n";
    echo "chmod -R 777 " . realpath($path) . "\n";
}
echo "</pre>";
?>
