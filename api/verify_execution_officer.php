<?php
session_start();
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

function eo_verify_json($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function eo_verify_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function eo_verify_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function eo_verify_lookup($conn, $code) {
    if ($code === '') return null;

    if (eo_verify_table_exists($conn, 'users')) {
        $where = ["contractor_id = ?"];
        if (eo_verify_column_exists($conn, 'users', 'employee_code')) {
            $where[] = "employee_code = ?";
        }
        $sql = "SELECT id, name, email, mobile, role, contractor_id"
             . (eo_verify_column_exists($conn, 'users', 'employee_code') ? ", employee_code" : ", contractor_id AS employee_code")
             . " FROM users WHERE role = 'execution_officer' AND (" . implode(' OR ', $where) . ") LIMIT 1";
        $params = array_fill(0, count($where), $code);
        $user = db_single($conn, $sql, str_repeat('s', count($params)), $params);
        if ($user) {
            return [
                'id' => (int)$user['id'],
                'employee_code' => $user['employee_code'] ?: $user['contractor_id'],
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'mobile' => $user['mobile'] ?? '',
                'source' => 'User Master',
            ];
        }
    }

    if (eo_verify_table_exists($conn, 'execution_officers')) {
        $officer = db_single(
            $conn,
            "SELECT id, employee_code, name, email, mobile FROM execution_officers WHERE employee_code = ? LIMIT 1",
            's',
            [$code]
        );
        if ($officer) {
            return [
                'id' => (int)$officer['id'],
                'employee_code' => $officer['employee_code'],
                'name' => $officer['name'] ?? '',
                'email' => $officer['email'] ?? '',
                'mobile' => $officer['mobile'] ?? '',
                'source' => 'Execution Officer Master',
            ];
        }
    }

    foreach (['sap_employee_master', 'sap_employees', 'employee_master', 'sqlserver_employee_master'] as $table) {
        if (!eo_verify_table_exists($conn, $table)) continue;
        $codeCol = eo_verify_column_exists($conn, $table, 'employee_code') ? 'employee_code' : (eo_verify_column_exists($conn, $table, 'e_code') ? 'e_code' : '');
        if ($codeCol === '') continue;
        $nameExpr = eo_verify_column_exists($conn, $table, 'name') ? 'name' : (eo_verify_column_exists($conn, $table, 'employee_name') ? 'employee_name' : "''");
        $emailExpr = eo_verify_column_exists($conn, $table, 'email') ? 'email' : "''";
        $mobileExpr = eo_verify_column_exists($conn, $table, 'mobile') ? 'mobile' : (eo_verify_column_exists($conn, $table, 'phone') ? 'phone' : "''");
        $safeTable = str_replace('`', '``', $table);
        $safeCodeCol = str_replace('`', '``', $codeCol);
        $row = db_single(
            $conn,
            "SELECT `$safeCodeCol` AS employee_code, $nameExpr AS name, $emailExpr AS email, $mobileExpr AS mobile FROM `$safeTable` WHERE `$safeCodeCol` = ? LIMIT 1",
            's',
            [$code]
        );
        if ($row) {
            return [
                'id' => 0,
                'employee_code' => $row['employee_code'],
                'name' => $row['name'] ?? '',
                'email' => $row['email'] ?? '',
                'mobile' => $row['mobile'] ?? '',
                'source' => strtoupper(str_replace('_', ' ', $table)),
            ];
        }
    }

    return null;
}

try {
    $code = strtoupper(trim((string)($_GET['code'] ?? $_POST['code'] ?? '')));
    if ($code === '') {
        eo_verify_json(['success' => false, 'message' => 'Executing Officer E-Code is required.'], 400);
    }

    $officer = eo_verify_lookup($conn, $code);
    if (!$officer) {
        eo_verify_json(['success' => false, 'message' => 'E-Code User Master/SAP/SQL Server mein nahi mila.'], 404);
    }

    eo_verify_json(['success' => true, 'data' => $officer]);
} catch (Throwable $e) {
    error_log('[VERIFY_EXECUTION_OFFICER] ' . $e->getMessage());
    eo_verify_json(['success' => false, 'message' => 'E-Code verification failed.'], 500);
}
