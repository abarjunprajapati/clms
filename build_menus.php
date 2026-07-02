<?php
$lines = file('include/layout.php');
$modules = [];
$dynamicModules = [];

$modules['dashboard'] = "        'dashboard'     => ['icon'=>'fa-tachometer-alt',    'color'=>'#6366f1'],";
$modules['users'] = "        'users'         => ['icon'=>'fa-users',              'color'=>'#8b5cf6'],";
$modules['contractors'] = "        'contractors'   => ['icon'=>'fa-building',           'color'=>'#3b82f6'],";
$modules['workmen'] = "        'workmen'       => ['icon'=>'fa-hard-hat',           'color'=>'#0ea5e9'],";
$modules['documents'] = "        'documents'     => ['icon'=>'fa-folder-open',        'color'=>'#f59e0b'],";
$modules['training'] = "        'training'      => ['icon'=>'fa-graduation-cap',     'color'=>'#10b981'],";
$modules['gate_pass'] = "        'gate_pass'     => ['icon'=>'fa-id-card',            'color'=>'#14b8a6'],";
$modules['safety'] = "        'safety'        => ['icon'=>'fa-shield-check',       'color'=>'#059669'],";
$modules['compliance'] = "        'compliance'    => ['icon'=>'fa-balance-scale',      'color'=>'#6366f1'],";
$modules['attendance'] = "        'attendance'    => ['icon'=>'fa-calendar-check',     'color'=>'#ec4899'],";
$modules['reports'] = "        'reports'       => ['icon'=>'fa-chart-bar',          'color'=>'#f97316'],";
$modules['noc'] = "        'noc'           => ['icon'=>'fa-exchange-alt',       'color'=>'#a855f7'],";
$modules['sap'] = "        'sap'           => ['icon'=>'fa-sync',               'color'=>'#64748b'],";
$modules['settings'] = "        'settings'      => ['icon'=>'fa-cog',                'color'=>'#94a3b8'],";
$modules['blocking'] = "        'blocking'      => ['icon'=>'fa-ban',                'color'=>'#ef4444'],";
$modules['audit_logs'] = "        'audit_logs'    => ['icon'=>'fa-history',            'color'=>'#475569'],";

