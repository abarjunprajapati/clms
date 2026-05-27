<?php
if (!function_exists('clms_portal_column_exists')) {
function clms_portal_column_exists(mysqli $conn, string $table, string $column): bool {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}
}

if (!function_exists('clms_portal_insert_contractor')) {
function clms_portal_insert_contractor(mysqli $conn, array $values): int {
    $columns = [];
    $params = [];
    $types = '';

    foreach ($values as $column => $value) {
        if (clms_portal_column_exists($conn, 'contractors', $column)) {
            $columns[] = '`' . str_replace('`', '``', $column) . '`';
            $params[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }
    }

    if (!$columns) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO contractors (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            return 0;
        }
        return (int)$stmt->insert_id;
    } catch (Throwable $e) {
        error_log('[customer_portal_context] contractor insert failed: ' . $e->getMessage());
        return 0;
    }
}
}

if (!function_exists('clms_get_portal_contractor')) {
function clms_get_portal_contractor(mysqli $conn) {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $role = $_SESSION['role'] ?? '';
    if (!$userId) {
        return null;
    }

    $existing = db_single($conn, "SELECT * FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]);
    if ($existing) {
        return $existing;
    }

    if ($role !== 'customer') {
        return null;
    }

    $customerCode = $_SESSION['customer_code'] ?? $_SESSION['contractor_id'] ?? ('CUST' . $userId);
    $customerName = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
    $portalCode = 'CUST-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string)$customerCode);
    $applicationNo = 'CUSTAPP-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string)$customerCode);

    $id = clms_portal_insert_contractor($conn, [
        'user_id' => $userId,
        'vendor_code' => $portalCode,
        'vendor_name' => $customerName,
        'contractor_name' => $customerName,
        'name' => $customerName,
        'sap_code' => $portalCode,
        'application_no' => $applicationNo,
        'email' => $_SESSION['email'] ?? '',
        'mobile' => $_SESSION['mobile'] ?? '',
    ]);

    if (!$id) {
        return null;
    }

    return db_single($conn, "SELECT * FROM contractors WHERE id = ? LIMIT 1", 'i', [$id]);
}
}
?>
