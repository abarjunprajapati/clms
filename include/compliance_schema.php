<?php
function ensureComplianceSchema($conn) {
    if (!function_exists('compliance_add_column_if_not_exists')) {
        function compliance_add_column_if_not_exists($conn, $table, $column, $definition) {
            $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($result && $result->num_rows == 0) {
                return $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
            return true;
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS wages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        worker_id INT NOT NULL,
        contractor_id INT NOT NULL,
        month_year VARCHAR(7) NOT NULL,
        total_days INT DEFAULT 0,
        salary DECIMAL(12,2) DEFAULT 0.00,
        wage_rate DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wage_worker_month (worker_id, month_year),
        KEY idx_wages_contractor_month (contractor_id, month_year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS compliance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        month VARCHAR(20),
        year YEAR,
        month_year VARCHAR(7),
        worker_count INT DEFAULT 0,
        attendance_days INT DEFAULT 0,
        wage_total DECIMAL(12,2) DEFAULT 0.00,
        esi_amount DECIMAL(10,2) DEFAULT 0.00,
        pf_amount DECIMAL(10,2) DEFAULT 0.00,
        klwf_amount DECIMAL(10,2) DEFAULT 0.00,
        esi_file VARCHAR(255),
        pf_file VARCHAR(255),
        klwf_file VARCHAR(255),
        validation_status VARCHAR(30) DEFAULT 'pending',
        validation_errors TEXT,
        status ENUM('pending','verified','rejected') DEFAULT 'pending',
        remarks TEXT,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_compliance_contractor_month (contractor_id, month_year),
        KEY idx_compliance_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    compliance_add_column_if_not_exists($conn, 'compliance', 'month_year', 'VARCHAR(7) AFTER year');
    compliance_add_column_if_not_exists($conn, 'compliance', 'worker_count', 'INT DEFAULT 0 AFTER month_year');
    compliance_add_column_if_not_exists($conn, 'compliance', 'attendance_days', 'INT DEFAULT 0 AFTER worker_count');
    compliance_add_column_if_not_exists($conn, 'compliance', 'wage_total', 'DECIMAL(12,2) DEFAULT 0.00 AFTER attendance_days');
    compliance_add_column_if_not_exists($conn, 'compliance', 'validation_status', 'VARCHAR(30) DEFAULT \'pending\' AFTER klwf_file');
    compliance_add_column_if_not_exists($conn, 'compliance', 'validation_errors', 'TEXT AFTER validation_status');
    compliance_add_column_if_not_exists($conn, 'compliance', 'challan_number', 'VARCHAR(100) AFTER month_year');
    compliance_add_column_if_not_exists($conn, 'compliance', 'amount', 'DECIMAL(10,2) DEFAULT 0.00 AFTER challan_number');
    compliance_add_column_if_not_exists($conn, 'compliance', 'file_path', 'VARCHAR(255) AFTER amount');
    compliance_add_column_if_not_exists($conn, 'compliance', 'challan_worker_count', 'INT DEFAULT 0 AFTER file_path');
    compliance_add_column_if_not_exists($conn, 'compliance', 'attendance_count', 'INT DEFAULT 0 AFTER challan_worker_count');
    compliance_add_column_if_not_exists($conn, 'compliance', 'verification_remarks', 'TEXT AFTER status');
    compliance_add_column_if_not_exists($conn, 'compliance', 'verified_by', 'INT DEFAULT NULL AFTER verification_remarks');
    compliance_add_column_if_not_exists($conn, 'compliance', 'verified_at', 'TIMESTAMP NULL AFTER verified_by');
    compliance_add_column_if_not_exists($conn, 'compliance', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    $conn->query("ALTER TABLE compliance MODIFY status ENUM('pending','verified','rejected','reupload_required') DEFAULT 'pending'");

    $conn->query("CREATE TABLE IF NOT EXISTS compliance_esi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        compliance_id INT NOT NULL,
        challan_no VARCHAR(100),
        challan_date DATE,
        employees_count INT DEFAULT 0,
        gross_wages DECIMAL(12,2) DEFAULT 0.00,
        employer_contribution DECIMAL(10,2) DEFAULT 0.00,
        employee_contribution DECIMAL(10,2) DEFAULT 0.00,
        total_contribution DECIMAL(10,2) DEFAULT 0.00,
        file_path VARCHAR(255),
        KEY idx_esi_compliance (compliance_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS compliance_epf (
        id INT AUTO_INCREMENT PRIMARY KEY,
        compliance_id INT NOT NULL,
        ecr_no VARCHAR(100),
        challan_date DATE,
        members_count INT DEFAULT 0,
        total_wages DECIMAL(12,2) DEFAULT 0.00,
        epf_contribution DECIMAL(10,2) DEFAULT 0.00,
        eps_contribution DECIMAL(10,2) DEFAULT 0.00,
        total_pf DECIMAL(10,2) DEFAULT 0.00,
        file_path VARCHAR(255),
        ecr_file_path VARCHAR(255),
        KEY idx_epf_compliance (compliance_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS compliance_klwf (
        id INT AUTO_INCREMENT PRIMARY KEY,
        compliance_id INT NOT NULL,
        challan_no VARCHAR(100),
        payment_date DATE,
        worker_count INT DEFAULT 0,
        employee_contribution DECIMAL(10,2) DEFAULT 0.00,
        employer_contribution DECIMAL(10,2) DEFAULT 0.00,
        amount DECIMAL(10,2) DEFAULT 0.00,
        file_path VARCHAR(255),
        KEY idx_klwf_compliance (compliance_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS compliance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        compliance_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        user_id INT DEFAULT 0,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_logs_compliance (compliance_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    compliance_add_column_if_not_exists($conn, 'workmen', 'compliance_status', "ENUM('pending','verified','non_compliant') DEFAULT 'pending' AFTER training_status");
    compliance_add_column_if_not_exists($conn, 'workmen', 'last_compliance_month', "VARCHAR(7) DEFAULT NULL AFTER compliance_status");
    compliance_add_column_if_not_exists($conn, 'contractors', 'compliance_status', "ENUM('pending','verified','non_compliant') DEFAULT 'pending' AFTER status");
}

function complianceMonthParts($monthYear) {
    $ts = strtotime($monthYear . '-01');
    if (!$ts) {
        $ts = strtotime(date('Y-m-01'));
    }
    return [date('F', $ts), (int)date('Y', $ts), date('Y-m', $ts)];
}
?>

