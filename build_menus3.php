<?php
$lines = file('include/layout.php');
$modules = [];
$dynamicModules = [];

// Base modules that shouldn't be touched
$base_modules = [
    'dashboard'     => ['icon'=>'fa-tachometer-alt',    'color'=>'#6366f1', 'label'=>'SuperAdmin Dashboard', 'link'=>'pages/admin/dashboard.php'],
    'users'         => ['icon'=>'fa-users',              'color'=>'#8b5cf6', 'label'=>'User Management', 'link'=>'pages/admin/users.php'],
    'contractors'   => ['icon'=>'fa-building',           'color'=>'#3b82f6', 'label'=>'Contractor Management', 'link'=>'pages/admin/contractor_control.php'],
    'workmen'       => ['icon'=>'fa-hard-hat',           'color'=>'#0ea5e9', 'label'=>'Workmen Management', 'link'=>'pages/admin/worker_management.php'],
    'documents'     => ['icon'=>'fa-folder-open',        'color'=>'#f59e0b', 'label'=>'Document Monitor', 'link'=>'pages/admin/documents_monitor.php'],
    'training'      => ['icon'=>'fa-graduation-cap',     'color'=>'#10b981', 'label'=>'Training Monitor', 'link'=>'pages/admin/training_monitor.php'],
    'gate_pass'     => ['icon'=>'fa-id-card',            'color'=>'#14b8a6', 'label'=>'Gate Pass Monitor', 'link'=>'pages/admin/gatepass_monitor.php'],
    'safety'        => ['icon'=>'fa-shield-check',       'color'=>'#059669', 'label'=>'Safety Desk', 'link'=>'pages/safety/dashboard.php'],
    'compliance'    => ['icon'=>'fa-balance-scale',      'color'=>'#6366f1', 'label'=>'Compliance Dashboard', 'link'=>'pages/admin/compliance_dashboard.php'],
    'attendance'    => ['icon'=>'fa-calendar-check',     'color'=>'#ec4899', 'label'=>'Attendance Dashboard', 'link'=>'pages/admin/attendance_dashboard.php'],
    'reports'       => ['icon'=>'fa-chart-bar',          'color'=>'#f97316', 'label'=>'Reports', 'link'=>'pages/admin/reports.php'],
    'noc'           => ['icon'=>'fa-exchange-alt',       'color'=>'#a855f7', 'label'=>'NOC Approvals', 'link'=>'pages/welfare/noc_approvals.php'],
    'sap'           => ['icon'=>'fa-sync',               'color'=>'#64748b', 'label'=>'SAP Logs', 'link'=>'pages/admin/sap_sync_logs.php'],
    'settings'      => ['icon'=>'fa-cog',                'color'=>'#94a3b8', 'label'=>'Settings', 'link'=>'pages/admin/settings.php'],
    'blocking'      => ['icon'=>'fa-ban',                'color'=>'#ef4444', 'label'=>'Blocking Control', 'link'=>'pages/welfare/blocking_control.php'],
    'audit_logs'    => ['icon'=>'fa-history',            'color'=>'#475569', 'label'=>'Audit Logs', 'link'=>'pages/admin/audit_logs.php'],
];

foreach ($base_modules as $k => $v) {
    $modules[$k] = "        '$k' => ['icon'=>'".$v['icon']."', 'color'=>'".$v['color']."'],";
    $dynamicModules[$k] = "                '$k' => ['link' => BASE_URL . '".$v['link']."', 'icon' => '".$v['icon']."', 'label' => '".$v['label']."'],";
}

$current_role = '';

