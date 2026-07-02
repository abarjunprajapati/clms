<?php
$f = file('c:\\xampp\\htdocs\\CLMS1\\pages\\contractor\\annexure-2a.php');
array_splice($f, 106, 106);
file_put_contents('c:\\xampp\\htdocs\\CLMS1\\pages\\contractor\\annexure-2a.php', implode('', $f));
echo "Cleaned up";
?>
