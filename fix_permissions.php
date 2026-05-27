<?php
$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/documents',
    __DIR__ . '/uploads/photos',
];

echo "<pre>";
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created: $dir\n";
        } else {
            echo "FAILED to create: $dir\n";
        }
    }
    
    if (chmod($dir, 0777)) {
        echo "Permissions set to 0777: $dir\n";
    } else {
        echo "FAILED to set permissions: $dir\n";
    }
}
echo "</pre>";
?>