foreach($lines as $line) {
    if (preg_match('/case\s+\'([a-z_]+)\':/', $line, $roleMatch)) {
        $current_role = $roleMatch[1];
    }

    if (strpos($line, '<a href=') !== false && strpos($line, 'class="sidebar-item"') !== false) {
        if (strpos($line, 'api/logout.php') !== false) continue;
        
        // Extract href
        preg_match('/href="([^"]+)"/', $line, $hrefMatch);
        $href = $hrefMatch ? $hrefMatch[1] : '';
        if (strpos($href, '#') !== false || empty($href)) continue;
        if (strpos($href, '__dash_link__') !== false) continue; // skip placeholders
        if (strpos($href, 'exe___m_link__') !== false) continue;
        
        // Extract icon
        preg_match('/<i class="([^"]+)"><\/i>/', $line, $iconMatch);
        if (!$iconMatch) preg_match('/class="fas ([^"]+)"/', $line, $iconMatch);
        $icon = $iconMatch ? explode(' ', trim($iconMatch[1]))[1] ?? 'fa-circle' : 'fa-circle';
        
        // Extract label
        $label = trim(strip_tags(substr($line, strpos($line, '</a>') - 150, 150)));
        if (strpos($label, '</i>') !== false) {
            $label = trim(substr($label, strpos($label, '</i>') + 4));
        } else {
            preg_match('/<\/i>\s*(.*?)<\/a>/', $line, $labelMatch);
            $label = $labelMatch ? trim($labelMatch[1]) : 'Unknown';
        }
        
        // Determine real path based on variables and role
        $real_path = '';
        if (strpos($href, "'.\$cb.'") !== false) $real_path = "pages/customer/" . str_replace("'.\$cb.'", '', $href);
        elseif (strpos($href, "'.\$cp.'") !== false) $real_path = "pages/contractor/" . str_replace("'.\$cp.'", '', $href);
        elseif (strpos($href, "'.\$wb.'") !== false) $real_path = "pages/welfare/" . str_replace("'.\$wb.'", '', $href);
        elseif (strpos($href, "'.\$ab.'") !== false) $real_path = "pages/admin/" . str_replace("'.\$ab.'", '', $href);
        elseif (strpos($href, "'.\$eb.'") !== false) $real_path = "pages/execution/" . str_replace("'.\$eb.'", '', $href);
        elseif (strpos($href, "../frontline/") !== false) $real_path = "pages/frontline/" . str_replace("../frontline/", '', $href);
        elseif (strpos($href, "../worker/") !== false) $real_path = "pages/worker/" . str_replace("../worker/", '', $href);
        else {
            $folder = 'admin';
            if ($current_role == 'welfare_user' || $current_role == 'welfare') $folder = 'welfare';
            if ($current_role == 'safety_user' || $current_role == 'safety') $folder = 'safety';
            if ($current_role == 'execution_officer' || $current_role == 'execution') $folder = 'execution';
            if ($current_role == 'pass_user' || $current_role == 'pass_issuer') $folder = 'admin'; // usually admin folder
            if ($current_role == 'contractor') $folder = 'contractor';
            if ($current_role == 'customer') $folder = 'customer';
            $real_path = "pages/$folder/" . $href;
        }

        $real_path = str_replace(['"', "'", ' '], '', $real_path);

        $base_name = str_replace('.php', '', basename($real_path));
        $base_name = preg_replace('/[^a-zA-Z0-9]/', '_', $base_name);
        
        $prefix = 'adm_';
        if (strpos($real_path, 'welfare') !== false) $prefix = 'wel_';
        if (strpos($real_path, 'contractor') !== false) $prefix = 'con_';
        if (strpos($real_path, 'customer') !== false) $prefix = 'cus_';
        if (strpos($real_path, 'safety') !== false) $prefix = 'saf_';
        if (strpos($real_path, 'execution') !== false) $prefix = 'exe_';
        if (strpos($real_path, 'frontline') !== false) $prefix = 'fro_';
        if (strpos($real_path, 'worker') !== false) $prefix = 'wor_';

        $key = $prefix . $base_name;
        if (empty($label)) $label = ucwords(str_replace('_', ' ', $base_name));

        if (!isset($modules[$key])) {
            $modules[$key] = "        '$key' => ['icon'=>'$icon', 'color'=>'#0ea5e9'],";
            $dynamicModules[$key] = "                '$key' => ['link' => BASE_URL . '$real_path', 'icon' => '$icon', 'label' => '" . addslashes($label) . "'],";
        }
    }
}

$mod_str = "    \$modules    = [\n" . implode("\n", $modules) . "\n    ];";
$dyn_str = "            \$dynamicModules = [\n" . implode("\n", $dynamicModules) . "\n            ];";

file_put_contents('scratch_patch_mod.txt', $mod_str);
file_put_contents('scratch_patch_dyn.txt', $dyn_str);
echo "Ready 3.";
