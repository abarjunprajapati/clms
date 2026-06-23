<?php
header('Content-Type: text/plain');

echo "=== System Time ===\n";
echo "PHP Time: " . date('Y-m-d H:i:s') . "\n";

$files = [
    'include/safety_training_control.php',
    'pages/safety/enrollment_approval.php',
    'pages/safety/training_schedule.php',
    'pages/contractor/book_safety_training.php'
];

echo "\n=== File Status ===\n";
foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "[MISSING] $file\n";
        continue;
    }
    
    $mtime = filemtime($file);
    $size = filesize($file);
    $content = file_get_contents($file);
    
    echo "[FOUND] $file\n";
    echo "  Last Modified: " . date('Y-m-d H:i:s', $mtime) . "\n";
    echo "  File Size: $size bytes\n";
    
    // Check specific fixes
    if ($file === 'pages/safety/enrollment_approval.php') {
        $hasFix = strpos($content, 'batch_language') !== false;
        echo "  Excludes Inactive Batches Fix: " . ($hasFix ? "YES (Uploaded)" : "NO (Old file)") . "\n";
    } elseif ($file === 'pages/safety/training_schedule.php') {
        $hasFix = strpos($content, '<> \'inactive\'') !== false || strpos($content, '<> "inactive"') !== false;
        echo "  Excludes Inactive Batches Fix: " . ($hasFix ? "YES (Uploaded)" : "NO (Old file)") . "\n";
    } elseif ($file === 'pages/contractor/book_safety_training.php') {
        $hasFix = strpos($content, 'languageSelect.value = batch.language_name') !== false;
        echo "  Language Sync Fix: " . ($hasFix ? "YES (Uploaded)" : "NO (Old file)") . "\n";
    } elseif ($file === 'include/safety_training_control.php') {
        $hasFix = strpos($content, 'pending_safety') !== false && strpos($content, 'contractor_confirmed') !== false;
        echo "  Untick Status Reset Fix: " . ($hasFix ? "YES (Uploaded)" : "NO (Old file)") . "\n";
    }
}
