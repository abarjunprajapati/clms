<?php
$p = file_get_contents('pages/admin/permissions.php');
$lines = explode("\n", $p);
$new_p = [];
foreach($lines as $l) {
    if (strpos($l, 'exe___m_link__') === false) {
        $new_p[] = $l;
    }
}
file_put_contents('pages/admin/permissions.php', implode("\n", $new_p));

$l = file_get_contents('include/layout.php');
$lines = explode("\n", $l);
$new_l = [];
foreach($lines as $line) {
    if (strpos($line, 'exe___m_link__') === false) {
        $new_l[] = $line;
    }
}
file_put_contents('include/layout.php', implode("\n", $new_l));

echo "Cleaned for sure.";
