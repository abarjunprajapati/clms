<?php
$lines = file('include/layout.php');
$modules = [];
$dynamicModules = [];
$currentSection = '';

foreach($lines as $line) {
    if (strpos($line, '<a href=') !== false && strpos($line, 'class="sidebar-item"') !== false) {
        if (strpos($line, 'api/logout.php') !== false) continue;
        
        // Extract href
        preg_match('/href="([^"]+)"/', $line, $hrefMatch);
        // Extract icon class
        preg_match('/<i class="([^"]+)"><\/i>/', $line, $iconMatch);
        if (!$iconMatch) {
            preg_match('/class="fas ([^"]+)"/', $line, $iconMatch);
        }
        // Extract label
        $label = trim(strip_tags(substr($line, strpos($line, '</a>') - 100, 100)));
        if (strpos($label, '</i>') !== false) {
            $label = trim(substr($label, strpos($label, '</i>') + 4));
        } else {
            preg_match('/<\/i>\s*(.*?)<\/a>/', $line, $labelMatch);
            $label = $labelMatch ? trim($labelMatch[1]) : 'Unknown';
        }

        $href = $hrefMatch ? $hrefMatch[1] : '';
        $icon = $iconMatch ? explode(' ', $iconMatch[1])[1] : 'fa-circle';
        
        // Create unique key based on href
        $key = str_replace(['.php', '-', '/', '?', '=', '"', "'", '.', '$', 'wb', 'cb', 'cp', 'eb'], '', basename($href));
        $key = str_replace(['dashboard', 'reports', 'attendance', 'compliance'], ['dash_'.$key, 'rep_'.$key, 'att_'.$key, 'comp_'.$key], $key);
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
        
        if (empty($key)) {
            $key = strtolower(preg_replace('/[^a-z0-9]/', '_', $label));
        }

        // Deal with variables in href
        if (strpos($href, '$cb') !== false) {
            $href = "BASE_URL . 'pages/customer/" . str_replace('".$cb."', '', $href) . "'";
            $key = 'cust_' . $key;
        } elseif (strpos($href, '$cp') !== false) {
            $href = "BASE_URL . 'pages/contractor/" . str_replace('".$cp."', '', $href) . "'";
            $key = 'cont_' . $key;
        } elseif (strpos($href, '$wb') !== false) {
            $href = "BASE_URL . 'pages/welfare/" . str_replace('".$wb."', '', $href) . "'";
            $key = 'welf_' . $key;
        } elseif (strpos($href, '$ab') !== false) {
            $href = "BASE_URL . 'pages/admin/" . str_replace('".$ab."', '', $href) . "'";
            $key = 'adm_' . $key;
        } elseif (strpos($href, '$eb') !== false) {
            $href = "BASE_URL . 'pages/execution/" . str_replace('".$eb."', '', $href) . "'";
            $key = 'exec_' . $key;
        } elseif (strpos($href, '../frontline/') !== false) {
            $href = "BASE_URL . 'pages/frontline/" . str_replace('../frontline/', '', $href) . "'";
            $key = 'fl_' . $key;
        } elseif (strpos($href, '../worker/') !== false) {
            $href = "BASE_URL . 'pages/worker/" . str_replace('../worker/', '', $href) . "'";
            $key = 'work_' . $key;
        } else {
            // Relative links in the same folder
            // Need to know context, let's just assume pages/admin for generic, but pass_user etc are in admin
            $href = "BASE_URL . 'pages/admin/" . $href . "'";
        }

        if (strpos($href, "'") === 0) {
            $href = "BASE_URL . 'pages/admin" . substr($href, 1);
        }

        if (!isset($modules[$key])) {
            $modules[$key] = [
                'icon' => $icon,
                'color' => '#6366f1' // default
            ];
            $dynamicModules[$key] = [
                'link' => $href,
                'icon' => $icon,
                'label' => $label
            ];
        }
    }
}

file_put_contents('scratch_modules.txt', var_export($modules, true));
file_put_contents('scratch_dyn_modules.txt', var_export($dynamicModules, true));
echo "Done";
