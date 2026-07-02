<?php
$p = file_get_contents('pages/admin/permissions.php');
$m = file_get_contents('scratch_patch_mod.txt');
$p = preg_replace('/\$modules\s*=\s*\[.*?\];/s', $m, $p);
file_put_contents('pages/admin/permissions.php', $p);
echo 'Done perm.';

$l = file_get_contents('include/layout.php');
$d = file_get_contents('scratch_patch_dyn.txt');
$l = preg_replace('/\$dynamicModules\s*=\s*\[.*?\];/s', $d, $l);
file_put_contents('include/layout.php', $l);
echo 'Done layout.';
