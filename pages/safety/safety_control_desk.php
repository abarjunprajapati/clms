<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);

header('Location: training_class_master.php', true, 302);
exit;
?>
