<?php
$folders = [
    __DIR__ . '/../cache',
    __DIR__ . '/../storage/cache',
    __DIR__ . '/../storage/temp',
    __DIR__ . '/../sessions',
];

$expireSeconds = 60 * 60 * 1; // 6 hours old files only

foreach ($folders as $dir) {
    if (!is_dir($dir)) continue;

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $path = $item->getPathname();

        // safety: important files skip
        if (preg_match('/(\.htaccess|index\.php|\.gitkeep)$/i', basename($path))) {
            continue;
        }

        if ($item->isFile() && time() - $item->getMTime() > $expireSeconds) {
            @unlink($path);
        }

        if ($item->isDir()) {
            @rmdir($path); // empty folder only
        }
    }
}

echo "Old cache cleaned: " . date('Y-m-d H:i:s');