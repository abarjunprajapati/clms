<?php
$search_dir = __DIR__ . '/../';
$search_terms = ['ecp_number', 'license_no'];

function search_in_dir($dir, $terms) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            // Exclude directories like .lh, .git, .gemini
            if (in_array($file, ['.lh', '.git', '.gemini', 'node_modules', 'vendor'])) continue;
            search_in_dir($path, $terms);
        } else {
            $content = file_get_contents($path);
            foreach ($terms as $term) {
                if (strpos($content, $term) !== false) {
                    echo "Found '$term' in $path\n";
                }
            }
        }
    }
}

search_in_dir($search_dir, $search_terms);
?>