$dynamicModules['users'] = "                'users'       => ['link' => BASE_URL . 'pages/admin/users.php', 'icon' => 'fa-users', 'label' => 'User Management'],";
$dynamicModules['contractors'] = "                'contractors' => ['link' => BASE_URL . 'pages/admin/contractor_control.php', 'icon' => 'fa-building', 'label' => 'Contractors'],";
$dynamicModules['workmen'] = "                'workmen'     => ['link' => BASE_URL . 'pages/admin/worker_management.php', 'icon' => 'fa-hard-hat', 'label' => 'Workmen'],";
$dynamicModules['documents'] = "                'documents'   => ['link' => BASE_URL . 'pages/admin/documents_monitor.php', 'icon' => 'fa-folder-open', 'label' => 'Documents'],";
$dynamicModules['training'] = "                'training'    => ['link' => BASE_URL . 'pages/admin/training_monitor.php', 'icon' => 'fa-graduation-cap', 'label' => 'Training'],";
$dynamicModules['gate_pass'] = "                'gate_pass'   => ['link' => BASE_URL . 'pages/admin/gatepass_monitor.php', 'icon' => 'fa-id-card', 'label' => 'Gate Pass'],";
$dynamicModules['safety'] = "                'safety'      => ['link' => BASE_URL . 'pages/safety/dashboard.php', 'icon' => 'fa-shield-check', 'label' => 'Safety Desk'],";
$dynamicModules['compliance'] = "                'compliance'  => ['link' => BASE_URL . 'pages/admin/compliance_dashboard.php', 'icon' => 'fa-balance-scale', 'label' => 'Compliance'],";
$dynamicModules['attendance'] = "                'attendance'  => ['link' => BASE_URL . 'pages/admin/attendance_dashboard.php', 'icon' => 'fa-calendar-check', 'label' => 'Attendance'],";
$dynamicModules['reports'] = "                'reports'     => ['link' => BASE_URL . 'pages/admin/reports.php', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],";
$dynamicModules['noc'] = "                'noc'         => ['link' => BASE_URL . 'pages/welfare/noc_approvals.php', 'icon' => 'fa-exchange-alt', 'label' => 'NOC Approvals'],";
$dynamicModules['sap'] = "                'sap'         => ['link' => BASE_URL . 'pages/admin/sap_sync_logs.php', 'icon' => 'fa-sync', 'label' => 'SAP Logs'],";
$dynamicModules['settings'] = "                'settings'    => ['link' => BASE_URL . 'pages/admin/settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],";
$dynamicModules['blocking'] = "                'blocking'    => ['link' => BASE_URL . 'pages/welfare/blocking_control.php', 'icon' => 'fa-ban', 'label' => 'Blocking Control'],";
$dynamicModules['audit_logs'] = "                'audit_logs'  => ['link' => BASE_URL . 'pages/admin/audit_logs.php', 'icon' => 'fa-history', 'label' => 'Audit Logs'],";

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
        $label = trim(strip_tags(substr($line, strpos($line, '</a>') - 100, 100)));
        if (strpos($label, '</i>') !== false) {
            $label = trim(substr($label, strpos($label, '</i>') + 4));
        } else {
            preg_match('/<\/i>\s*(.*?)<\/a>/', $line, $labelMatch);
            $label = $labelMatch ? trim($labelMatch[1]) : 'Unknown';
        }

        $href = $hrefMatch ? $hrefMatch[1] : '';
        $icon = $iconMatch ? explode(' ', $iconMatch[1])[1] : 'fa-circle';
        
        // Clean href
        $real_href = str_replace(['".$cb."', '".$cp."', '".$wb."', '".$ab."', '".$eb."'], ['pages/customer/', 'pages/contractor/', 'pages/welfare/', 'pages/admin/', 'pages/execution/'], $href);
        if (strpos($real_href, '../frontline/') !== false) $real_href = str_replace('../frontline/', 'pages/frontline/', $real_href);
        if (strpos($real_href, '../worker/') !== false) $real_href = str_replace('../worker/', 'pages/worker/', $real_href);
        
        // If it starts with a letter (relative to admin)
        if (strpos($real_href, 'pages/') === false && strpos($real_href, 'http') === false) {
            if (strpos($real_href, 'training_') !== false || strpos($real_href, 'instructor_') !== false || strpos($real_href, 'enrollment_') !== false || strpos($real_href, 'upcoming_') !== false || strpos($real_href, 'retraining') !== false) {
                // assume safety if we are near safety chunk
                $real_href = 'pages/safety/' . $real_href;
            } elseif (strpos($real_href, 'pass_') !== false || strpos($real_href, 'issue_') !== false || strpos($real_href, 'acc_') !== false || strpos($real_href, 'verify_') !== false || strpos($real_href, 'reupload') !== false) {
                $real_href = 'pages/admin/' . $real_href;
            } elseif (strpos($real_href, 'annexure') !== false || strpos($real_href, 'enrolment-') !== false || strpos($real_href, 'gatepass') !== false || strpos($real_href, 'payment') !== false || strpos($real_href, 'book_') !== false || strpos($real_href, 'esi') !== false || strpos($real_href, 'epf') !== false || strpos($real_href, 'lwf') !== false || strpos($real_href, 'monthly_') !== false || strpos($real_href, 'noc_') !== false) {
                $real_href = 'pages/contractor/' . $real_href;
            } else {
                $real_href = 'pages/admin/' . $real_href;
            }
        }
        
        $key = str_replace(['.php', '-', '/', '?', '=', '"', "'", '.', 'pages'], '', basename($real_href));
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
        
        if (strpos($real_href, 'welfare') !== false) $key = 'wf_' . $key;
        elseif (strpos($real_href, 'contractor') !== false) $key = 'ct_' . $key;
        elseif (strpos($real_href, 'customer') !== false) $key = 'cu_' . $key;
        elseif (strpos($real_href, 'frontline') !== false) $key = 'fl_' . $key;
        elseif (strpos($real_href, 'execution') !== false) $key = 'ex_' . $key;
        elseif (strpos($real_href, 'safety') !== false) $key = 'sf_' . $key;
        else $key = 'ad_' . $key;
        
        $key = substr($key, 0, 30); // truncate

        // skip if already explicitly added in my manual list earlier (to avoid duplicates)
        // I will just let it overwrite or add.
        
        $modules[$key] = "        '$key' => ['icon'=>'$icon', 'color'=>'#0ea5e9'],";
        $dynamicModules[$key] = "                '$key' => ['link' => BASE_URL . '$real_href', 'icon' => '$icon', 'label' => '" . addslashes($label) . "'],";
    }
}

// Generate replacement chunks
$mod_str = "    \$modules    = [\n" . implode("\n", $modules) . "\n    ];";
$dyn_str = "            \$dynamicModules = [\n" . implode("\n", $dynamicModules) . "\n            ];";

file_put_contents('scratch_patch_mod.txt', $mod_str);
file_put_contents('scratch_patch_dyn.txt', $dyn_str);
echo "Ready.";
