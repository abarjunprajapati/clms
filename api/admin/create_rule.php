<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule_code = $_POST['rule_code'] ?? '';
    $rule_name = $_POST['rule_name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($rule_code) || empty($rule_name)) {
        header("Location: ../../pages/admin/policy_monitor.php?error=Missing required fields");
        exit;
    }

    $sql = "INSERT INTO business_rules (rule_code, rule_name, description, is_active) VALUES (?, ?, ?, 1)";
    if (db_execute($conn, $sql, 'sss', [$rule_code, $rule_name, $description])) {
        header("Location: ../../pages/admin/policy_monitor.php?success=Rule deployed successfully");
    } else {
        header("Location: ../../pages/admin/policy_monitor.php?error=Failed to deploy rule");
    }
}
?>
