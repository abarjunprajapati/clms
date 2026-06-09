<?php
/**
 * Batch fix all checkAuth() calls across the pages directory
 * Replaces stale role aliases with exact DB roles.
 * 
 * Alias mapping:
 *   'admin'       => 'super_admin'
 *   'welfare'     => 'welfare_user'
 *   'safety'      => 'safety_user'
 *   'pass_issuer' => 'pass_user'
 *   'frontline'   => 'front_line_user'
 *   'execution'   => 'execution_officer'
 */

$aliasMap = [
    "'admin'"       => "'super_admin'",
    "'welfare'"     => "'welfare_user'",
    "'safety'"      => "'safety_user'",
    "'pass_issuer'" => "'pass_user'",
    "'frontline'"   => "'front_line_user'",
    "'execution'"   => "'execution_officer'",
];

$pagesDir = __DIR__ . '/../pages';
$apiDir   = __DIR__ . '/../api';

$dirs = [$pagesDir, $apiDir];
$changed = 0;
$filesChanged = [];

foreach ($dirs as $baseDir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;

        // Fix checkAuth arrays
        $content = preg_replace_callback(
            '/checkAuth\(\[([^\]]+)\]\)/',
            function ($matches) use ($aliasMap) {
                $inner = $matches[1];
                foreach ($aliasMap as $old => $new) {
                    $inner = str_replace($old, $new, $inner);
                }
                // Deduplicate roles
                $roles = array_map('trim', explode(',', $inner));
                $roles = array_unique($roles);
                $inner = implode(', ', $roles);
                return "checkAuth([{$inner}])";
            },
            $content
        );

        // Fix enforceRole arrays
        $content = preg_replace_callback(
            '/enforceRole\(\[([^\]]+)\]\)/',
            function ($matches) use ($aliasMap) {
                $inner = $matches[1];
                foreach ($aliasMap as $old => $new) {
                    $inner = str_replace($old, $new, $inner);
                }
                $roles = array_map('trim', explode(',', $inner));
                $roles = array_unique($roles);
                $inner = implode(', ', $roles);
                return "enforceRole([{$inner}])";
            },
            $content
        );

        if ($content !== $original) {
            file_put_contents($path, $content);
            $changed++;
            $filesChanged[] = str_replace('\\', '/', str_replace(dirname(__DIR__) . '\\', '', $path));
        }
    }
}

echo "Fixed $changed files.\n";
foreach ($filesChanged as $f) {
    echo "  - $f\n";
}
