<?php
/**
 * Contractor/customer onboarding gate helpers.
 * Keeps login redirects, page access, and sidebar visibility in sync.
 */

if (!function_exists('clms_onboarding_table_exists')) {
function clms_onboarding_table_exists($conn, $table) {
    if (!$conn || !$table) return false;
    $table = clms_db_real_escape_string($conn, $table);
    $result = clms_db_query($conn, "SHOW TABLES LIKE '{$table}'");
    return $result && clms_db_num_rows($result) > 0;
}
}

if (!function_exists('clms_onboarding_is_complete')) {
function clms_onboarding_is_complete($conn, $role, $account_code = '', $user_id = 0) {
    $role = strtolower((string)$role);
    $account_code = trim((string)$account_code);
    $user_id = (int)$user_id;

    if ($role === 'contractor') {
        if (!clms_onboarding_table_exists($conn, 'contractors')) return false;

        if ($account_code !== '') {
            $row = db_single($conn, "SELECT status FROM contractors WHERE vendor_code = ? ORDER BY id DESC LIMIT 1", 's', [$account_code]);
            if ($row && strtolower((string)($row['status'] ?? '')) === 'approved') return true;
        }

        if ($user_id > 0) {
            $row = db_single($conn, "SELECT status FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$user_id]);
            if ($row && strtolower((string)($row['status'] ?? '')) === 'approved') return true;
        }

        return false;
    }

    if ($role === 'customer') {
        if ($account_code === '' || !clms_onboarding_table_exists($conn, 'contractor_annexure3a')) return false;
        $approved = db_count(
            $conn,
            "SELECT COUNT(*) AS c FROM contractor_annexure3a WHERE customer_code = ? AND LOWER(COALESCE(status, '')) = 'approved'",
            's',
            [$account_code]
        );
        return $approved > 0;
    }

    return true;
}
}

if (!function_exists('clms_onboarding_redirect_for_session')) {
function clms_onboarding_redirect_for_session($conn) {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['contractor', 'customer'], true)) return '';

    $account_code = $role === 'customer'
        ? ($_SESSION['customer_code'] ?? $_SESSION['contractor_id'] ?? '')
        : ($_SESSION['contractor_id'] ?? $_SESSION['vendor_code'] ?? '');

    $done = clms_onboarding_is_complete($conn, $role, $account_code, $_SESSION['user_id'] ?? 0);
    if ($done) return '';

    return $role === 'customer'
        ? 'pages/customer/annexure-3a.php'
        : 'pages/contractor/annexure-2a.php';
}
}

if (!function_exists('clms_onboarding_current_page_allowed')) {
function clms_onboarding_current_page_allowed($role, $script_name) {
    $script_name = str_replace('\\', '/', (string)$script_name);
    if ($role === 'customer') {
        return preg_match('#/pages/customer/(annexure-3a|welfare-actions)\.php$#', $script_name) === 1;
    }
    if ($role === 'contractor') {
        return preg_match('#/pages/contractor/(annexure-2a|welfare-actions)\.php$#', $script_name) === 1;
    }
    return true;
}
}
