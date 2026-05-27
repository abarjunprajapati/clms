<?php
$oldDir = __DIR__ . '/uploads';
$newDir = __DIR__ . '/uploads_new';

echo "<pre>";
if (file_exists($oldDir)) {
    echo "Renaming existing uploads to uploads_backup_" . time() . "...\n";
    rename($oldDir, $oldDir . '_backup_' . time());
}

echo "Creating new uploads directory as PHP user...\n";
if (mkdir($oldDir, 0777, true)) {
    chmod($oldDir, 0777);
    mkdir($oldDir . '/documents', 0777, true);
    chmod($oldDir . '/documents', 0777);
    echo "SUCCESS: New writable uploads directory created! ✅\n";
} else {
    echo "FAILED: Could not create directory. Parent folder might be locked. ❌\n";
}
echo "</pre>";
?>
