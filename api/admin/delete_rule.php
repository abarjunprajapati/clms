<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    if (db_execute($conn, "DELETE FROM business_rules WHERE id = ?", 'i', [$id])) {
        header("Location: ../../pages/admin/policy_monitor.php?success=Rule deleted");
    } else {
        header("Location: ../../pages/admin/policy_monitor.php?error=Delete failed");
    }
} else {
    header("Location: ../../pages/admin/policy_monitor.php");
}
?>
