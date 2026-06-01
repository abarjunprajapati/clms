<?php
/**
 * Shared execution portal context.
 * Ensures the logged-in execution user has an execution_officers row and
 * baseline assignments so execution pages can show available operational data.
 */

if (!function_exists('clms_execution_table_exists')) {
function clms_execution_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}
}

if (!function_exists('clms_execution_column_exists')) {
function clms_execution_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}
}

if (!function_exists('clms_execution_insert_if_columns')) {
function clms_execution_insert_if_columns($conn, $table, array $values) {
    $columns = [];
    $params = [];
    $types = '';

    foreach ($values as $column => $value) {
        if (!clms_execution_column_exists($conn, $table, $column)) {
            continue;
        }
        $columns[] = '`' . str_replace('`', '``', $column) . '`';
        $params[] = $value;
        $types .= is_int($value) ? 'i' : 's';
    }

    if (!$columns) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES ($placeholders)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    $ok = mysqli_stmt_execute($stmt);
    $id = $ok ? (int)mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($stmt);
    return $id;
}
}

if (!function_exists('clms_execution_ensure_column')) {
function clms_execution_ensure_column($conn, $table, $column, $definition) {
    if (!clms_execution_table_exists($conn, $table) || clms_execution_column_exists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}
}

if (!function_exists('clms_execution_ensure_schema')) {
function clms_execution_ensure_schema($conn) {
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_officers (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        employee_code VARCHAR(50) NULL,
        name VARCHAR(150) NULL,
        email VARCHAR(150) NULL,
        mobile VARCHAR(20) NULL,
        department_id BIGINT NULL,
        designation VARCHAR(100) NULL,
        status VARCHAR(30) DEFAULT 'active',
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        UNIQUE KEY uq_execution_employee_code (employee_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_officer_contractors (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        contractor_id BIGINT NULL,
        work_order_id BIGINT NULL,
        assigned_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_officer_workorders (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        work_order_id BIGINT NULL,
        assigned_by BIGINT NULL,
        assigned_date DATE NULL,
        status VARCHAR(30) DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_worker_deployments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        workman_id BIGINT NULL,
        contractor_id BIGINT NULL,
        work_order_id BIGINT NULL,
        department_id BIGINT NULL,
        execution_officer_id BIGINT NULL,
        deployed_date DATE NULL,
        shift VARCHAR(20) NULL,
        status VARCHAR(30) DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_departments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        dept_name VARCHAR(150) NULL,
        department_name VARCHAR(150) NULL,
        status VARCHAR(30) DEFAULT 'active',
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS attendance (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        workman_id BIGINT NULL,
        check_in DATETIME NULL,
        check_out DATETIME NULL,
        device_id VARCHAR(100) NULL,
        status VARCHAR(30) DEFAULT 'present',
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS attendance_exceptions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        workman_id BIGINT NULL,
        contractor_id BIGINT NULL,
        exception_type VARCHAR(100) NULL,
        remarks TEXT NULL,
        status VARCHAR(30) DEFAULT 'open',
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_observations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        contractor_id BIGINT NULL,
        workman_id BIGINT NULL,
        work_order_id BIGINT NULL,
        observation_type VARCHAR(120) NULL,
        remarks TEXT NULL,
        severity VARCHAR(30) DEFAULT 'low',
        action_required TINYINT(1) DEFAULT 0,
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_escalations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        escalated_to VARCHAR(50) NULL,
        escalation_type VARCHAR(120) NULL,
        contractor_id BIGINT NULL,
        workman_id BIGINT NULL,
        severity VARCHAR(30) DEFAULT 'medium',
        remarks TEXT NULL,
        status VARCHAR(30) DEFAULT 'open',
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_actions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        workman_id BIGINT NULL,
        contractor_id BIGINT NULL,
        action_type VARCHAR(100) NULL,
        action_reason TEXT NULL,
        status VARCHAR(30) DEFAULT 'open',
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_recommendations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        workman_id BIGINT NULL,
        reason TEXT NULL,
        status VARCHAR(30) DEFAULT 'open',
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_audit_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        action VARCHAR(100) NULL,
        entity_type VARCHAR(100) NULL,
        entity_id BIGINT NULL,
        new_value TEXT NULL,
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS execution_notifications (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        execution_officer_id BIGINT NULL,
        recipient_role VARCHAR(50) NULL,
        title VARCHAR(200) NULL,
        message TEXT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'execution_officers' => [
            'employee_code' => 'VARCHAR(50) NULL',
            'name' => 'VARCHAR(150) NULL',
            'email' => 'VARCHAR(150) NULL',
            'mobile' => 'VARCHAR(20) NULL',
            'status' => "VARCHAR(30) DEFAULT 'active'",
            'created_at' => 'DATETIME NULL',
            'updated_at' => 'DATETIME NULL',
        ],
        'execution_officer_contractors' => [
            'execution_officer_id' => 'BIGINT NULL',
            'contractor_id' => 'BIGINT NULL',
            'work_order_id' => 'BIGINT NULL',
            'assigned_at' => 'DATETIME NULL',
        ],
        'execution_officer_workorders' => [
            'execution_officer_id' => 'BIGINT NULL',
            'work_order_id' => 'BIGINT NULL',
            'assigned_by' => 'BIGINT NULL',
            'assigned_date' => 'DATE NULL',
            'status' => "VARCHAR(30) DEFAULT 'active'",
        ],
        'execution_worker_deployments' => [
            'workman_id' => 'BIGINT NULL',
            'contractor_id' => 'BIGINT NULL',
            'work_order_id' => 'BIGINT NULL',
            'department_id' => 'BIGINT NULL',
            'execution_officer_id' => 'BIGINT NULL',
            'deployed_date' => 'DATE NULL',
            'shift' => 'VARCHAR(20) NULL',
            'status' => "VARCHAR(30) DEFAULT 'active'",
        ],
        'master_departments' => [
            'dept_name' => 'VARCHAR(150) NULL',
            'department_name' => 'VARCHAR(150) NULL',
            'status' => "VARCHAR(30) DEFAULT 'active'",
            'created_at' => 'DATETIME NULL',
        ],
        'attendance' => [
            'workman_id' => 'BIGINT NULL',
            'check_in' => 'DATETIME NULL',
            'check_out' => 'DATETIME NULL',
            'device_id' => 'VARCHAR(100) NULL',
            'status' => "VARCHAR(30) DEFAULT 'present'",
            'created_at' => 'DATETIME NULL',
        ],
        'attendance_exceptions' => [
            'workman_id' => 'BIGINT NULL',
            'contractor_id' => 'BIGINT NULL',
            'exception_type' => 'VARCHAR(100) NULL',
            'remarks' => 'TEXT NULL',
            'status' => "VARCHAR(30) DEFAULT 'open'",
            'created_at' => 'DATETIME NULL',
        ],
        'execution_observations' => [
            'execution_officer_id' => 'BIGINT NULL',
            'contractor_id' => 'BIGINT NULL',
            'workman_id' => 'BIGINT NULL',
            'work_order_id' => 'BIGINT NULL',
            'observation_type' => 'VARCHAR(120) NULL',
            'remarks' => 'TEXT NULL',
            'severity' => "VARCHAR(30) DEFAULT 'low'",
            'action_required' => 'TINYINT(1) DEFAULT 0',
            'created_at' => 'DATETIME NULL',
        ],
        'execution_escalations' => [
            'execution_officer_id' => 'BIGINT NULL',
            'escalated_to' => 'VARCHAR(50) NULL',
            'escalation_type' => 'VARCHAR(120) NULL',
            'contractor_id' => 'BIGINT NULL',
            'workman_id' => 'BIGINT NULL',
            'severity' => "VARCHAR(30) DEFAULT 'medium'",
            'remarks' => 'TEXT NULL',
            'status' => "VARCHAR(30) DEFAULT 'open'",
            'created_at' => 'DATETIME NULL',
        ],
        'execution_actions' => [
            'execution_officer_id' => 'BIGINT NULL',
            'workman_id' => 'BIGINT NULL',
            'contractor_id' => 'BIGINT NULL',
            'action_type' => 'VARCHAR(100) NULL',
            'action_reason' => 'TEXT NULL',
            'status' => "VARCHAR(30) DEFAULT 'open'",
            'created_at' => 'DATETIME NULL',
        ],
        'execution_recommendations' => [
            'execution_officer_id' => 'BIGINT NULL',
            'workman_id' => 'BIGINT NULL',
            'reason' => 'TEXT NULL',
            'status' => "VARCHAR(30) DEFAULT 'open'",
            'created_at' => 'DATETIME NULL',
        ],
        'execution_audit_logs' => [
            'execution_officer_id' => 'BIGINT NULL',
            'action' => 'VARCHAR(100) NULL',
            'entity_type' => 'VARCHAR(100) NULL',
            'entity_id' => 'BIGINT NULL',
            'new_value' => 'TEXT NULL',
            'created_at' => 'DATETIME NULL',
        ],
        'execution_notifications' => [
            'execution_officer_id' => 'BIGINT NULL',
            'recipient_role' => 'VARCHAR(50) NULL',
            'title' => 'VARCHAR(200) NULL',
            'message' => 'TEXT NULL',
            'is_read' => 'TINYINT(1) DEFAULT 0',
            'created_at' => 'DATETIME NULL',
        ],
    ] as $table => $columns) {
        foreach ($columns as $column => $definition) {
            clms_execution_ensure_column($conn, $table, $column, $definition);
        }
    }
}
}

if (!function_exists('clms_execution_get_officer_id')) {
function clms_execution_get_officer_id($conn, $userId = null) {
    $userId = (int)($userId ?? ($_SESSION['user_id'] ?? 0));
    if (!$userId) {
        return 0;
    }
    clms_execution_ensure_schema($conn);

    $user = db_single($conn, "SELECT id, contractor_id, name, email, mobile FROM users WHERE id = ? LIMIT 1", 'i', [$userId]);
    $employeeCode = trim((string)($user['contractor_id'] ?? ''));
    if ($employeeCode === '') {
        $employeeCode = 'EXEC-' . $userId;
    }

    $officer = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = ? LIMIT 1", 's', [$employeeCode]);
    if (!$officer && clms_execution_column_exists($conn, 'execution_officers', 'employee_code')) {
        $id = clms_execution_insert_if_columns($conn, 'execution_officers', [
            'employee_code' => $employeeCode,
            'name' => $user['name'] ?? ($_SESSION['name'] ?? 'Execution Officer'),
            'email' => $user['email'] ?? '',
            'mobile' => $user['mobile'] ?? '',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($id) {
            $officer = ['id' => $id];
        }
    }

    $officerId = (int)($officer['id'] ?? 0);
    if ($officerId) {
        clms_execution_ensure_default_assignments($conn, $officerId);
    }

    return $officerId;
}
}

if (!function_exists('clms_execution_ensure_default_assignments')) {
function clms_execution_ensure_default_assignments($conn, $officerId) {
    $officerId = (int)$officerId;
    if (!$officerId) return;

    if (clms_execution_table_exists($conn, 'execution_officer_contractors') && clms_execution_table_exists($conn, 'contractors')) {
        $assigned = db_count($conn, "SELECT COUNT(*) c FROM execution_officer_contractors WHERE execution_officer_id = ?", 'i', [$officerId]);
        if ($assigned === 0) {
            $contractorCols = ['id'];
            $contractorCols[] = clms_execution_column_exists($conn, 'contractors', 'work_order_no') ? 'work_order_no' : "'' AS work_order_no";
            $contractorCols[] = clms_execution_column_exists($conn, 'contractors', 'vendor_code') ? 'vendor_code' : "'' AS vendor_code";
            $statusWhere = clms_execution_column_exists($conn, 'contractors', 'status')
                ? "WHERE COALESCE(status, '') NOT IN ('rejected', 'blocked', 'expired')"
                : "";
            $contractors = db_fetch_all($conn, "SELECT " . implode(', ', $contractorCols) . " FROM contractors $statusWhere");
            foreach ($contractors as $contractor) {
                $workOrderId = null;
                if (clms_execution_table_exists($conn, 'work_orders')) {
                    if (!empty($contractor['work_order_no'])) {
                        $wo = db_single($conn, "SELECT id FROM work_orders WHERE work_order_no = ? LIMIT 1", 's', [$contractor['work_order_no']]);
                        if ($wo) {
                            $workOrderId = (int)$wo['id'];
                        }
                    }
                    if ($workOrderId === null && !empty($contractor['vendor_code'])) {
                        $wo = db_single($conn, "SELECT id FROM work_orders WHERE vendor_code = ? ORDER BY id DESC LIMIT 1", 's', [$contractor['vendor_code']]);
                        if ($wo) {
                            $workOrderId = (int)$wo['id'];
                        }
                    }
                }
                clms_execution_insert_if_columns($conn, 'execution_officer_contractors', [
                    'execution_officer_id' => $officerId,
                    'contractor_id' => (int)$contractor['id'],
                    'work_order_id' => $workOrderId,
                    'assigned_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    if (clms_execution_table_exists($conn, 'execution_officer_workorders') && clms_execution_table_exists($conn, 'work_orders')) {
        $assigned = db_count($conn, "SELECT COUNT(*) c FROM execution_officer_workorders WHERE execution_officer_id = ?", 'i', [$officerId]);
        if ($assigned === 0) {
            $woStatusWhere = clms_execution_column_exists($conn, 'work_orders', 'wo_status')
                ? "WHERE COALESCE(wo_status, 'ACTIVE') = 'ACTIVE'"
                : "";
            $workOrders = db_fetch_all($conn, "SELECT id FROM work_orders $woStatusWhere");
            foreach ($workOrders as $wo) {
                clms_execution_insert_if_columns($conn, 'execution_officer_workorders', [
                    'execution_officer_id' => $officerId,
                    'work_order_id' => (int)$wo['id'],
                    'assigned_by' => (int)($_SESSION['user_id'] ?? 0),
                    'assigned_date' => date('Y-m-d'),
                    'status' => 'active',
                ]);
            }
        }
    }

    if (clms_execution_table_exists($conn, 'execution_worker_deployments') && clms_execution_table_exists($conn, 'workmen')) {
        $assigned = db_count($conn, "SELECT COUNT(*) c FROM execution_worker_deployments WHERE execution_officer_id = ?", 'i', [$officerId]);
        if ($assigned === 0) {
            $workerCols = ['w.id'];
            $workerCols[] = clms_execution_column_exists($conn, 'workmen', 'contractor_id') ? 'w.contractor_id' : '0 AS contractor_id';
            $workerCols[] = clms_execution_column_exists($conn, 'workmen', 'work_order_no') ? 'w.work_order_no' : "'' AS work_order_no";
            $workerWhere = clms_execution_column_exists($conn, 'workmen', 'status')
                ? "WHERE COALESCE(w.status, '') NOT IN ('draft', 'rejected', 'blocked', 'inactive')"
                : "";
            $workers = db_fetch_all($conn, "
                SELECT " . implode(', ', $workerCols) . "
                FROM workmen w
                $workerWhere
                ORDER BY w.id DESC
                LIMIT 500
            ");
            foreach ($workers as $worker) {
                $workOrderId = null;
                if (!empty($worker['work_order_no']) && clms_execution_table_exists($conn, 'work_orders')) {
                    $wo = db_single($conn, "SELECT id FROM work_orders WHERE work_order_no = ? LIMIT 1", 's', [$worker['work_order_no']]);
                    if ($wo) {
                        $workOrderId = (int)$wo['id'];
                    }
                }
                clms_execution_insert_if_columns($conn, 'execution_worker_deployments', [
                    'workman_id' => (int)$worker['id'],
                    'contractor_id' => (int)$worker['contractor_id'],
                    'work_order_id' => $workOrderId,
                    'execution_officer_id' => $officerId,
                    'deployed_date' => date('Y-m-d'),
                    'shift' => 'General',
                    'status' => 'active',
                ]);
            }
        }
    }
}
}
