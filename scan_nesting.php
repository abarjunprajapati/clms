<?php
// Find the double nesting source
$files = glob(__DIR__ . '/api/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, "'data' => \$") !== false || strpos($content, '"data" => $') !== false) {
        echo "POSSIBLE NESTING IN: " . basename($file) . "\n";
        // Show context around the match
        preg_match_all('/.{0,50}data.{0,50}=>.{0,50}/', $content, $matches);
        foreach ($matches[0] as $m) echo "  CONTEXT: $m\n";
    }
}

// Also check root level
$files = glob(__DIR__ . '/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, "'data' => \$") !== false || strpos($content, '"data" => $') !== false) {
        echo "POSSIBLE NESTING IN ROOT: " . basename($file) . "\n";
    }
}

